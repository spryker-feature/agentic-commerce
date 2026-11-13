<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce;

use Generated\Shared\Transfer\RedisConfigurationTransfer;
use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Client\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;

/**
 * @method \SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig getSharedConfig()
 */
class AgenticCommerceConfig extends AbstractBundleConfig
{
    protected const string REDIS_CONNECTION_KEY = 'AGENTIC_COMMERCE_REDIS';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_SCHEME
     */
    protected const string FALLBACK_REDIS_SCHEME = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_SCHEME';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_HOST
     */
    protected const string FALLBACK_REDIS_HOST = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_HOST';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PORT
     */
    protected const string FALLBACK_REDIS_PORT = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_PORT';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_USER
     */
    protected const string FALLBACK_REDIS_USER = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_USER';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_DATABASE
     */
    protected const string FALLBACK_REDIS_DATABASE = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_DATABASE';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PASSWORD
     */
    protected const string FALLBACK_REDIS_PASSWORD = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_PASSWORD';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_PERSISTENT_CONNECTION
     */
    protected const string FALLBACK_REDIS_PERSISTENT_CONNECTION = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_PERSISTENT_CONNECTION';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_DATA_SOURCE_NAMES
     */
    protected const string FALLBACK_REDIS_DATA_SOURCE_NAMES = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_DATA_SOURCE_NAMES';

    /**
     * @uses \Spryker\Shared\SecurityBlocker\SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_CONNECTION_OPTIONS
     */
    protected const string FALLBACK_REDIS_CONNECTION_OPTIONS = 'SECURITY_BLOCKER_REDIS:SECURITY_BLOCKER_REDIS_CONNECTION_OPTIONS';

    protected const int TOKEN_TIME_LEEWAY_IN_SECONDS = 5;

    /**
     * @api
     */
    public const string RATE_LIMIT_NUMBER_OF_ATTEMPTS = 'numberOfAttempts';

    /**
     * @api
     */
    public const string RATE_LIMIT_TTL_IN_SECONDS = 'ttlInSeconds';

    /**
     * @api
     */
    public const string RATE_LIMIT_BLOCK_FOR_IN_SECONDS = 'blockForInSeconds';

    protected const array RATE_LIMITS = [
        SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_REGISTER => [
            self::RATE_LIMIT_NUMBER_OF_ATTEMPTS => 10,
            self::RATE_LIMIT_TTL_IN_SECONDS => 3600,
            self::RATE_LIMIT_BLOCK_FOR_IN_SECONDS => 3600,
        ],
        SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN => [
            self::RATE_LIMIT_NUMBER_OF_ATTEMPTS => 60,
            self::RATE_LIMIT_TTL_IN_SECONDS => 60,
            self::RATE_LIMIT_BLOCK_FOR_IN_SECONDS => 300,
        ],
        SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE => [
            self::RATE_LIMIT_NUMBER_OF_ATTEMPTS => 30,
            self::RATE_LIMIT_TTL_IN_SECONDS => 600,
            self::RATE_LIMIT_BLOCK_FOR_IN_SECONDS => 900,
        ],
        SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_AUTHORIZE => [
            self::RATE_LIMIT_NUMBER_OF_ATTEMPTS => 30,
            self::RATE_LIMIT_TTL_IN_SECONDS => 60,
            self::RATE_LIMIT_BLOCK_FOR_IN_SECONDS => 300,
        ],
        SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_METADATA_FETCH => [
            self::RATE_LIMIT_NUMBER_OF_ATTEMPTS => 30,
            self::RATE_LIMIT_TTL_IN_SECONDS => 600,
            self::RATE_LIMIT_BLOCK_FOR_IN_SECONDS => 900,
        ],
    ];

    /**
     * Specification:
     * - Returns the issuer every delegated access token must carry as its issuer claim.
     * - Returns an empty string when delegated access is not configured.
     *
     * @api
     */
    public function getIssuer(): string
    {
        return $this->getSharedConfig()->getIssuer();
    }

    /**
     * Specification:
     * - Returns the resource URI every delegated access token must name in its audience claim (RFC 8707, Resource Indicators for OAuth 2.0).
     * - Returns an empty string when delegated access is not configured.
     *
     * @api
     */
    public function getResourceUri(): string
    {
        return $this->getSharedConfig()->getResourceUri();
    }

    /**
     * Specification:
     * - Returns the PEM (Privacy-Enhanced Mail) encoded public key that verifies delegated access tokens.
     * - Returns an empty string when delegated access is not configured.
     *
     * @api
     */
    public function getOauthPublicKey(): string
    {
        return $this->getSharedConfig()->getOauthPublicKey();
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
        return $this->getSharedConfig()->getConsentDurationsInDays();
    }

    /**
     * Specification:
     * - Returns the seconds of clock difference allowed when the time claims of a delegated access token are checked.
     *
     * @api
     */
    public function getTokenTimeLeewayInSeconds(): int
    {
        return static::TOKEN_TIME_LEEWAY_IN_SECONDS;
    }

    /**
     * Specification:
     * - Returns the key of the Redis connection that holds the delegated access runtime state.
     *
     * @api
     */
    public function getRedisConnectionKey(): string
    {
        return static::REDIS_CONNECTION_KEY;
    }

    /**
     * Specification:
     * - Returns the configuration of the Redis connection that holds the delegated access runtime state.
     * - Reads the `AgenticCommerceConstants::REDIS_*` values, falling back to the matching `SecurityBlockerConstants::SECURITY_BLOCKER_REDIS_*` values.
     *
     * @api
     */
    public function getRedisConnectionConfiguration(): RedisConfigurationTransfer
    {
        return (new RedisConfigurationTransfer())
            ->setDataSourceNames($this->getConnectionValue(AgenticCommerceConstants::REDIS_DATA_SOURCE_NAMES, static::FALLBACK_REDIS_DATA_SOURCE_NAMES, []))
            ->setConnectionCredentials($this->getRedisConnectionCredentials())
            ->setClientOptions($this->getConnectionValue(AgenticCommerceConstants::REDIS_CONNECTION_OPTIONS, static::FALLBACK_REDIS_CONNECTION_OPTIONS, []));
    }

    /**
     * Specification:
     * - Returns whether the delegated access rate limits are switched on.
     * - Reads `AgenticCommerceConstants::RATE_LIMIT_ENABLED`; defaults to `false`.
     *
     * @api
     */
    public function isRateLimitEnabled(): bool
    {
        return (bool)$this->get(AgenticCommerceConstants::RATE_LIMIT_ENABLED, false);
    }

    /**
     * Specification:
     * - Returns the rate limit of every delegated access SecurityBlocker type, keyed by type.
     * - Each limit holds `numberOfAttempts`, `ttlInSeconds` and `blockForInSeconds`.
     *
     * @api
     *
     * @return array<string, array{numberOfAttempts: int, ttlInSeconds: int, blockForInSeconds: int}>
     */
    public function getRateLimits(): array
    {
        return static::RATE_LIMITS;
    }

    /**
     * Specification:
     * - Returns the seconds a valid consent check of Zed stays in the consent cache; `0` switches the cache off.
     *
     * @api
     */
    public function getConsentCacheTtlSeconds(): int
    {
        return $this->getSharedConfig()->getConsentCacheTtlSeconds();
    }

    /**
     * Specification:
     * - Returns the seconds a revocation keeps the cache entry of a consent blocked; never less than `getConsentCacheTtlSeconds()`.
     *
     * @api
     */
    public function getConsentCacheTombstoneTtlSeconds(): int
    {
        return $this->getSharedConfig()->getConsentCacheTombstoneTtlSeconds();
    }

    /**
     * Specification:
     * - Returns the minimum time in seconds between two writes of a consent's `last_used_at`; a cached consent goes to Zed once per interval.
     *
     * @api
     */
    public function getLastUsedAtWriteIntervalInSeconds(): int
    {
        return $this->getSharedConfig()->getLastUsedAtWriteIntervalInSeconds();
    }

    protected function getRedisConnectionCredentials(): RedisCredentialsTransfer
    {
        return (new RedisCredentialsTransfer())
            ->setScheme($this->getConnectionValue(AgenticCommerceConstants::REDIS_SCHEME, static::FALLBACK_REDIS_SCHEME))
            ->setHost($this->getConnectionValue(AgenticCommerceConstants::REDIS_HOST, static::FALLBACK_REDIS_HOST))
            ->setPort($this->getConnectionValue(AgenticCommerceConstants::REDIS_PORT, static::FALLBACK_REDIS_PORT))
            ->setUsername($this->getConnectionValue(AgenticCommerceConstants::REDIS_USER, static::FALLBACK_REDIS_USER, false))
            ->setDatabase($this->getConnectionValue(AgenticCommerceConstants::REDIS_DATABASE, static::FALLBACK_REDIS_DATABASE))
            ->setPassword($this->getConnectionValue(AgenticCommerceConstants::REDIS_PASSWORD, static::FALLBACK_REDIS_PASSWORD, false))
            ->setIsPersistent($this->getConnectionValue(AgenticCommerceConstants::REDIS_PERSISTENT_CONNECTION, static::FALLBACK_REDIS_PERSISTENT_CONNECTION, false));
    }

    protected function getConnectionValue(string $key, string $fallbackKey, mixed $default = null): mixed
    {
        if ($this->getConfig()::hasValue($key)) {
            return $this->get($key);
        }

        return $this->get($fallbackKey, $default);
    }
}
