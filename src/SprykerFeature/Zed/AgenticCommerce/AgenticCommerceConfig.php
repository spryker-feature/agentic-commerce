<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce;

use Spryker\Shared\Oauth\OauthConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;

/**
 * @method \SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig getSharedConfig()
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AgenticCommerceConfig extends AbstractBundleConfig
{
    /**
     * @var non-empty-string
     */
    protected const string LIST_SEPARATOR = ',';

    /**
     * @uses \Spryker\Zed\QuoteApproval\Communication\Plugin\Permission\PlaceOrderPermissionPlugin::KEY
     */
    protected const string PERMISSION_KEY_PLACE_ORDER = 'PlaceOrderPermissionPlugin';

    protected const int KILL_SWITCH_REASON_MINIMUM_LENGTH = 5;

    protected const int CART_HANDOFF_LIFETIME_IN_SECONDS = 900;

    protected const int CONSENT_REVOCATION_BATCH_SIZE = 500;

    protected const int COMPANY_CONSENT_READ_BATCH_SIZE = 500;

    protected const int HOUSEKEEPING_BATCH_SIZE = 500;

    /**
     * @var list<string>
     */
    protected const array CANCELED_OMS_STATE_NAMES = ['cancelled'];

    protected const int UNUSED_CLIENT_RETENTION_DAYS = 7;

    protected const int CONSENT_EVENT_RETENTION_DAYS = 365;

    protected const int INACTIVE_CONSENT_RETENTION_DAYS = 365;

    protected const int CONSENT_EVENT_CLIENT_DATA_RETENTION_DAYS = 30;

    protected const int CLIENT_ID_METADATA_DOCUMENT_FETCH_TIMEOUT_SECONDS = 5;

    protected const int CLIENT_ID_METADATA_DOCUMENT_CACHE_MIN_SECONDS = 300;

    protected const int CLIENT_ID_METADATA_DOCUMENT_CACHE_MAX_SECONDS = 86400;

    protected const int CLIENT_ID_METADATA_DOCUMENT_CACHE_DEFAULT_SECONDS = 3600;

    protected const int CLIENT_ID_METADATA_DOCUMENT_FAILURE_CACHE_TTL_DEFAULT_SECONDS = 300;

    /**
     * @uses \Spryker\Shared\Config\Application\Environment::defineRegion()
     */
    protected const string ENVIRONMENT_VARIABLE_REGION = 'SPRYKER_CURRENT_REGION';

    protected const string REGION_NAME_DEFAULT = 'default';

    protected const string CONSOLE_LOCALE_NAME = 'en_US';

    protected const int ACCESS_TOKEN_LIFETIME_IN_SECONDS = 600;

    /**
     * The storefront identity scopes a token of each principal type carries, so the principal gets the user-type roles a storefront token of it gets.
     */
    protected const array IDENTITY_SCOPES_BY_PRINCIPAL_TYPE = [
        SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER => [
            SharedAgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER,
        ],
        SharedAgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER => [
            SharedAgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER,
            SharedAgenticCommerceConfig::SCOPE_IDENTITY_COMPANY_USER,
        ],
    ];

    /**
     * Specification:
     * - Returns the entries of `AgenticCommerceConstants::SUPPORTED_SCOPES` as configured, without validating them.
     *
     * @api
     *
     * @return array<mixed>
     */
    public function getConfiguredSupportedScopes(): array
    {
        return (array)$this->get(AgenticCommerceConstants::SUPPORTED_SCOPES, []);
    }

    /**
     * Specification:
     * - Returns the scopes a delegated-access client can be registered for and a customer can consent to.
     *
     * @api
     *
     * @return list<string>
     */
    public function getSupportedScopes(): array
    {
        return $this->getSharedConfig()->getSupportedScopes();
    }

    /**
     * Specification:
     * - Returns the locale the console commands translate their error messages into.
     *
     * @api
     */
    public function getConsoleLocaleName(): string
    {
        return static::CONSOLE_LOCALE_NAME;
    }

    /**
     * Specification:
     * - Returns the minimum number of characters, after trimming, of the reason the Back Office requires to switch delegated access off or on.
     *
     * @api
     */
    public function getKillSwitchReasonMinimumLength(): int
    {
        return static::KILL_SWITCH_REASON_MINIMUM_LENGTH;
    }

    /**
     * Specification:
     * - Returns the name of the region the process runs in, read from the environment variable `SPRYKER_CURRENT_REGION`.
     * - Returns `default` when the variable is not set.
     *
     * @api
     */
    public function getRegionName(): string
    {
        return getenv(static::ENVIRONMENT_VARIABLE_REGION) ?: static::REGION_NAME_DEFAULT;
    }

    /**
     * Specification:
     * - Returns the hosts a plain `http` redirect URI may use (RFC 8252 loopback redirection); every other redirect URI must be `https`.
     *
     * @api
     *
     * @return list<string>
     */
    public function getLoopbackRedirectHosts(): array
    {
        return SharedAgenticCommerceConfig::LOOPBACK_REDIRECT_HOSTS;
    }

    /**
     * Specification:
     * - Returns the canonical resource URI every authorization request must name in `resource` (RFC 8707, Resource Indicators for OAuth 2.0).
     *
     * @api
     */
    public function getResourceUri(): string
    {
        return $this->getSharedConfig()->getResourceUri();
    }

    /**
     * Specification:
     * - Returns the only Proof Key for Code Exchange (PKCE) code challenge method accepted, for every client.
     *
     * @api
     */
    public function getCodeChallengeMethod(): string
    {
        return SharedAgenticCommerceConfig::CODE_CHALLENGE_METHOD_S256;
    }

    /**
     * Specification:
     * - Returns the consent durations in days a customer can choose on the consent page.
     *
     * @api
     *
     * @return list<int>
     */
    public function getConsentDurationsInDays(): array
    {
        return $this->getSharedConfig()->getConsentDurationsInDays();
    }

    /**
     * Specification:
     * - Returns the minimum time in seconds between two writes of a consent's `last_used_at`, so a busy client does not write on every call.
     * - Delegates to `\SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig::getLastUsedAtWriteIntervalInSeconds()`, which the consent cache of the Glue firewall reads too.
     * - Change the interval only there: overriding this getter would change the database throttle but not the cache marker.
     *
     * @api
     */
    public function getLastUsedAtWriteIntervalInSeconds(): int
    {
        return $this->getSharedConfig()->getLastUsedAtWriteIntervalInSeconds();
    }

    /**
     * Specification:
     * - Returns how many seconds a cart hand-off token can be redeemed after it was issued.
     *
     * @api
     */
    public function getCartHandoffLifetimeInSeconds(): int
    {
        return static::CART_HANDOFF_LIFETIME_IN_SECONDS;
    }

    /**
     * Specification:
     * - Returns how many consents a system revocation revokes per transaction, so blocking a client with many consents does not hold one long transaction.
     *
     * @api
     */
    public function getConsentRevocationBatchSize(): int
    {
        return static::CONSENT_REVOCATION_BATCH_SIZE;
    }

    /**
     * Specification:
     * - Returns how many company users the company consent read looks up at a time, so a company with many users is read in bounded queries.
     *
     * @api
     */
    public function getCompanyConsentReadBatchSize(): int
    {
        return static::COMPANY_CONSENT_READ_BATCH_SIZE;
    }

    /**
     * Specification:
     * - Returns how many rows each housekeeping step reads and changes at a time, so no step holds one long transaction.
     *
     * @api
     */
    public function getHousekeepingBatchSize(): int
    {
        return static::HOUSEKEEPING_BATCH_SIZE;
    }

    /**
     * Specification:
     * - Returns the days after its creation a self-registered client (dynamic registration or client ID metadata document) that never got a consent is deleted by housekeeping.
     *
     * @api
     */
    public function getUnusedClientRetentionDays(): int
    {
        return static::UNUSED_CLIENT_RETENTION_DAYS;
    }

    /**
     * Specification:
     * - Returns the days a consent event is kept; housekeeping keeps the newest event of every consent and every event a retention plugin retains beyond it.
     *
     * @api
     */
    public function getConsentEventRetentionDays(): int
    {
        return static::CONSENT_EVENT_RETENTION_DAYS;
    }

    /**
     * Specification:
     * - Returns the days after its last change a revoked or expired consent is deleted by housekeeping, unless a retention plugin retains one of its events.
     *
     * @api
     */
    public function getInactiveConsentRetentionDays(): int
    {
        return static::INACTIVE_CONSENT_RETENTION_DAYS;
    }

    /**
     * Specification:
     * - Returns the days the IP address and user agent of a consent event are kept before housekeeping clears them; the event itself stays.
     *
     * @api
     */
    public function getConsentEventClientDataRetentionDays(): int
    {
        return static::CONSENT_EVENT_CLIENT_DATA_RETENTION_DAYS;
    }

    /**
     * Specification:
     * - Returns the storefront identity scopes a delegated access token of the principal type carries next to its consented scopes.
     * - Returns no identity scope for an unknown principal type, so such a token never gets the user-type roles of a storefront customer.
     *
     * @api
     *
     * @return list<string>
     */
    public function getIdentityScopes(string $principalType): array
    {
        return static::IDENTITY_SCOPES_BY_PRINCIPAL_TYPE[$principalType] ?? [];
    }

    /**
     * Specification:
     * - Returns the OMS state names of a canceled order item.
     * - An order whose items are all in one of these states does not count against an order budget.
     *
     * @api
     *
     * @return list<string>
     */
    public function getCanceledOmsStateNames(): array
    {
        return static::CANCELED_OMS_STATE_NAMES;
    }

    /**
     * Specification:
     * - Returns the reference of the merchant that stands for the shop itself, or `null` when there is none.
     * - A quote item without a merchant reference is sold by the shop: a merchant allowlist allows it only when it lists this reference.
     *
     * @api
     */
    public function getShopMerchantReference(): ?string
    {
        return null;
    }

    /**
     * Specification:
     * - Returns the lifetime of a delegated access token in seconds.
     *
     * @api
     */
    public function getAccessTokenLifetimeInSeconds(): int
    {
        return static::ACCESS_TOKEN_LIFETIME_IN_SECONDS;
    }

    /**
     * Specification:
     * - Returns the issuer identifier written to the `iss` claim of delegated access tokens.
     *
     * @api
     */
    public function getIssuer(): string
    {
        return $this->getSharedConfig()->getIssuer();
    }

    /**
     * Specification:
     * - Returns the private key, PEM (Privacy-Enhanced Mail) encoded, that signs delegated access tokens.
     * - Throws when the key is not configured.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    public function getPrivateKey(): string
    {
        return $this->getRequiredValue(AgenticCommerceConstants::PRIVATE_KEY);
    }

    /**
     * Specification:
     * - Returns the key that encrypts delegated access refresh tokens; it must differ from the storefront OAuth encryption key.
     * - Throws when the key is not configured.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    public function getRefreshTokenEncryptionKey(): string
    {
        return $this->getRequiredValue(AgenticCommerceConstants::REFRESH_TOKEN_ENCRYPTION_KEY);
    }

    /**
     * Specification:
     * - Returns the configured storefront public key: a path to the key file, with or without the `file://` prefix, or the key contents.
     * - Returns an empty string when it is not configured.
     *
     * @api
     */
    public function getStorefrontPublicKey(): string
    {
        return (string)$this->get(OauthConstants::PUBLIC_KEY_PATH, '');
    }

    /**
     * Specification:
     * - Returns the key that encrypts storefront OAuth refresh tokens and authorization codes.
     * - Returns an empty string when it is not configured.
     *
     * @api
     */
    public function getStorefrontEncryptionKey(): string
    {
        return (string)$this->get(OauthConstants::ENCRYPTION_KEY, '');
    }

    /**
     * Specification:
     * - Maps a delegated-access scope to the permission key a company user needs to hold it.
     * - A scope without a mapping is not restricted by permissions.
     * - A mapped scope is granted when a role of the company user holds the permission key; permission configuration, for example amount limits, is not evaluated here and stays with the endpoint that performs the action.
     * - Maps `order:place` to `PlaceOrderPermissionPlugin` by default.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getScopePermissionKeys(): array
    {
        return [
            SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE => static::PERMISSION_KEY_PLACE_ORDER,
        ];
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function getRequiredValue(string $key): string
    {
        $value = (string)$this->get($key, '');

        if ($value === '') {
            throw new AgenticCommerceConfigurationException(sprintf('The configuration "%s" is required to issue delegated access tokens.', $key));
        }

        return $value;
    }

    /**
     * Specification:
     * - Returns whether the IP address and user agent of the request are recorded in consent events.
     * - Returns `false` unless the project switches it on.
     *
     * @api
     */
    public function isConsentEventClientDataCaptured(): bool
    {
        return $this->getSharedConfig()->isConsentEventClientDataCaptured();
    }

    /**
     * Specification:
     * - Returns whether a Client ID Metadata Document (CIMD) URL is accepted as `client_id`.
     *
     * @api
     */
    public function isClientIdMetadataDocumentEnabled(): bool
    {
        return $this->getSharedConfig()->isClientIdMetadataDocumentEnabled();
    }

    /**
     * Specification:
     * - Returns whether the Dynamic Client Registration (DCR) endpoint accepts registrations.
     *
     * @api
     */
    public function isDynamicClientRegistrationEnabled(): bool
    {
        return $this->getSharedConfig()->isDynamicClientRegistrationEnabled();
    }

    /**
     * Specification:
     * - Returns the seconds the fetch of a Client ID Metadata Document (CIMD) may take in total.
     *
     * @api
     */
    public function getClientIdMetadataDocumentFetchTimeoutSeconds(): int
    {
        return static::CLIENT_ID_METADATA_DOCUMENT_FETCH_TIMEOUT_SECONDS;
    }

    /**
     * Specification:
     * - Returns the maximum size in bytes of a Client ID Metadata Document (CIMD) body.
     *
     * @api
     */
    public function getClientIdMetadataDocumentMaxBytes(): int
    {
        return $this->getSharedConfig()->getClientMetadataMaxBytes();
    }

    /**
     * Specification:
     * - Returns the maximum size in bytes of a Dynamic Client Registration (DCR) request body, the same as of a Client ID Metadata Document (CIMD).
     *
     * @api
     */
    public function getDynamicClientRegistrationRequestMaxBytes(): int
    {
        return $this->getSharedConfig()->getClientMetadataMaxBytes();
    }

    /**
     * Specification:
     * - Returns the lower bound in seconds of the cache lifetime of a fetched Client ID Metadata Document (CIMD), which also applies to `no-store`.
     *
     * @api
     */
    public function getClientIdMetadataDocumentCacheMinSeconds(): int
    {
        return static::CLIENT_ID_METADATA_DOCUMENT_CACHE_MIN_SECONDS;
    }

    /**
     * Specification:
     * - Returns the upper bound in seconds of the cache lifetime of a fetched Client ID Metadata Document (CIMD).
     *
     * @api
     */
    public function getClientIdMetadataDocumentCacheMaxSeconds(): int
    {
        return static::CLIENT_ID_METADATA_DOCUMENT_CACHE_MAX_SECONDS;
    }

    /**
     * Specification:
     * - Returns the cache lifetime in seconds of a fetched Client ID Metadata Document (CIMD) that sends no cache headers.
     *
     * @api
     */
    public function getClientIdMetadataDocumentCacheDefaultSeconds(): int
    {
        return static::CLIENT_ID_METADATA_DOCUMENT_CACHE_DEFAULT_SECONDS;
    }

    /**
     * Specification:
     * - Returns the scope ceiling a client created from a Client ID Metadata Document (CIMD) gets unless the map names its URL.
     * - Returns an empty list by default: such a client can be granted no scope until the shop sets a ceiling.
     *
     * @api
     *
     * @return list<string>
     */
    public function getClientIdMetadataDocumentDefaultScopeCeiling(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the scope ceilings of known Client ID Metadata Document (CIMD) clients, keyed by the `client_id` URL.
     * - Applies when the client row is created.
     *
     * @api
     *
     * @return array<string, list<string>>
     */
    public function getClientIdMetadataDocumentScopeCeilingMap(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the scope ceiling a client created by Dynamic Client Registration (DCR) gets unless the map names its `software_id`.
     * - Returns an empty list by default: such a client can be granted no scope until the shop sets a ceiling.
     *
     * @api
     *
     * @return list<string>
     */
    public function getDynamicClientRegistrationDefaultScopeCeiling(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns the scope ceilings of known Dynamic Client Registration (DCR) clients, keyed by `software_id`.
     * - Applies when the client row is created.
     * - A `software_id` is asserted by the registering client itself, so this map is a convenience, not a trust signal.
     *
     * @api
     *
     * @return array<string, list<string>>
     */
    public function getDynamicClientRegistrationScopeCeilingMap(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns how Dynamic Client Registration (DCR) restricts redirect URIs: `DYNAMIC_CLIENT_REGISTRATION_MODE_ALLOWLIST` accepts only hosts of the redirect host allowlist, `DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN` accepts any valid redirect URI.
     *
     * @api
     */
    public function getDynamicClientRegistrationMode(): string
    {
        return SharedAgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_ALLOWLIST;
    }

    /**
     * Specification:
     * - Returns the redirect URI hosts that Dynamic Client Registration (DCR) accepts in allowlist mode; a host matches exactly.
     * - An empty list accepts no host in allowlist mode.
     *
     * @api
     *
     * @return list<string>
     */
    public function getDynamicClientRegistrationRedirectHostAllowlist(): array
    {
        return $this->getStringList(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_REDIRECT_HOST_ALLOWLIST);
    }

    /**
     * Specification:
     * - Returns the seconds a failed Client ID Metadata Document (CIMD) fetch keeps the client ID refused without fetching again; `0` switches the negative cache off.
     *
     * @api
     */
    public function getClientIdMetadataDocumentFailureCacheTtlSeconds(): int
    {
        return max(0, (int)$this->get(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_FAILURE_CACHE_TTL_SECONDS, static::CLIENT_ID_METADATA_DOCUMENT_FAILURE_CACHE_TTL_DEFAULT_SECONDS));
    }

    /**
     * Specification:
     * - Returns the hosts a Client ID Metadata Document (CIMD) URL may have; a host matches exactly.
     * - An empty list allows every host.
     *
     * @api
     *
     * @return list<string>
     */
    public function getClientIdMetadataDocumentHostAllowlist(): array
    {
        return $this->getStringList(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST);
    }

    /**
     * Specification:
     * - Returns the `software_id` values that are refused even when no client of that family exists yet; a value matches exactly.
     *
     * @api
     *
     * @return list<string>
     */
    public function getClientDenylistSoftwareIds(): array
    {
        return $this->getStringList(AgenticCommerceConstants::CLIENT_DENYLIST_SOFTWARE_IDS);
    }

    /**
     * Specification:
     * - Returns the hosts of client IDs and redirect URIs that are refused even when no client of that family exists yet; a host matches exactly.
     *
     * @api
     *
     * @return list<string>
     */
    public function getClientDenylistHosts(): array
    {
        return $this->getStringList(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS);
    }

    /**
     * Accepts a list or a comma-separated string, as an environment variable gives it; trims every value and leaves out empty ones.
     *
     * @return list<string>
     */
    protected function getStringList(string $key): array
    {
        $values = $this->get($key, []);

        if (is_string($values)) {
            $values = explode(static::LIST_SEPARATOR, $values);
        }

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => trim((string)$value), (array)$values),
            static fn (string $value): bool => $value !== '',
        ));
    }
}
