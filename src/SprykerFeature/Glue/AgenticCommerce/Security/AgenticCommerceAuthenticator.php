<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AgenticCommerceAuthenticator extends AbstractAuthenticator
{
    protected const string SUBJECT_KEY_CUSTOMER_REFERENCE = 'customer_reference';

    protected const string ERROR_DESCRIPTION_INVALID_TOKEN = 'The access token is invalid, expired or revoked.';

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected StoreClientInterface $storeClient,
        protected AgenticCommerceProtectedResourceResolver $agenticCommerceProtectedResourceResolver,
        protected AgenticCommerceAuthenticationChallengeBuilder $agenticCommerceAuthenticationChallengeBuilder,
        protected AgenticCommerceScopeRoleMapper $agenticCommerceScopeRoleMapper,
        protected AgenticCommerceBearerTokenExtractor $agenticCommerceBearerTokenExtractor,
        protected AgenticCommerceAuthenticationEntryPoint $agenticCommerceAuthenticationEntryPoint,
        protected AgenticCommercePathMatcher $agenticCommercePathMatcher,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return !$this->agenticCommercePathMatcher->isPublicProtocolPath($request);
    }

    public function authenticate(Request $request): Passport
    {
        $agenticCommerceProtectedResourceTransfer = $this->agenticCommerceProtectedResourceResolver->getProtectedResource($request);
        $agenticCommerceAccessTokenTransfer = $this->findValidAccessToken($request);

        if (
            $agenticCommerceAccessTokenTransfer === null
            || !$this->isIssuedForTheResource($agenticCommerceAccessTokenTransfer, $agenticCommerceProtectedResourceTransfer)
            || !$this->isIssuedForTheCurrentStore($agenticCommerceAccessTokenTransfer)
        ) {
            throw new AuthenticationException(static::ERROR_DESCRIPTION_INVALID_TOKEN);
        }

        $customerReference = $this->findCustomerReference((string)$agenticCommerceAccessTokenTransfer->getSubject());

        if ($customerReference === null) {
            throw new AuthenticationException(static::ERROR_DESCRIPTION_INVALID_TOKEN);
        }

        $agenticCommerceConsentTransfer = $this->findUsableConsent($agenticCommerceAccessTokenTransfer, $customerReference);

        if ($agenticCommerceConsentTransfer === null) {
            throw new AuthenticationException(static::ERROR_DESCRIPTION_INVALID_TOKEN);
        }

        $agenticCommerceApiUser = new AgenticCommerceApiUser(
            $agenticCommerceAccessTokenTransfer,
            $agenticCommerceConsentTransfer,
            $this->agenticCommerceScopeRoleMapper->mapScopesToRoles(
                array_values(array_intersect($agenticCommerceAccessTokenTransfer->getScopes(), $agenticCommerceConsentTransfer->getGrantedScopes())),
                $agenticCommerceConsentTransfer->getPrincipalTypeOrFail(),
            ),
            $agenticCommerceProtectedResourceTransfer->getNameOrFail(),
            $customerReference,
        );

        return new SelfValidatingPassport(
            new UserBadge($agenticCommerceAccessTokenTransfer->getTokenIdentifierOrFail(), static fn (): AgenticCommerceApiUser => $agenticCommerceApiUser),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $agenticCommerceApiUser = $token->getUser();

        if ($agenticCommerceApiUser instanceof AgenticCommerceApiUser) {
            $this->agenticCommerceClient->setAuthenticatedConsentUuid($agenticCommerceApiUser->getAgenticCommerceConsent()->getUuidOrFail());
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($this->agenticCommerceBearerTokenExtractor->findBearerToken($request) === null) {
            return $this->agenticCommerceAuthenticationEntryPoint->start($request, $exception);
        }

        return new JsonResponse(
            [
                AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR => AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN,
                AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR_DESCRIPTION => static::ERROR_DESCRIPTION_INVALID_TOKEN,
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                AgenticCommerceAuthenticationChallengeBuilder::HEADER_WWW_AUTHENTICATE => $this->agenticCommerceAuthenticationChallengeBuilder->buildUnauthorizedHeader(
                    $request,
                    AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN,
                ),
            ],
        );
    }

    protected function findValidAccessToken(Request $request): ?AgenticCommerceAccessTokenTransfer
    {
        $accessToken = $this->agenticCommerceBearerTokenExtractor->findBearerToken($request);

        if ($accessToken === null) {
            return null;
        }

        $agenticCommerceAccessTokenValidationResponseTransfer = $this->agenticCommerceClient->validateAgenticCommerceAccessToken($accessToken);

        if (!$agenticCommerceAccessTokenValidationResponseTransfer->getIsValid()) {
            return null;
        }

        return $agenticCommerceAccessTokenValidationResponseTransfer->getAgenticCommerceAccessToken();
    }

    protected function isIssuedForTheResource(
        AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer,
        AgenticCommerceProtectedResourceTransfer $agenticCommerceProtectedResourceTransfer
    ): bool {
        return in_array($agenticCommerceProtectedResourceTransfer->getResourceUriOrFail(), $agenticCommerceAccessTokenTransfer->getAudience(), true);
    }

    protected function isIssuedForTheCurrentStore(AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer): bool
    {
        return $this->storeClient->getCurrentStore()->getNameOrFail() === $agenticCommerceAccessTokenTransfer->getStoreName();
    }

    protected function findUsableConsent(
        AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer,
        string $customerReference
    ): ?AgenticCommerceConsentTransfer {
        $storeName = $agenticCommerceAccessTokenTransfer->getStoreNameOrFail();
        $consentUuid = $agenticCommerceAccessTokenTransfer->getConsentUuidOrFail();
        $isConsentCacheEnabled = $this->agenticCommerceConfig->getConsentCacheTtlSeconds() > 0;

        if ($isConsentCacheEnabled) {
            $cachedAgenticCommerceConsentTransfer = $this->findCachedConsent($agenticCommerceAccessTokenTransfer, $customerReference);

            if ($cachedAgenticCommerceConsentTransfer !== null && !$this->agenticCommerceClient->shouldTouchAgenticCommerceConsentLastUsedAt($storeName, $consentUuid)) {
                return $cachedAgenticCommerceConsentTransfer;
            }
        }

        $agenticCommerceConsentUsageResponseTransfer = $this->agenticCommerceClient->validateAgenticCommerceConsentUsage(
            (new AgenticCommerceConsentUsageRequestTransfer())
                ->setConsentUuid($consentUuid)
                ->setClientIdentifier($agenticCommerceAccessTokenTransfer->getClientIdOrFail())
                ->setCustomerReference($customerReference)
                ->setStoreName($storeName),
        );

        if (!$agenticCommerceConsentUsageResponseTransfer->getIsValid()) {
            return null;
        }

        if ($isConsentCacheEnabled) {
            $this->agenticCommerceClient->saveAgenticCommerceConsentUsageToCache($storeName, $consentUuid, $customerReference, $agenticCommerceConsentUsageResponseTransfer);
        }

        return $agenticCommerceConsentUsageResponseTransfer->getAgenticCommerceConsent();
    }

    protected function findCachedConsent(
        AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer,
        string $customerReference
    ): ?AgenticCommerceConsentTransfer {
        $agenticCommerceConsentUsageResponseTransfer = $this->agenticCommerceClient->findAgenticCommerceConsentUsageInCache(
            $agenticCommerceAccessTokenTransfer->getStoreNameOrFail(),
            $agenticCommerceAccessTokenTransfer->getConsentUuidOrFail(),
        );

        if ($agenticCommerceConsentUsageResponseTransfer === null || !$this->isCachedForTheToken($agenticCommerceConsentUsageResponseTransfer, $agenticCommerceAccessTokenTransfer, $customerReference)) {
            return null;
        }

        return $agenticCommerceConsentUsageResponseTransfer->getAgenticCommerceConsent();
    }

    protected function isCachedForTheToken(
        AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer,
        AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer,
        string $customerReference
    ): bool {
        $agenticCommerceConsentTransfer = $agenticCommerceConsentUsageResponseTransfer->getAgenticCommerceConsent();

        return $agenticCommerceConsentUsageResponseTransfer->getIsValid()
            && $agenticCommerceConsentTransfer !== null
            && $agenticCommerceConsentTransfer->getUuid() === $agenticCommerceAccessTokenTransfer->getConsentUuid()
            && $agenticCommerceConsentTransfer->getClientIdentifier() === $agenticCommerceAccessTokenTransfer->getClientId()
            && $agenticCommerceConsentTransfer->getCustomerReference() === $customerReference
            && $agenticCommerceConsentTransfer->getStoreName() === $agenticCommerceAccessTokenTransfer->getStoreName();
    }

    protected function findCustomerReference(string $subject): ?string
    {
        $userIdentifier = json_decode($subject, true);
        $customerReference = is_array($userIdentifier) ? ($userIdentifier[static::SUBJECT_KEY_CUSTOMER_REFERENCE] ?? null) : null;

        return is_string($customerReference) && $customerReference !== '' ? $customerReference : null;
    }
}
