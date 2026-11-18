<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataValidationResponseTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\RedirectUriMatcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;

class ClientMetadataValidator implements ClientMetadataValidatorInterface
{
    protected const int REDIRECT_URIS_MAX = 5;

    protected const int CLIENT_NAME_MAX_LENGTH = 100;

    protected const int SOFTWARE_FIELD_MAX_LENGTH = 255;

    /**
     * Control characters and the Unicode bidirectional embeddings, overrides (U+202A to U+202E) and isolates (U+2066 to U+2069),
     * which can reorder how the consent page shows the name.
     */
    protected const string PATTERN_REFUSED_CLIENT_NAME_CHARACTER = '/[\p{Cc}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

    /**
     * Only whitespace or Unicode separators, such as the no-break space.
     */
    protected const string PATTERN_BLANK_CLIENT_NAME = '/^[\s\p{Z}]*$/u';

    /**
     * @var non-empty-string
     */
    protected const string SCOPE_DELIMITER = ' ';

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_GRANT_TYPES = [
        SharedAgenticCommerceConfig::GRANT_TYPE_AUTHORIZATION_CODE,
        SharedAgenticCommerceConfig::GRANT_TYPE_REFRESH_TOKEN,
    ];

    public function __construct(
        protected RedirectUriValidatorInterface $redirectUriValidator,
        protected RedirectUriMatcherInterface $redirectUriMatcher,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected SupportedScopeReaderInterface $supportedScopeReader
    ) {
    }

    /**
     * @param list<string>|null $allowedRedirectHosts
     */
    public function validate(
        AgenticCommerceClientMetadataTransfer $clientMetadataTransfer,
        ?array $allowedRedirectHosts,
        string $registrationType
    ): AgenticCommerceClientMetadataValidationResponseTransfer {
        $redirectUriError = $this->findRedirectUriError($clientMetadataTransfer->getRedirectUris(), $allowedRedirectHosts);

        if ($redirectUriError !== null) {
            return $this->createInvalidResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $redirectUriError);
        }

        $acceptedGrantTypes = $this->resolveGrantTypes($clientMetadataTransfer->getGrantTypes(), $registrationType);
        $metadataError = $acceptedGrantTypes === null
            ? 'Only the authorization_code and refresh_token grant types are supported, and authorization_code is required.'
            : $this->findMetadataError($clientMetadataTransfer);

        if ($metadataError !== null) {
            return $this->createInvalidResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $metadataError);
        }

        $requestedScopes = array_filter(explode(static::SCOPE_DELIMITER, (string)$clientMetadataTransfer->getScope()));

        return (new AgenticCommerceClientMetadataValidationResponseTransfer())
            ->setIsValid(true)
            ->setAcceptedScopes(array_values(array_intersect($this->supportedScopeReader->getSupportedScopeNames(), $requestedScopes)))
            ->setAcceptedGrantTypes($acceptedGrantTypes)
            ->setClientName($this->resolveClientName($clientMetadataTransfer, $registrationType));
    }

    /**
     * A Client ID Metadata Document (CIMD) keeps only the supported grant types; a Dynamic Client Registration (DCR) asking for any other is refused.
     *
     * @param array<string> $requestedGrantTypes
     *
     * @return list<string>|null
     */
    protected function resolveGrantTypes(array $requestedGrantTypes, string $registrationType): ?array
    {
        if ($requestedGrantTypes === []) {
            return static::SUPPORTED_GRANT_TYPES;
        }

        $acceptedGrantTypes = array_values(array_intersect(static::SUPPORTED_GRANT_TYPES, $requestedGrantTypes));

        if ($registrationType === SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC && count($acceptedGrantTypes) !== count(array_unique($requestedGrantTypes))) {
            return null;
        }

        return in_array(SharedAgenticCommerceConfig::GRANT_TYPE_AUTHORIZATION_CODE, $acceptedGrantTypes, true) ? $acceptedGrantTypes : null;
    }

    /**
     * Falls back to the host of the client ID (CIMD) or of the first redirect URI (DCR); the redirect URIs are validated before.
     */
    protected function resolveClientName(AgenticCommerceClientMetadataTransfer $clientMetadataTransfer, string $registrationType): string
    {
        $clientName = (string)$clientMetadataTransfer->getClientName();

        if ($clientName !== '') {
            return $clientName;
        }

        $clientIdHost = $registrationType === SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT
            ? $this->redirectUriValidator->findHost((string)$clientMetadataTransfer->getClientId())
            : null;

        return $clientIdHost ?? (string)$this->redirectUriValidator->findHost(array_values($clientMetadataTransfer->getRedirectUris())[0]);
    }

    /**
     * @param array<string> $redirectUris
     * @param list<string>|null $allowedRedirectHosts
     */
    protected function findRedirectUriError(array $redirectUris, ?array $allowedRedirectHosts): ?string
    {
        if ($redirectUris === [] || count($redirectUris) > static::REDIRECT_URIS_MAX) {
            return sprintf('Between one and %d redirect_uris are required.', static::REDIRECT_URIS_MAX);
        }

        foreach ($redirectUris as $redirectUri) {
            if (!$this->redirectUriValidator->isRedirectUriAllowed($redirectUri)) {
                return sprintf('The redirect_uri "%s" is not allowed.', $redirectUri);
            }

            if ($allowedRedirectHosts !== null && !$this->isRedirectHostAllowed($redirectUri, $allowedRedirectHosts)) {
                return sprintf('The redirect_uri host of "%s" is not on the allowlist.', $redirectUri);
            }
        }

        return null;
    }

    /**
     * Exact comparison of normalized hosts: `claude.ai.evil.example` does not match `claude.ai`.
     *
     * @param list<string> $allowedRedirectHosts
     */
    protected function isRedirectHostAllowed(string $redirectUri, array $allowedRedirectHosts): bool
    {
        if ($this->redirectUriMatcher->isLoopbackRedirectUri($redirectUri)) {
            return true;
        }

        $redirectHost = $this->redirectUriValidator->findHost($redirectUri);

        return $redirectHost !== null && in_array($redirectHost, $this->agenticCommerceService->normalizeHosts($allowedRedirectHosts), true);
    }

    protected function findMetadataError(AgenticCommerceClientMetadataTransfer $clientMetadataTransfer): ?string
    {
        return match (true) {
            !$this->isPublicClientPossible($clientMetadataTransfer) => 'Only public clients (token_endpoint_auth_method "none") can register.',
            array_diff($clientMetadataTransfer->getResponseTypes(), [SharedAgenticCommerceConfig::RESPONSE_TYPE_CODE]) !== [] => 'Only the code response type is supported.',
            mb_strlen((string)$clientMetadataTransfer->getClientName()) > static::CLIENT_NAME_MAX_LENGTH => sprintf('client_name may have at most %d characters.', static::CLIENT_NAME_MAX_LENGTH),
            !$this->isDisplayableClientName((string)$clientMetadataTransfer->getClientName()) => 'client_name must not be blank or contain control or bidirectional formatting characters.',
            !$this->redirectUriValidator->isOptionalHttpsUri($clientMetadataTransfer->getClientUri()), !$this->redirectUriValidator->isOptionalHttpsUri($clientMetadataTransfer->getLogoUri()) => 'client_uri and logo_uri must be HTTPS URIs of at most 2048 characters, without user information or fragment.',
            mb_strlen((string)$clientMetadataTransfer->getSoftwareId()) > static::SOFTWARE_FIELD_MAX_LENGTH, mb_strlen((string)$clientMetadataTransfer->getSoftwareVersion()) > static::SOFTWARE_FIELD_MAX_LENGTH => sprintf('software_id and software_version may have at most %d characters.', static::SOFTWARE_FIELD_MAX_LENGTH),
            default => null,
        };
    }

    /**
     * An empty name counts as absent and falls back to a host; a blank name, a control character or a bidirectional formatting character is refused.
     */
    protected function isDisplayableClientName(string $clientName): bool
    {
        if ($clientName === '') {
            return true;
        }

        return preg_match(static::PATTERN_BLANK_CLIENT_NAME, $clientName) === 0 && preg_match(static::PATTERN_REFUSED_CLIENT_NAME_CHARACTER, $clientName) === 0;
    }

    /**
     * The client is always stored as public: a client that authenticates with a secret or a key is accepted only when it also supports `none`.
     */
    protected function isPublicClientPossible(AgenticCommerceClientMetadataTransfer $clientMetadataTransfer): bool
    {
        $tokenEndpointAuthMethod = $clientMetadataTransfer->getTokenEndpointAuthMethod() ?? SharedAgenticCommerceConfig::TOKEN_ENDPOINT_AUTH_METHOD_NONE;

        return $tokenEndpointAuthMethod === SharedAgenticCommerceConfig::TOKEN_ENDPOINT_AUTH_METHOD_NONE
            || in_array(SharedAgenticCommerceConfig::TOKEN_ENDPOINT_AUTH_METHOD_NONE, $clientMetadataTransfer->getTokenEndpointAuthMethodsSupported(), true);
    }

    protected function createInvalidResponse(string $errorCode, string $errorDescription): AgenticCommerceClientMetadataValidationResponseTransfer
    {
        return (new AgenticCommerceClientMetadataValidationResponseTransfer())
            ->setIsValid(false)
            ->setErrorCode($errorCode)
            ->setErrorDescription($errorDescription);
    }
}
