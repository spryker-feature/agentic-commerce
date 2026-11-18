<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce;

use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyAdminRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceContextTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCustomerRequestTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCustomerResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceFactory getFactory()
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AgenticCommerceClient extends AbstractClient implements AgenticCommerceClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function validateAgenticCommerceAccessToken(string $accessToken): AgenticCommerceAccessTokenValidationResponseTransfer
    {
        return $this->getFactory()
            ->createAgenticCommerceAccessTokenValidator()
            ->validateAgenticCommerceAccessToken($accessToken);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function rejectAgenticCommerceToken(
        OauthAccessTokenValidationRequestTransfer $oauthAccessTokenValidationRequestTransfer,
        OauthAccessTokenValidationResponseTransfer $oauthAccessTokenValidationResponseTransfer
    ): OauthAccessTokenValidationResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceAccessTokenValidator()
            ->rejectAgenticCommerceToken($oauthAccessTokenValidationRequestTransfer, $oauthAccessTokenValidationResponseTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->validateAgenticCommerceAuthorizationRequest($agenticCommerceAuthorizationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommercePrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer
    {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommercePrincipalCandidates($customerTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommercePrincipalCustomer(
        AgenticCommercePrincipalCustomerRequestTransfer $agenticCommercePrincipalCustomerRequestTransfer
    ): AgenticCommercePrincipalCustomerResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommercePrincipalCustomer($agenticCommercePrincipalCustomerRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommerceGrantableScopes($agenticCommerceGrantableScopesRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceScopeCollection(
        AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer
    ): AgenticCommerceScopeCollectionTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommerceScopeCollection($agenticCommerceScopeCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceAuthorizationDetailsTypeCollection(
        AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer
    ): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommerceAuthorizationDetailsTypeCollection($agenticCommerceAuthorizationDetailsTypeCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return list<int>
     */
    public function getAgenticCommerceConsentDurationsInDays(): array
    {
        return $this->getFactory()->getConfig()->getConsentDurationsInDays();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->createAgenticCommerceConsentCollection($agenticCommerceConsentCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommerceConsentCollection($agenticCommerceConsentCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceConsentsByCompany(
        AgenticCommerceCompanyConsentCriteriaTransfer $agenticCommerceCompanyConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommerceConsentsByCompany($agenticCommerceCompanyConsentCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function revokeAgenticCommerceConsentByCompanyAdmin(
        AgenticCommerceCompanyAdminRevocationRequestTransfer $agenticCommerceCompanyAdminRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->revokeAgenticCommerceConsentByCompanyAdmin($agenticCommerceCompanyAdminRevocationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->updateAgenticCommerceConsentCollection($agenticCommerceConsentCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->validateAgenticCommerceConsentUsage($agenticCommerceConsentUsageRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function revokeAgenticCommerceRefreshToken(
        AgenticCommerceRefreshTokenRevocationRequestTransfer $agenticCommerceRefreshTokenRevocationRequestTransfer
    ): AgenticCommerceRefreshTokenRevocationResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->revokeAgenticCommerceRefreshToken($agenticCommerceRefreshTokenRevocationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerAgenticCommerceClient(
        AgenticCommerceClientRegistrationRequestTransfer $agenticCommerceClientRegistrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->registerAgenticCommerceClient($agenticCommerceClientRegistrationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->getAgenticCommerceConsentConstraintFields($agenticCommerceConstraintFieldRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function checkOrderPlacement(
        AgenticCommerceContextTransfer $agenticCommerceContextTransfer,
        QuoteTransfer $quoteTransfer
    ): AgenticCommerceOrderPlacementCheckTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->checkAgenticCommerceOrderPlacement(
                (new AgenticCommerceOrderPlacementCheckRequestTransfer())
                    ->setContext($agenticCommerceContextTransfer)
                    ->setQuote($quoteTransfer),
            );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAuthenticatedConsentUuid(?string $consentUuid): void
    {
        $this->getFactory()
            ->createAuthenticatedConsentHolder()
            ->setConsentUuid($consentUuid);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findAuthenticatedConsentUuid(): ?string
    {
        return $this->getFactory()
            ->createAuthenticatedConsentHolder()
            ->findConsentUuid();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function checkAgenticCommerceRateLimit(string $type, string $ip, ?string $account): SecurityCheckAuthResponseTransfer
    {
        return $this->getFactory()->createRateLimitChecker()->checkRateLimit($type, $ip, $account);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasAgenticCommerceMetadataDocumentFailure(string $clientIdentifier): bool
    {
        return $this->getFactory()->createMetadataDocumentFailureCache()->has($clientIdentifier);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function saveAgenticCommerceMetadataDocumentFailure(string $clientIdentifier, int $ttlSeconds): void
    {
        $this->getFactory()->createMetadataDocumentFailureCache()->save($clientIdentifier, $ttlSeconds);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function incrementAgenticCommerceFailedTokenAttempt(string $ip, ?string $clientId): void
    {
        $this->getFactory()->createRateLimitChecker()->incrementFailedAttempt($ip, $clientId);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer> $securityBlockerConfigurationSettingsTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer>
     */
    public function expandAgenticCommerceSecurityBlockerConfigurationSettings(array $securityBlockerConfigurationSettingsTransfers): array
    {
        return $this->getFactory()->createSecurityBlockerConfigurationSettingsExpander()->expand($securityBlockerConfigurationSettingsTransfers);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function resetAgenticCommerceFailureLatches(): void
    {
        $this->getFactory()->createRateLimitChecker()->forgetFailure();
        $this->getFactory()->createAgenticCommerceRedisWrapper()->resetAvailability();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isAgenticCommerceEnabled(string $storeName): bool
    {
        return $this->getFactory()->createKillSwitch()->isEnabled($storeName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isAgenticCommerceEnabledStrict(string $storeName): bool
    {
        return $this->getFactory()->createKillSwitch()->readIsEnabled($storeName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function disableAgenticCommerce(string $storeName): void
    {
        $this->getFactory()->createKillSwitch()->disable($storeName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function enableAgenticCommerce(string $storeName): void
    {
        $this->getFactory()->createKillSwitch()->enable($storeName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findAgenticCommerceConsentUsageInCache(string $storeName, string $consentUuid): ?AgenticCommerceConsentUsageResponseTransfer
    {
        return $this->getFactory()->createConsentUsageCache()->find($storeName, $consentUuid);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function saveAgenticCommerceConsentUsageToCache(
        string $storeName,
        string $consentUuid,
        string $customerReference,
        AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer
    ): void {
        $this->getFactory()->createConsentUsageCache()->save($storeName, $consentUuid, $customerReference, $agenticCommerceConsentUsageResponseTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param list<string> $consentUuids
     */
    public function invalidateAgenticCommerceConsentUsageCache(string $storeName, array $consentUuids): void
    {
        $this->getFactory()->createConsentUsageCache()->invalidate($storeName, $consentUuids);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function invalidateAgenticCommerceConsentUsageCacheByCustomerReference(string $storeName, string $customerReference): void
    {
        $this->getFactory()->createConsentUsageCache()->invalidateByCustomerReference($storeName, $customerReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function shouldTouchAgenticCommerceConsentLastUsedAt(string $storeName, string $consentUuid): bool
    {
        return $this->getFactory()->createConsentUsageCache()->shouldTouchLastUsedAt($storeName, $consentUuid);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createCartHandoff(
        AgenticCommerceCartHandoffRequestTransfer $agenticCommerceCartHandoffRequestTransfer
    ): AgenticCommerceCartHandoffResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->createAgenticCommerceCartHandoff($agenticCommerceCartHandoffRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function redeemCartHandoff(
        AgenticCommerceCartHandoffRedeemRequestTransfer $agenticCommerceCartHandoffRedeemRequestTransfer
    ): AgenticCommerceCartHandoffRedeemResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceStub()
            ->redeemAgenticCommerceCartHandoff($agenticCommerceCartHandoffRedeemRequestTransfer);
    }
}
