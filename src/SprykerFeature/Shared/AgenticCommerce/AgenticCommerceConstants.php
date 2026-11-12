<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Shared\AgenticCommerce;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface AgenticCommerceConstants
{
    /**
     * Specification:
     * - OAuth issuer identifier of the region, the Glue storefront base URL (e.g. `https://glue.shop.example`).
     * - Must be byte-identical wherever it appears: authorization server metadata, the `iss` response parameter and the delegated access token `iss` claim.
     * - When empty, the delegated access protocol endpoints answer `404`.
     *
     * @api
     */
    public const string ISSUER = 'AGENTIC_COMMERCE:ISSUER';

    /**
     * Specification:
     * - Absolute URL of the commerce tools resource on the Glue storefront host, e.g. `https://glue.shop.example/mcp`.
     * - When empty, the commerce tools resource is not declared.
     *
     * @api
     */
    public const string MCP_RESOURCE_URI = 'AGENTIC_COMMERCE:MCP_RESOURCE_URI';

    /**
     * Specification:
     * - Absolute URL of the authorization endpoint on the storefront (Yves) host, e.g. `https://shop.example/connect/authorize`.
     *
     * @api
     */
    public const string AUTHORIZATION_ENDPOINT = 'AGENTIC_COMMERCE:AUTHORIZATION_ENDPOINT';

    /**
     * Specification:
     * - Switches the acceptance of a Client ID Metadata Document (CIMD) URL as `client_id` on or off.
     * - Defaults to `false`.
     *
     * @api
     */
    public const string CLIENT_ID_METADATA_DOCUMENT_ENABLED = 'AGENTIC_COMMERCE:CLIENT_ID_METADATA_DOCUMENT_ENABLED';

    /**
     * Specification:
     * - Switches the RFC 7591 Dynamic Client Registration (DCR) endpoint on or off.
     * - Defaults to `false`.
     *
     * @api
     */
    public const string DYNAMIC_CLIENT_REGISTRATION_ENABLED = 'AGENTIC_COMMERCE:DYNAMIC_CLIENT_REGISTRATION_ENABLED';

    /**
     * Specification:
     * - Switches on recording the IP address and user agent of the request in consent events.
     * - Defaults to `false`; housekeeping clears both after `getConsentEventClientDataRetentionDays()`.
     *
     * @api
     */
    public const string CONSENT_EVENT_CLIENT_DATA_CAPTURED = 'AGENTIC_COMMERCE:CONSENT_EVENT_CLIENT_DATA_CAPTURED';

    /**
     * Specification:
     * - Private key contents (PEM, Privacy-Enhanced Mail) used to sign delegated access tokens.
     * - Must differ from the storefront OAuth key pair, so storefront validation rejects delegated access tokens by construction.
     *
     * @api
     */
    public const string PRIVATE_KEY = 'AGENTIC_COMMERCE:PRIVATE_KEY';

    /**
     * Specification:
     * - Public key contents (PEM, Privacy-Enhanced Mail) used to verify delegated access tokens.
     * - Must never be added to the storefront OAuth public key configuration.
     *
     * @api
     */
    public const string PUBLIC_KEY = 'AGENTIC_COMMERCE:PUBLIC_KEY';

    /**
     * Specification:
     * - Base64-encoded key that encrypts delegated access refresh tokens.
     * - Must differ from `\Spryker\Shared\Oauth\OauthConstants::ENCRYPTION_KEY`, so the storefront refresh grant can never redeem a delegated access refresh token and the other way round.
     *
     * @api
     */
    public const string REFRESH_TOKEN_ENCRYPTION_KEY = 'AGENTIC_COMMERCE:REFRESH_TOKEN_ENCRYPTION_KEY';

    /**
     * Specification:
     * - List of the action scopes a client can be granted, in the order the consent screen lists them.
     * - Every scope has the shape `<resource>:<action>`, lower-case words joined by hyphens (e.g. `catalog:read`).
     *
     * @api
     */
    public const string SUPPORTED_SCOPES = 'AGENTIC_COMMERCE:SUPPORTED_SCOPES';

    /**
     * Specification:
     * - Scheme (e.g. `tcp`) of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_SCHEME` value when not set.
     *
     * @api
     */
    public const string REDIS_SCHEME = 'AGENTIC_COMMERCE:REDIS_SCHEME';

    /**
     * Specification:
     * - Host of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_HOST` value when not set.
     *
     * @api
     */
    public const string REDIS_HOST = 'AGENTIC_COMMERCE:REDIS_HOST';

    /**
     * Specification:
     * - Port of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PORT` value when not set.
     *
     * @api
     */
    public const string REDIS_PORT = 'AGENTIC_COMMERCE:REDIS_PORT';

    /**
     * Specification:
     * - User name of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_USER` value when not set.
     *
     * @api
     */
    public const string REDIS_USER = 'AGENTIC_COMMERCE:REDIS_USER';

    /**
     * Specification:
     * - Password of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PASSWORD` value when not set.
     *
     * @api
     */
    public const string REDIS_PASSWORD = 'AGENTIC_COMMERCE:REDIS_PASSWORD';

    /**
     * Specification:
     * - Database index of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_DATABASE` value when not set.
     *
     * @api
     */
    public const string REDIS_DATABASE = 'AGENTIC_COMMERCE:REDIS_DATABASE';

    /**
     * Specification:
     * - Data source names for a clustered or replicated setup of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_DATA_SOURCE_NAMES` value when not set.
     *
     * @api
     */
    public const string REDIS_DATA_SOURCE_NAMES = 'AGENTIC_COMMERCE:REDIS_DATA_SOURCE_NAMES';

    /**
     * Specification:
     * - Client options of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_CONNECTION_OPTIONS` value when not set.
     *
     * @api
     */
    public const string REDIS_CONNECTION_OPTIONS = 'AGENTIC_COMMERCE:REDIS_CONNECTION_OPTIONS';

    /**
     * Specification:
     * - Whether the connection is persistent of the Redis connection that holds the delegated access runtime state.
     * - Falls back to the matching `\Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PERSISTENT_CONNECTION` value when not set.
     *
     * @api
     */
    public const string REDIS_PERSISTENT_CONNECTION = 'AGENTIC_COMMERCE:REDIS_PERSISTENT_CONNECTION';

    /**
     * Specification:
     * - Seconds the Glue firewall keeps a valid consent check of Zed in the delegated access Redis.
     * - `0` switches the consent cache off; every call then asks Zed.
     * - Defaults to `60`.
     *
     * @api
     */
    public const string CONSENT_CACHE_TTL_SECONDS = 'AGENTIC_COMMERCE:CONSENT_CACHE_TTL_SECONDS';

    /**
     * Specification:
     * - Seconds a revocation keeps the cache entry of a consent blocked, so no request that read the consent before the revocation can fill it again.
     * - Never shorter than `CONSENT_CACHE_TTL_SECONDS`.
     * - Defaults to `120`.
     *
     * @api
     */
    public const string CONSENT_CACHE_TOMBSTONE_TTL_SECONDS = 'AGENTIC_COMMERCE:CONSENT_CACHE_TOMBSTONE_TTL_SECONDS';

    /**
     * Specification:
     * - Switches the delegated access rate limits on: registration, token, authorize and metadata fetch requests are counted and blocked per SecurityBlocker type.
     * - While off, no request is counted or blocked and SecurityBlocker is not called.
     * - Defaults to `false`.
     *
     * @api
     */
    public const string RATE_LIMIT_ENABLED = 'AGENTIC_COMMERCE:RATE_LIMIT_ENABLED';

    /**
     * Specification:
     * - Seconds a failed Client ID Metadata Document (CIMD) fetch keeps the client ID refused without fetching again.
     * - `0` switches the negative cache off.
     * - Defaults to `300`.
     *
     * @api
     */
    public const string CLIENT_ID_METADATA_DOCUMENT_FAILURE_CACHE_TTL_SECONDS = 'AGENTIC_COMMERCE:CLIENT_ID_METADATA_DOCUMENT_FAILURE_CACHE_TTL_SECONDS';

    /**
     * Specification:
     * - Exact hosts a Client ID Metadata Document (CIMD) URL may have; a client ID on another host is refused before any fetch.
     * - An empty list allows every host.
     * - Defaults to an empty list.
     *
     * @api
     */
    public const string CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST = 'AGENTIC_COMMERCE:CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST';

    /**
     * Specification:
     * - Exact `software_id` values whose registrations are refused even when no client of that family exists yet.
     * - Defaults to an empty list.
     *
     * @api
     */
    public const string CLIENT_DENYLIST_SOFTWARE_IDS = 'AGENTIC_COMMERCE:CLIENT_DENYLIST_SOFTWARE_IDS';

    /**
     * Specification:
     * - Exact hosts of client IDs and redirect URIs that are refused even when no client of that family exists yet.
     * - Defaults to an empty list.
     *
     * @api
     */
    public const string CLIENT_DENYLIST_HOSTS = 'AGENTIC_COMMERCE:CLIENT_DENYLIST_HOSTS';
}
