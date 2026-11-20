<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientResolveResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientResolverInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;

class AuthorizationRequestValidator implements AuthorizationRequestValidatorInterface
{
    /**
     * RFC 7636 (Proof Key for Code Exchange by OAuth Public Clients), section 4.2: an S256 challenge is the unpadded base64url encoding of a SHA-256 digest, always 43 characters.
     */
    protected const string S256_CODE_CHALLENGE_PATTERN = '/^[A-Za-z0-9_-]{43}$/D';

    /**
     * RFC 6749 (The OAuth 2.0 Authorization Framework), section 3.3: scopes are delimited by a space.
     *
     * @var non-empty-string
     */
    protected const string SCOPE_DELIMITER = ' ';

    protected const string ERROR_DESCRIPTION_CLIENT_METADATA_DOCUMENT_UNUSABLE = 'The client metadata document could not be used.';

    protected const string ERROR_DESCRIPTION_CLIENT_UNKNOWN_OR_INACTIVE = 'The client is unknown or not active.';

    protected const string ERROR_DESCRIPTION_REDIRECT_URI_NOT_REGISTERED = 'The redirect_uri is not registered for this client.';

    protected const string ERROR_DESCRIPTION_UNSUPPORTED_RESPONSE_TYPE = 'Only the "code" response type is supported.';

    protected const string ERROR_DESCRIPTION_CODE_CHALLENGE_METHOD_NOT_S256 = 'The code_challenge_method must be S256.';

    protected const string ERROR_DESCRIPTION_CODE_CHALLENGE_MALFORMED = 'The code_challenge is missing or malformed.';

    protected const string ERROR_DESCRIPTION_RESOURCE_NOT_PROTECTED = 'The resource parameter must name a protected resource of this server.';

    protected const string ERROR_DESCRIPTION_NO_GRANTABLE_SCOPE = 'None of the requested scopes can be granted to this client.';

    public function __construct(
        protected ClientResolverInterface $clientResolver,
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected RedirectUriMatcherInterface $redirectUriMatcher,
        protected RequestedScopeNarrowerInterface $requestedScopeNarrower,
        protected SupportedScopeReaderInterface $supportedScopeReader,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AuthorizationDetailsParserInterface $authorizationDetailsParser,
        protected StoreFacadeInterface $storeFacade
    ) {
    }

    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer {
        $agenticCommerceAuthorizationRequestTransfer->setIsLoopbackRedirect(false);
        $agenticCommerceAuthorizationResponseTransfer = (new AgenticCommerceAuthorizationResponseTransfer())
            ->setAuthorizationRequest($agenticCommerceAuthorizationRequestTransfer)
            ->setIsValid(false)
            ->setIsRedirectable(false);

        $clientResolveResponseTransfer = $this->resolveClient((string)$agenticCommerceAuthorizationRequestTransfer->getClientId());

        if ($clientResolveResponseTransfer->getFailureReason() !== null) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT, static::ERROR_DESCRIPTION_CLIENT_METADATA_DOCUMENT_UNUSABLE)
                ->setFailureReason($clientResolveResponseTransfer->getFailureReason());
        }

        $agenticCommerceClientTransfer = $this->findActiveAgenticCommerceClient($clientResolveResponseTransfer);

        if ($agenticCommerceClientTransfer === null) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT, static::ERROR_DESCRIPTION_CLIENT_UNKNOWN_OR_INACTIVE);
        }

        $redirectUri = (string)$agenticCommerceAuthorizationRequestTransfer->getRedirectUri();

        if (!$this->redirectUriMatcher->isMatching($redirectUri, array_values($agenticCommerceClientTransfer->getRedirectUris()))) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_REDIRECT_URI_NOT_REGISTERED);
        }

        $agenticCommerceAuthorizationRequestTransfer->setIsLoopbackRedirect($this->redirectUriMatcher->isLoopbackRedirectUri($redirectUri));
        $agenticCommerceAuthorizationResponseTransfer
            ->setAgenticCommerceClient($agenticCommerceClientTransfer)
            ->setIsRedirectable($this->isRedirectTargetTrusted($agenticCommerceClientTransfer));

        return $this->validateRedirectableAuthorizationRequest($agenticCommerceAuthorizationRequestTransfer, $agenticCommerceAuthorizationResponseTransfer, $agenticCommerceClientTransfer);
    }

    protected function validateRedirectableAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer
    ): AgenticCommerceAuthorizationResponseTransfer {
        if ($agenticCommerceAuthorizationRequestTransfer->getResponseType() !== SharedAgenticCommerceConfig::RESPONSE_TYPE_CODE) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_UNSUPPORTED_RESPONSE_TYPE, static::ERROR_DESCRIPTION_UNSUPPORTED_RESPONSE_TYPE);
        }

        if ($agenticCommerceAuthorizationRequestTransfer->getCodeChallengeMethod() !== $this->agenticCommerceConfig->getCodeChallengeMethod()) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_CODE_CHALLENGE_METHOD_NOT_S256);
        }

        if (preg_match(static::S256_CODE_CHALLENGE_PATTERN, (string)$agenticCommerceAuthorizationRequestTransfer->getCodeChallenge()) !== 1) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_CODE_CHALLENGE_MALFORMED);
        }

        if ($this->agenticCommerceService->findProtectedResourceByResourceUri((string)$agenticCommerceAuthorizationRequestTransfer->getResource()) === null) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_TARGET, static::ERROR_DESCRIPTION_RESOURCE_NOT_PROTECTED);
        }

        $scopeCeiling = array_values($agenticCommerceClientTransfer->getScopeCeiling());
        $grantableScopes = $this->requestedScopeNarrower->narrow(
            $this->parseScope((string)$agenticCommerceAuthorizationRequestTransfer->getScope()),
            $scopeCeiling,
            $this->supportedScopeReader->getSupportedScopeNames(),
        );

        // An empty ceiling means the shop has granted the client nothing yet: the consent page says so instead of the client getting an error.
        if ($grantableScopes === [] && $scopeCeiling !== []) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_SCOPE, static::ERROR_DESCRIPTION_NO_GRANTABLE_SCOPE);
        }

        $parseResponseTransfer = $this->authorizationDetailsParser->parse(
            $agenticCommerceAuthorizationRequestTransfer->getAuthorizationDetails(),
            $this->createConstraintFieldRequest($grantableScopes),
        );

        if (!$parseResponseTransfer->getIsValid()) {
            return $this->setError($agenticCommerceAuthorizationResponseTransfer, SharedAgenticCommerceConfig::ERROR_INVALID_AUTHORIZATION_DETAILS, (string)$parseResponseTransfer->getErrorDescription());
        }

        return $agenticCommerceAuthorizationResponseTransfer
            ->setIsValid(true)
            ->setGrantableScopes($grantableScopes)
            ->setRequestedConstraints($parseResponseTransfer->getRequestedConstraints());
    }

    /**
     * The principal is not known yet, so a requested constraint is validated without it.
     *
     * @param list<string> $grantableScopes
     */
    protected function createConstraintFieldRequest(array $grantableScopes): AgenticCommerceConstraintFieldRequestTransfer
    {
        $storeTransfer = $this->storeFacade->getCurrentStore(true);

        return (new AgenticCommerceConstraintFieldRequestTransfer())
            ->setScopes($grantableScopes)
            ->setStoreName($storeTransfer->getNameOrFail())
            ->setCurrencyCode($storeTransfer->getDefaultCurrencyIsoCode());
    }

    /**
     * A self-registered client chooses its own redirect URIs, so sending errors there would make the shop an open redirector
     * (RFC 9700, OAuth 2.0 Security Best Current Practice, section 4.11.2). Only a pre-registered or verified client gets them.
     */
    protected function isRedirectTargetTrusted(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): bool
    {
        return $agenticCommerceClientTransfer->getRegistrationType() === SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED
            || $agenticCommerceClientTransfer->getTrustLevel() === SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED;
    }

    protected function resolveClient(string $clientIdentifier): AgenticCommerceClientResolveResponseTransfer
    {
        if ($clientIdentifier === '') {
            return new AgenticCommerceClientResolveResponseTransfer();
        }

        return $this->clientResolver->resolveClient($clientIdentifier);
    }

    protected function findActiveAgenticCommerceClient(
        AgenticCommerceClientResolveResponseTransfer $clientResolveResponseTransfer
    ): ?AgenticCommerceClientTransfer {
        $agenticCommerceClientTransfer = $clientResolveResponseTransfer->getAgenticCommerceClient();

        if ($agenticCommerceClientTransfer === null || $agenticCommerceClientTransfer->getStatus() !== SharedAgenticCommerceConfig::CLIENT_STATUS_ACTIVE) {
            return null;
        }

        return $agenticCommerceClientTransfer;
    }

    /**
     * @return list<string>
     */
    protected function parseScope(string $scope): array
    {
        return array_values(array_filter(
            explode(static::SCOPE_DELIMITER, $scope),
            static fn (string $requestedScope): bool => $requestedScope !== '',
        ));
    }

    protected function setError(
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        string $errorCode,
        string $errorDescription
    ): AgenticCommerceAuthorizationResponseTransfer {
        return $agenticCommerceAuthorizationResponseTransfer
            ->setErrorCode($errorCode)
            ->setErrorDescription($errorDescription);
    }
}
