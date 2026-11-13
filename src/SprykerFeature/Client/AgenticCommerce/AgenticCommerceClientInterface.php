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

interface AgenticCommerceClientInterface
{
    /**
     * Specification:
     * - Makes no Zed request.
     * - Validates a delegated access token (JSON Web Token) locally.
     * - Requires an RS256 signature that verifies under the delegated access public key, the configured issuer, the resource URI of a declared protected resource in the audience, and current issued-at, not-before and expiration times, with a leeway of five seconds for clock differences between hosts.
     * - Requires a non-empty subject, token identifier, client id, store and consent uuid claim, and a scopes claim that is a list.
     * - Returns `isValid=false` with error code `invalid_token` when the issuer, the resource URI or the public key is not configured, and for a storefront access token.
     * - Returns `isValid=true` with the claims mapped to `AgenticCommerceAccessTokenTransfer` otherwise.
     * - Returns `isValid=false` with error code `invalid_token` for any failed check, without telling which check failed.
     * - Does not check whether the consent behind the token is still active; see `validateAgenticCommerceConsentUsage()`.
     *
     * @api
     */
    public function validateAgenticCommerceAccessToken(string $accessToken): AgenticCommerceAccessTokenValidationResponseTransfer;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Keeps storefront applications from accepting a delegated access token.
     * - Treats a token as delegated when it carries the consent uuid claim or names the configured resource URI in its audience; the signature is not checked.
     * - Sets `isValid=false` and an `invalid_token` error for a delegated access token.
     * - Returns the response unchanged for any other token, including a malformed one, which the storefront validation rejects itself.
     *
     * @api
     */
    public function rejectAgenticCommerceToken(
        OauthAccessTokenValidationRequestTransfer $oauthAccessTokenValidationRequestTransfer,
        OauthAccessTokenValidationResponseTransfer $oauthAccessTokenValidationResponseTransfer
    ): OauthAccessTokenValidationResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Validates an authorization request as `AgenticCommerceFacadeInterface::validateAgenticCommerceAuthorizationRequest()` does.
     *
     * @api
     */
    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Returns the principals the customer may act as through a consent, as `AgenticCommerceFacadeInterface::getAgenticCommercePrincipalCandidates()` does.
     *
     * @api
     */
    public function getAgenticCommercePrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Returns the customer of a principal, with its company user and business unit for a company user, as `AgenticCommerceFacadeInterface::getAgenticCommercePrincipalCustomer()` does.
     *
     * @api
     */
    public function getAgenticCommercePrincipalCustomer(
        AgenticCommercePrincipalCustomerRequestTransfer $agenticCommercePrincipalCustomerRequestTransfer
    ): AgenticCommercePrincipalCustomerResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Narrows the requested scopes to those the principal may grant, as `AgenticCommerceFacadeInterface::getAgenticCommerceGrantableScopes()` does.
     *
     * @api
     */
    public function getAgenticCommerceGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Returns the scopes of the given names with the glossary keys of their consent labels, as `AgenticCommerceFacadeInterface::getAgenticCommerceScopeCollection()` does.
     *
     * @api
     */
    public function getAgenticCommerceScopeCollection(
        AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer
    ): AgenticCommerceScopeCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Returns the authorization details types of the wired consent constraint plugins, as `AgenticCommerceFacadeInterface::getAgenticCommerceAuthorizationDetailsTypeCollection()` does.
     *
     * @api
     */
    public function getAgenticCommerceAuthorizationDetailsTypeCollection(
        AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer
    ): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Returns the consent durations in days a customer can choose on the consent screen, the first one being the default.
     * - Reads `\SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig::getConsentDurationsInDays()`, the list the consent creation accepts.
     *
     * @api
     *
     * @return list<int>
     */
    public function getAgenticCommerceConsentDurationsInDays(): array;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Records the customer's consent and issues authorization codes, as `AgenticCommerceFacadeInterface::createAgenticCommerceConsentCollection()` does.
     * - Returns the consents without their refresh token identifier.
     *
     * @api
     */
    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Returns consents filtered by effective status, as `AgenticCommerceFacadeInterface::getAgenticCommerceConsentCollection()` does.
     * - Returns the consents without their refresh token identifier.
     *
     * @api
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Revokes consents on behalf of the customer who gave them, as `AgenticCommerceFacadeInterface::updateAgenticCommerceConsentCollection()` does.
     * - Returns the consents without their refresh token identifier.
     *
     * @api
     */
    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Requires `agenticCommerceCompanyConsentConditions` with `idCompany` and `companyUserUuid`, the company user of the acting company admin.
     * - Returns the active consents of the company's active company users, as `AgenticCommerceFacadeInterface::getAgenticCommerceConsentsByCompany()` does, each with the user's name and email and the client name shown at consent.
     * - Returns an empty collection unless Zed confirmed that the acting company user belongs to the company and holds the manage permission.
     * - Returns the consents without their refresh token identifier.
     *
     * @api
     */
    public function getAgenticCommerceConsentsByCompany(
        AgenticCommerceCompanyConsentCriteriaTransfer $agenticCommerceCompanyConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Revokes one consent on behalf of a company admin after Zed checked that it belongs to the company and that the company admin holds the manage permission, as `AgenticCommerceFacadeInterface::revokeAgenticCommerceConsentByCompanyAdmin()` does.
     * - Returns the revoked consent without its refresh token identifier, or the error.
     *
     * @api
     */
    public function revokeAgenticCommerceConsentByCompanyAdmin(
        AgenticCommerceCompanyAdminRevocationRequestTransfer $agenticCommerceCompanyAdminRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Checks that the consent behind a delegated call may still be used and records its last use, as `AgenticCommerceFacadeInterface::validateAgenticCommerceConsentUsage()` does.
     * - Returns the consent without its refresh token identifier.
     *
     * @api
     */
    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Revokes the consent that holds the presented refresh token (RFC 7009, OAuth 2.0 Token Revocation), as `AgenticCommerceFacadeInterface::revokeAgenticCommerceRefreshToken()` does.
     *
     * @api
     */
    public function revokeAgenticCommerceRefreshToken(
        AgenticCommerceRefreshTokenRevocationRequestTransfer $agenticCommerceRefreshTokenRevocationRequestTransfer
    ): AgenticCommerceRefreshTokenRevocationResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Registers a public client following RFC 7591 (OAuth 2.0 Dynamic Client Registration Protocol), as `AgenticCommerceFacadeInterface::registerAgenticCommerceClient()` does.
     *
     * @api
     */
    public function registerAgenticCommerceClient(
        AgenticCommerceClientRegistrationRequestTransfer $agenticCommerceClientRegistrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Returns the constraint fields the consent page offers for the principal and scopes, in plugin order, as `AgenticCommerceFacadeInterface::getAgenticCommerceConsentConstraintFields()` does.
     *
     * @api
     */
    public function getAgenticCommerceConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Asks whether the consent of the context may place the quote, as `AgenticCommerceFacadeInterface::checkAgenticCommerceOrderPlacement()` decides.
     * - Sends `AgenticCommerceContextTransfer.consentUuid` and the quote; Zed loads the consent and ignores the scopes of the context.
     * - Answers `refused` with `consent_mismatch` when the Zed request does not carry the same consent, which `AgenticCommerceZedRequestMetaDataProviderPlugin` sends for the authenticated delegated request.
     *
     * @api
     */
    public function checkOrderPlacement(
        AgenticCommerceContextTransfer $agenticCommerceContextTransfer,
        QuoteTransfer $quoteTransfer
    ): AgenticCommerceOrderPlacementCheckTransfer;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Remembers the consent uuid of the delegated request being handled, or forgets it with `null`.
     * - The value lives until it is set again: the Glue application sets it when a delegated access token authenticates and clears it when a new main request starts.
     * - `AgenticCommerceZedRequestMetaDataProviderPlugin` sends it with every Zed request.
     *
     * @api
     */
    public function setAuthenticatedConsentUuid(?string $consentUuid): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Returns the consent uuid set by `setAuthenticatedConsentUuid()`.
     * - Returns `null` when the request being handled is not delegated.
     *
     * @api
     */
    public function findAuthenticatedConsentUuid(): ?string;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Asks SecurityBlocker whether the bucket of the type, IP and account is full; the IP may be empty for a bucket per account alone.
     * - Returns that response unchanged when it is blocked, `blockedFor` holding the seconds to wait.
     * - Otherwise counts the request and answers `isBlocked = false`, so the request that fills the bucket still passes.
     * - Counts nothing for `AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE`, which only `incrementAgenticCommerceFailedTokenAttempt()` fills.
     * - Answers "not blocked" and logs one error when SecurityBlocker fails, then makes no further SecurityBlocker call until `resetAgenticCommerceFailureLatches()`.
     *
     * @api
     */
    public function checkAgenticCommerceRateLimit(string $type, string $ip, ?string $account): SecurityCheckAuthResponseTransfer;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Returns true while a failed fetch of the Client ID Metadata Document (CIMD) of the client identifier is stored in the delegated access Redis.
     * - Returns false on a miss and, after logging one error, when Redis is not reachable.
     *
     * @api
     */
    public function hasAgenticCommerceMetadataDocumentFailure(string $clientIdentifier): bool;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Stores a failed fetch of the Client ID Metadata Document (CIMD) of the client identifier in the delegated access Redis for the given seconds.
     * - Removes a stored failure of the client identifier for `0` seconds or less.
     * - Logs one error when Redis is not reachable.
     *
     * @api
     */
    public function saveAgenticCommerceMetadataDocumentFailure(string $clientIdentifier, int $ttlSeconds): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Counts one failed token grant in the bucket of `AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN` (account is the client id) and one in the bucket of `AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE` (IP only).
     * - Counts nothing after a SecurityBlocker failure that `checkAgenticCommerceRateLimit()` or this method already logged.
     * - Logs one error and counts nothing when SecurityBlocker fails.
     *
     * @api
     */
    public function incrementAgenticCommerceFailedTokenAttempt(string $ip, ?string $clientId): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Call once at the start of each main request, before any other method of this client, and in a long-running process before each unit of work that needs Redis.
     * - Until the next reset, the first SecurityBlocker failure of the rate limit and the first failure of the delegated access Redis latch:
     *   later calls of that kind fail fast without a new connection attempt.
     * - Resets both latches, so the next call reaches SecurityBlocker and Redis again and logs its own outage.
     *
     * @api
     */
    public function resetAgenticCommerceFailureLatches(): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Adds the settings of the delegated access SecurityBlocker types and keeps the settings of other types.
     *
     * @api
     *
     * @param array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer> $securityBlockerConfigurationSettingsTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer>
     */
    public function expandAgenticCommerceSecurityBlockerConfigurationSettings(array $securityBlockerConfigurationSettingsTransfers): array;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Reads the kill switch key of the given store from Redis.
     * - Returns false while delegated access is switched off, true otherwise.
     * - Returns true and logs one error when Redis is not reachable.
     *
     * @api
     */
    public function isAgenticCommerceEnabled(string $storeName): bool;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Reads the kill switch key of the given store from Redis.
     * - Returns false while delegated access is switched off, true otherwise.
     * - Unlike `isAgenticCommerceEnabled()`, does not fail open: throws when Redis is not reachable, for callers that report the state.
     *
     * @api
     *
     * @throws \SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException
     */
    public function isAgenticCommerceEnabledStrict(string $storeName): bool;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Switches delegated access off for the given store by storing the kill switch key without an expiry.
     *
     * @api
     *
     * @throws \SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException
     */
    public function disableAgenticCommerce(string $storeName): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Switches delegated access on again for the given store by deleting the kill switch key.
     *
     * @api
     *
     * @throws \SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException
     */
    public function enableAgenticCommerce(string $storeName): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Returns the cached valid consent check of the consent in the store, from the delegated access Redis.
     * - Returns null on a miss, on a revocation tombstone and when Redis is not reachable (fail closed), so the caller asks Zed.
     *
     * @api
     */
    public function findAgenticCommerceConsentUsageInCache(string $storeName, string $consentUuid): ?AgenticCommerceConsentUsageResponseTransfer;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Caches a valid consent check for `AgenticCommerceConfig::getConsentCacheTtlSeconds()` seconds, only when its key is absent (`SET NX`): it never replaces another entry or a revocation tombstone.
     * - Adds the consent to the index of the customer's cached consents in the store.
     * - Does nothing for a refused check, while the TTL is `0`, or when Redis is not reachable.
     *
     * @api
     */
    public function saveAgenticCommerceConsentUsageToCache(
        string $storeName,
        string $consentUuid,
        string $customerReference,
        AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer
    ): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Writes the tombstone `revoked` over the cache entry of every consent, for `AgenticCommerceConfig::getConsentCacheTombstoneTtlSeconds()` seconds; a tombstone is a miss and blocks every fill.
     * - Logs an error and returns when Redis is not reachable; the entries then expire with the cache TTL.
     *
     * @api
     *
     * @param list<string> $consentUuids
     */
    public function invalidateAgenticCommerceConsentUsageCache(string $storeName, array $consentUuids): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Tombstones every consent in the index of the customer's cached consents in the store, for consents removed without a revocation.
     * - Logs an error and returns when Redis is not reachable.
     *
     * @api
     */
    public function invalidateAgenticCommerceConsentUsageCacheByCustomerReference(string $storeName, string $customerReference): void;

    /**
     * Specification:
     * - Makes no Zed request.
     * - Returns true once per `AgenticCommerceConfig::getLastUsedAtWriteIntervalInSeconds()` and consent (`SET NX` on a marker key), and when Redis is not reachable.
     * - The caller then treats a cache hit as a miss and asks Zed, whose consent check writes `last_used_at`.
     *
     * @api
     */
    public function shouldTouchAgenticCommerceConsentLastUsedAt(string $storeName, string $consentUuid): bool;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Issues a cart hand-off token as `AgenticCommerceFacadeInterface::createAgenticCommerceCartHandoff()` does.
     *
     * @api
     */
    public function createCartHandoff(
        AgenticCommerceCartHandoffRequestTransfer $agenticCommerceCartHandoffRequestTransfer
    ): AgenticCommerceCartHandoffResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed request.
     * - Redeems a cart hand-off token as `AgenticCommerceFacadeInterface::redeemAgenticCommerceCartHandoff()` does.
     *
     * @api
     */
    public function redeemCartHandoff(
        AgenticCommerceCartHandoffRedeemRequestTransfer $agenticCommerceCartHandoffRedeemRequestTransfer
    ): AgenticCommerceCartHandoffRedeemResponseTransfer;
}
