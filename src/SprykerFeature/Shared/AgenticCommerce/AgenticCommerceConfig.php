<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Shared\AgenticCommerce;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Shared\Kernel\AbstractSharedConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;

class AgenticCommerceConfig extends AbstractSharedConfig
{
    public const string CONSENT_STATUS_ACTIVE = 'active';

    public const string CONSENT_STATUS_REVOKED = 'revoked';

    public const string CONSENT_STATUS_EXPIRED = 'expired';

    public const string CONSENT_REVOKED_BY_CUSTOMER = 'customer';

    public const string CONSENT_REVOKED_BY_CLIENT = 'client';

    public const string CONSENT_REVOKED_BY_COMPANY_ADMIN = 'company_admin';

    public const string CONSENT_REVOKED_BY_BACK_OFFICE = 'back_office';

    public const string CONSENT_REVOKED_BY_SYSTEM_REUSE_DETECTED = 'system_reuse_detected';

    public const string CONSENT_REVOKED_BY_SYSTEM_CODE_REPLAY = 'system_code_replay';

    public const string CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE = 'system_principal_inactive';

    public const string CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED = 'system_client_blocked';

    /**
     * The actor of the `granted` and `re_consented` events. A revocation event's actor is its `revoked_by` value.
     */
    public const string CONSENT_ACTOR_CUSTOMER = 'customer';

    public const string CONSENT_ACTOR_SYSTEM_SCOPE_FILTER = 'system_scope_filter';

    /**
     * The actor of the `expired` event, written by the housekeeping command.
     */
    public const string CONSENT_ACTOR_SYSTEM = 'system';

    public const string CONSENT_EVENT_GRANTED = 'granted';

    public const string CONSENT_EVENT_RE_CONSENTED = 're_consented';

    public const string CONSENT_EVENT_SCOPES_NARROWED = 'scopes_narrowed';

    public const string CONSENT_EVENT_REVOKED = 'revoked';

    public const string CONSENT_EVENT_REUSE_DETECTED = 'reuse_detected';

    public const string CONSENT_EVENT_CODE_REPLAYED = 'code_replayed';

    public const string CONSENT_EVENT_EXPIRED = 'expired';

    /**
     * The events that set the terms of a consent; each records the scopes and the constraints in force after it.
     *
     * @var list<string>
     */
    public const array CONSENT_TERMS_EVENTS = [self::CONSENT_EVENT_GRANTED, self::CONSENT_EVENT_RE_CONSENTED, self::CONSENT_EVENT_SCOPES_NARROWED];

    public const string PRINCIPAL_TYPE_CUSTOMER = 'customer';

    public const string PRINCIPAL_TYPE_COMPANY_USER = 'company_user';

    public const string CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT = 'order_total_limit';

    public const string CONSTRAINT_TYPE_ORDER_BUDGET = 'order_budget';

    public const string CONSTRAINT_TYPE_MERCHANT_ALLOWLIST = 'merchant_allowlist';

    public const string CONSTRAINT_TYPE_CATEGORY_ALLOWLIST = 'category_allowlist';

    /**
     * The key of the amount in cents in an `order_total_limit` constraint value.
     */
    public const string CONSTRAINT_VALUE_KEY_AMOUNT = 'amount';

    /**
     * The key of the ISO currency code in an `order_total_limit` constraint value.
     */
    public const string CONSTRAINT_VALUE_KEY_CURRENCY = 'currency';

    /**
     * The key of the budget period in an `order_budget` constraint value: `day`, `week` or `month`.
     */
    public const string CONSTRAINT_VALUE_KEY_PERIOD = 'period';

    /**
     * The key of the list of merchant references in a `merchant_allowlist` constraint value.
     */
    public const string CONSTRAINT_VALUE_KEY_MERCHANT_REFERENCES = 'merchantReferences';

    /**
     * The key of the list of category keys in a `category_allowlist` constraint value.
     */
    public const string CONSTRAINT_VALUE_KEY_CATEGORY_KEYS = 'categoryKeys';

    /**
     * An amount in the field currency, submitted in major units and kept in cents.
     *
     * @api
     */
    public const string CONSTRAINT_INPUT_KIND_MONEY = 'money';

    /**
     * An amount as above together with the period it applies to.
     *
     * @api
     */
    public const string CONSTRAINT_INPUT_KIND_MONEY_PERIOD = 'money_period';

    /**
     * Several values of one flat list, ticked as checkboxes.
     *
     * @api
     */
    public const string CONSTRAINT_INPUT_KIND_MULTI_SELECT = 'multi_select';

    /**
     * Several values of a list with parents, ticked as checkboxes and shown nested by the parents.
     *
     * @api
     */
    public const string CONSTRAINT_INPUT_KIND_CATEGORY_TREE = 'category_tree';

    /**
     * @api
     */
    public const array SUPPORTED_CONSTRAINT_INPUT_KINDS = [
        self::CONSTRAINT_INPUT_KIND_MONEY,
        self::CONSTRAINT_INPUT_KIND_MONEY_PERIOD,
        self::CONSTRAINT_INPUT_KIND_MULTI_SELECT,
        self::CONSTRAINT_INPUT_KIND_CATEGORY_TREE,
    ];

    public const string BUDGET_PERIOD_DAY = 'day';

    public const string BUDGET_PERIOD_WEEK = 'week';

    public const string BUDGET_PERIOD_MONTH = 'month';

    public const string ERROR_CONSENT_CONSTRAINT_REQUIRED = 'agentic_commerce.consent.validation.constraint_required';

    /**
     * The customer removed a constraint the client requested through `authorization_details`.
     */
    public const string ERROR_CONSENT_CONSTRAINT_REQUESTED_REMOVED = 'agentic_commerce.consent.constraint.requested_removed';

    /**
     * The customer submitted a constraint that is wider than the one the client requested through `authorization_details`.
     */
    public const string ERROR_CONSENT_CONSTRAINT_WIDER_THAN_REQUESTED = 'agentic_commerce.consent.constraint.wider_than_requested';

    /**
     * RFC 9396 (OAuth 2.0 Rich Authorization Requests), section 5: the `authorization_details` parameter is invalid.
     */
    public const string ERROR_INVALID_AUTHORIZATION_DETAILS = 'invalid_authorization_details';

    /**
     * RFC 9396, section 2: the prefix of the `type` of an authorization details object; each constraint plugin appends its own suffix.
     */
    public const string AUTHORIZATION_DETAILS_TYPE_BASE = 'https://spryker.com/oauth/authorization-details/';

    public const int AUTHORIZATION_DETAILS_MAX_OBJECTS = 10;

    public const int AUTHORIZATION_DETAILS_MAX_BYTES = 4096;

    /**
     * The key under which the Client sends the consent of a delegated request with every Zed request.
     */
    public const string ZED_REQUEST_META_DATA_KEY = 'agentic_commerce';

    /**
     * The key under which the Client sends the company user of the storefront session with every Zed request, so a cascade revocation knows the acting company admin.
     */
    public const string ZED_REQUEST_ACTING_COMPANY_USER_META_DATA_KEY = 'agentic_commerce_acting_company_user';

    public const string RESOURCE_NAME_MCP = 'mcp';

    public const string SCOPE_CATALOG_READ = 'catalog:read';

    public const string SCOPE_COMPANY_READ = 'company:read';

    public const string SCOPE_CART_READ = 'cart:read';

    public const string SCOPE_CART_WRITE = 'cart:write';

    public const string SCOPE_ORDER_PLACE = 'order:place';

    public const string RESULT_ALLOWED = 'allowed';

    public const string RESULT_REQUIRES_APPROVAL = 'requires_approval';

    public const string RESULT_REFUSED = 'refused';

    public const string REASON_CONSENT_INACTIVE = 'consent_inactive';

    public const string REASON_CONSENT_MISMATCH = 'consent_mismatch';

    public const string REASON_SCOPE_MISSING = 'scope_missing';

    public const string REASON_NO_LIMIT_FOR_CUSTOMER = 'no_limit_for_customer';

    public const string REASON_LIMIT_EXCEEDED = 'limit_exceeded';

    public const string REASON_BUDGET_EXCEEDED = 'budget_exceeded';

    public const string REASON_MERCHANT_NOT_ALLOWED = 'merchant_not_allowed';

    public const string REASON_CATEGORY_NOT_ALLOWED = 'category_not_allowed';

    public const string REASON_DELIVERY_ADDRESS_NOT_ALLOWED = 'delivery_address_not_allowed';

    public const string CHECKOUT_ERROR_PREFIX = 'agentic_commerce.checkout.';

    public const string APPROVAL_STATUS_NONE = 'none';

    public const string APPROVAL_STATUS_WAITING = 'waiting';

    public const string APPROVAL_STATUS_DECLINED = 'declined';

    public const string CLIENT_STATUS_ACTIVE = 'active';

    public const string CLIENT_STATUS_BLOCKED = 'blocked';

    /**
     * Prefix of `spy_agentic_commerce_client.blocked_by` for a client blocked with every client of its software id.
     */
    public const string CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID = 'software_id:';

    /**
     * Prefix of `spy_agentic_commerce_client.blocked_by` for a client blocked with every client of a redirect host; the host follows in lower case.
     */
    public const string CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST = 'redirect_host:';

    public const string CLIENT_REGISTRATION_TYPE_PRE_REGISTERED = 'pre_registered';

    public const string CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT = 'client_id_metadata_document';

    public const string CLIENT_REGISTRATION_TYPE_DYNAMIC = 'dynamic';

    public const string CLIENT_TRUST_LEVEL_VERIFIED = 'verified';

    public const string CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED = 'domain_verified';

    public const string CLIENT_TRUST_LEVEL_UNVERIFIED = 'unverified';

    public const string DYNAMIC_CLIENT_REGISTRATION_MODE_ALLOWLIST = 'allowlist';

    public const string DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN = 'open';

    public const string TOKEN_ENDPOINT_AUTH_METHOD_NONE = 'none';

    public const string CODE_CHALLENGE_METHOD_S256 = 'S256';

    public const string RESPONSE_TYPE_CODE = 'code';

    public const string ERROR_INVALID_CLIENT = 'invalid_client';

    public const string ERROR_INVALID_REQUEST = 'invalid_request';

    public const string ERROR_INVALID_TOKEN = 'invalid_token';

    public const string ERROR_SERVER_ERROR = 'server_error';

    public const string ERROR_ACCESS_DENIED = 'access_denied';

    public const string ERROR_INVALID_SCOPE = 'invalid_scope';

    public const string ERROR_UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    public const string ERROR_INVALID_REDIRECT_URI = 'invalid_redirect_uri';

    public const string ERROR_INVALID_CLIENT_METADATA = 'invalid_client_metadata';

    public const string SECURITY_BLOCKER_TYPE_REGISTER = 'agentic_commerce_register';

    public const string SECURITY_BLOCKER_TYPE_TOKEN = 'agentic_commerce_token';

    public const string SECURITY_BLOCKER_TYPE_TOKEN_FAILURE = 'agentic_commerce_token_failure';

    public const string SECURITY_BLOCKER_TYPE_AUTHORIZE = 'agentic_commerce_authorize';

    public const string SECURITY_BLOCKER_TYPE_METADATA_FETCH = 'agentic_commerce_metadata_fetch';

    public const string CLIENT_RESOLVE_FAILURE_CLIENT_ID_METADATA_DOCUMENT_DISABLED = 'client_id_metadata_document_disabled';

    public const string CLIENT_RESOLVE_FAILURE_INVALID_URL = 'client_id_metadata_document_invalid_url';

    public const string CLIENT_RESOLVE_FAILURE_ADDRESS_REFUSED = 'client_id_metadata_document_address_refused';

    public const string CLIENT_RESOLVE_FAILURE_FETCH_FAILED = 'client_id_metadata_document_fetch_failed';

    public const string CLIENT_RESOLVE_FAILURE_INVALID_DOCUMENT = 'client_id_metadata_document_invalid_document';

    public const string CLIENT_RESOLVE_FAILURE_CLIENT_ID_MISMATCH = 'client_id_metadata_document_client_id_mismatch';

    /**
     * Another OAuth client, outside Agentic Commerce or under a client ID that differs only in letter case, already holds the client ID.
     */
    public const string CLIENT_RESOLVE_FAILURE_CLIENT_ID_TAKEN = 'client_id_metadata_document_client_id_taken';

    /**
     * A redirect URI of the document is on a host blocked as a family (`--redirect-host`).
     */
    public const string CLIENT_RESOLVE_FAILURE_REDIRECT_HOST_BLOCKED = 'client_id_metadata_document_redirect_host_blocked';

    /**
     * The document names a `software_id` that is on the denylist or blocked as a family.
     */
    public const string CLIENT_RESOLVE_FAILURE_SOFTWARE_ID_BLOCKED = 'client_id_metadata_document_software_id_blocked';

    public const string CLIENT_RESOLVE_FAILURE_HOST_NOT_ALLOWED = 'client_id_metadata_document_host_not_allowed';

    public const string CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED = 'client_id_metadata_document_host_denylisted';

    /**
     * An earlier fetch of the document failed and the negative cache still holds it.
     */
    public const string CLIENT_RESOLVE_FAILURE_RECENTLY_FAILED = 'client_id_metadata_document_recently_failed';

    public const string CLIENT_RESOLVE_FAILURE_FETCH_RATE_LIMITED = 'client_id_metadata_document_fetch_rate_limited';

    /**
     * RFC 8707 (Resource Indicators for OAuth 2.0), section 2: the `resource` parameter is missing or names another resource.
     */
    public const string ERROR_INVALID_TARGET = 'invalid_target';

    /**
     * RFC 8252 loopback hosts: the only hosts a plain `http` redirect URI may use.
     */
    public const array LOOPBACK_REDIRECT_HOSTS = ['127.0.0.1', 'localhost', '[::1]'];

    public const string REQUEST_APPLICATION_AGENTIC_COMMERCE = 'AGENTIC_COMMERCE';

    public const string REDIS_KEY_PREFIX = 'agentic_commerce';

    public const string REDIS_KEY_SEPARATOR = ':';

    /**
     * @uses \Spryker\Zed\OauthCodeFlow\OauthCodeFlowConfig::GRANT_TYPE_AUTHORIZATION_CODE
     */
    public const string GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';

    /**
     * @uses \Spryker\Zed\Oauth\OauthConfig::GRANT_TYPE_REFRESH_TOKEN
     */
    public const string GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';

    public const string CLAIM_CONSENT_UUID = 'delegated_access_consent';

    public const string CLAIM_CLIENT_ID = 'client_id';

    public const string CLAIM_SCOPES = 'scopes';

    public const string CLAIM_STORE = 'store';

    public const string CLAIM_AUTHORIZATION_DETAILS = 'authorization_details';

    public const string AUTHORIZATION_CODE_PAYLOAD_KEY_RESOURCE = 'resource';

    /**
     * @uses \Spryker\Zed\OauthCustomerConnector\OauthCustomerConnectorConfig::SCOPE_CUSTOMER
     */
    public const string SCOPE_IDENTITY_CUSTOMER = 'customer';

    /**
     * @uses \Spryker\Zed\OauthCompanyUser\OauthCompanyUserConfig::SCOPE_COMPANY_USER
     */
    public const string SCOPE_IDENTITY_COMPANY_USER = 'company_user';

    /**
     * A refresh token identifier is `<consent uuid><separator><random part>`.
     */
    public const string REFRESH_TOKEN_IDENTIFIER_SEPARATOR = '.';

    /**
     * An action scope is `<resource>:<action>`, each part lower-case words joined by hyphens.
     */
    public const string DELEGATED_SCOPE_PATTERN = '/^[a-z]+(-[a-z]+)*:[a-z]+(-[a-z]+)*$/';

    public const string ROLE_AGENTIC_COMMERCE = 'ROLE_AGENTIC_COMMERCE';

    public const string ROLE_SCOPE_PREFIX = 'ROLE_SCOPE_';

    /**
     * API Platform operations of a protected resource carry this key with the value `true` in their `extraProperties`; the delegated access firewall then takes them wherever their path lies.
     */
    public const string EXTRA_PROPERTY_AGENTIC_COMMERCE = 'agenticCommerce';

    /**
     * The protocol endpoints a client calls without a delegated access token, without delimiters, as a Symfony firewall `pattern` takes it.
     */
    public const string PUBLIC_PROTOCOL_PATH_PATTERN = '^/(oauth/(token|revoke|register)$|\.well-known/oauth-)';

    /**
     * The role `\Spryker\ApiPlatform\Security\ApiUserProvider` derives from the storefront identity scope `customer`.
     */
    public const string ROLE_CUSTOMER = 'ROLE_CUSTOMER';

    /**
     * The role `\Spryker\ApiPlatform\Security\ApiUserProvider` derives from the storefront identity scope `company_user`.
     */
    public const string ROLE_COMPANY_USER = 'ROLE_COMPANY_USER';

    protected const array CONSENT_DURATIONS_IN_DAYS = [30, 7, 1];

    protected const int CLIENT_METADATA_MAX_BYTES = 5120;

    protected const int CONSENT_CACHE_TTL_SECONDS = 60;

    protected const int CONSENT_CACHE_TOMBSTONE_TTL_SECONDS = 120;

    /**
     * Redis refuses an expiry of zero seconds.
     */
    protected const int MINIMUM_EXPIRY_SECONDS = 1;

    protected const int LAST_USED_AT_WRITE_INTERVAL_IN_SECONDS = 3600;

    protected const int PROVENANCE_TTL_IN_SECONDS = 86400;

    public const string TOKEN_ENDPOINT_PATH = '/oauth/token';

    public const string REVOCATION_ENDPOINT_PATH = '/oauth/revoke';

    public const string REGISTRATION_ENDPOINT_PATH = '/oauth/register';

    protected const string RESOURCE_PATH = '/agentic-commerce';

    protected const string URL_PATH_SEPARATOR = '/';

    protected const string PROTECTED_RESOURCE_METADATA_PATH = '/.well-known/oauth-protected-resource/agentic-commerce';

    protected const string CART_HANDOFF_PATH = '/agentic-commerce/cart-handoff/';

    /**
     * Specification:
     * - Returns the action scopes a client can be granted, in the order the consent screen lists them.
     * - Reads `AgenticCommerceConstants::SUPPORTED_SCOPES`; returns an empty list when it is not configured.
     * - Throws `AgenticCommerceConfigurationException` for a scope that does not match `DELEGATED_SCOPE_PATTERN`.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return list<string>
     */
    public function getSupportedScopes(): array
    {
        $supportedScopes = [];

        foreach ((array)$this->get(AgenticCommerceConstants::SUPPORTED_SCOPES, []) as $supportedScope) {
            if (!is_string($supportedScope) || preg_match(static::DELEGATED_SCOPE_PATTERN, $supportedScope) !== 1) {
                throw new AgenticCommerceConfigurationException(sprintf(
                    'The supported scope "%s" does not have the shape <resource>:<action>.',
                    is_string($supportedScope) ? $supportedScope : get_debug_type($supportedScope),
                ));
            }

            $supportedScopes[] = $supportedScope;
        }

        return $supportedScopes;
    }

    /**
     * Specification:
     * - Returns whether delegated access is switched on: the issuer, the authorization endpoint and the public key are all configured.
     * - While it is off, the storefront pages and the Glue protocol endpoints answer `404`.
     *
     * @api
     */
    public function isConfigured(): bool
    {
        return $this->getIssuer() !== ''
            && $this->getAuthorizationEndpoint() !== ''
            && $this->getOauthPublicKey() !== '';
    }

    /**
     * Specification:
     * - Returns the consent durations in days a customer can choose on the consent screen, the first one being the default.
     *
     * @api
     *
     * @return list<int>
     */
    public function getConsentDurationsInDays(): array
    {
        return array_values(static::CONSENT_DURATIONS_IN_DAYS);
    }

    /**
     * Specification:
     * - Returns the OAuth issuer identifier of the region's delegated access authorization server (RFC 8414 `issuer`).
     * - Returns an empty string when it is not configured.
     * - Removes trailing slashes, so the issuer and every URL built from it are the same wherever they appear.
     *
     * @api
     */
    public function getIssuer(): string
    {
        return rtrim($this->get(AgenticCommerceConstants::ISSUER, ''), static::URL_PATH_SEPARATOR);
    }

    /**
     * Specification:
     * - Returns the canonical URI of the delegated access resource, `<issuer>/agentic-commerce`; tokens are audience-bound to it (RFC 8707).
     * - Returns an empty string when the issuer is not configured.
     *
     * @api
     */
    public function getResourceUri(): string
    {
        return $this->buildIssuerUrl(static::RESOURCE_PATH);
    }

    /**
     * Specification:
     * - Returns the canonical URI of the commerce tools resource.
     * - Returns an empty string when it is not configured.
     *
     * @api
     */
    public function getMcpResourceUri(): string
    {
        return $this->get(AgenticCommerceConstants::MCP_RESOURCE_URI, '');
    }

    /**
     * Specification:
     * - Returns the URL of the protected resource metadata document (RFC 9728) advertised in `WWW-Authenticate`, `<issuer>/.well-known/oauth-protected-resource/agentic-commerce`.
     * - Returns an empty string when the issuer is not configured.
     *
     * @api
     */
    public function getProtectedResourceMetadataUrl(): string
    {
        return $this->buildIssuerUrl(static::PROTECTED_RESOURCE_METADATA_PATH);
    }

    /**
     * Specification:
     * - Returns the storefront URL where the customer signs in and gives consent to a client.
     *
     * @api
     */
    public function getAuthorizationEndpoint(): string
    {
        return $this->get(AgenticCommerceConstants::AUTHORIZATION_ENDPOINT, '');
    }

    /**
     * Specification:
     * - Returns the URL where a client exchanges an authorization code or a refresh token for an access token, `<issuer>/oauth/token`.
     * - Returns an empty string when the issuer is not configured.
     *
     * @api
     */
    public function getTokenEndpoint(): string
    {
        return $this->buildIssuerUrl(static::TOKEN_ENDPOINT_PATH);
    }

    /**
     * Specification:
     * - Returns the URL where a client revokes a refresh token (RFC 7009), `<issuer>/oauth/revoke`.
     * - Returns an empty string when the issuer is not configured.
     *
     * @api
     */
    public function getRevocationEndpoint(): string
    {
        return $this->buildIssuerUrl(static::REVOCATION_ENDPOINT_PATH);
    }

    /**
     * Specification:
     * - Returns the URL where a client registers itself (RFC 7591), `<issuer>/oauth/register`.
     * - Returns an empty string when the issuer is not configured.
     *
     * @api
     */
    public function getRegistrationEndpoint(): string
    {
        return $this->buildIssuerUrl(static::REGISTRATION_ENDPOINT_PATH);
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
        return (bool)$this->get(AgenticCommerceConstants::CONSENT_EVENT_CLIENT_DATA_CAPTURED, false);
    }

    /**
     * Specification:
     * - Returns whether a Client ID Metadata Document (CIMD) URL is accepted as `client_id`.
     * - Returns `false` unless the project switches it on.
     *
     * @api
     */
    public function isClientIdMetadataDocumentEnabled(): bool
    {
        return (bool)$this->get(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, false);
    }

    /**
     * Specification:
     * - Returns whether the Dynamic Client Registration (DCR) endpoint (RFC 7591) accepts registrations.
     * - Returns `false` unless the project switches it on.
     *
     * @api
     */
    public function isDynamicClientRegistrationEnabled(): bool
    {
        return (bool)$this->get(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, false);
    }

    /**
     * Specification:
     * - Returns the maximum size in bytes of client metadata a client sends: a Client ID Metadata Document (CIMD) body and a Dynamic Client Registration (DCR) request body.
     *
     * @api
     */
    public function getClientMetadataMaxBytes(): int
    {
        return static::CLIENT_METADATA_MAX_BYTES;
    }

    /**
     * Specification:
     * - Returns the PEM (Privacy-Enhanced Mail) encoded public key that verifies delegated access tokens; it never verifies storefront tokens, and the storefront never trusts it.
     *
     * @api
     */
    public function getOauthPublicKey(): string
    {
        return $this->get(AgenticCommerceConstants::PUBLIC_KEY, '');
    }

    /**
     * Specification:
     * - Returns the seconds the Glue firewall keeps a valid consent check of Zed in the delegated access Redis.
     * - Reads `AgenticCommerceConstants::CONSENT_CACHE_TTL_SECONDS`, `60` by default.
     * - Returns `0` while the consent cache is switched off.
     *
     * @api
     */
    public function getConsentCacheTtlSeconds(): int
    {
        return max(0, (int)$this->get(AgenticCommerceConstants::CONSENT_CACHE_TTL_SECONDS, static::CONSENT_CACHE_TTL_SECONDS));
    }

    /**
     * Specification:
     * - Returns the seconds a revocation keeps the cache entry of a consent blocked.
     * - Reads `AgenticCommerceConstants::CONSENT_CACHE_TOMBSTONE_TTL_SECONDS`, `120` by default.
     * - Never returns less than `getConsentCacheTtlSeconds()`, nor less than one second.
     *
     * @api
     */
    public function getConsentCacheTombstoneTtlSeconds(): int
    {
        return max(
            (int)$this->get(AgenticCommerceConstants::CONSENT_CACHE_TOMBSTONE_TTL_SECONDS, static::CONSENT_CACHE_TOMBSTONE_TTL_SECONDS),
            $this->getConsentCacheTtlSeconds(),
            static::MINIMUM_EXPIRY_SECONDS,
        );
    }

    /**
     * Specification:
     * - Returns the minimum time in seconds between two writes of a consent's `last_used_at`.
     * - A consent served from the consent cache goes to Zed once per interval, so the write still happens.
     *
     * @api
     */
    public function getLastUsedAtWriteIntervalInSeconds(): int
    {
        return static::LAST_USED_AT_WRITE_INTERVAL_IN_SECONDS;
    }

    /**
     * Specification:
     * - Returns the seconds the SKUs a tool returned stay known as offered to a consent: the access token lifetime times the refresh window.
     * - A refreshed token of the same consent keeps its provenance within this time.
     *
     * @api
     */
    public function getProvenanceTtlInSeconds(): int
    {
        return static::PROVENANCE_TTL_IN_SECONDS;
    }

    /**
     * Specification:
     * - Returns the storefront URL that redeems a cart hand-off token, `<Yves base URL>/agentic-commerce/cart-handoff/<token>`.
     * - Returns an empty string when the Yves base URL is not configured.
     *
     * @api
     */
    public function getCartHandoffUrl(string $token): string
    {
        $yvesBaseUrl = $this->get(ApplicationConstants::BASE_URL_YVES, '');

        if ($yvesBaseUrl === '') {
            return '';
        }

        return $yvesBaseUrl . static::CART_HANDOFF_PATH . $token;
    }

    protected function buildIssuerUrl(string $path): string
    {
        $issuer = $this->getIssuer();

        if ($issuer === '') {
            return '';
        }

        return $issuer . $path;
    }
}
