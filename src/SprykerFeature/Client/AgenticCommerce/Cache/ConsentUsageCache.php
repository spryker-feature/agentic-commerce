<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Cache;

use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use JsonException;
use Psr\Log\LoggerInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class ConsentUsageCache implements ConsentUsageCacheInterface
{
    protected const string KEY_TYPE_ENTRY = 'consent';

    protected const string KEY_TYPE_LAST_USED_MARKER = 'consent-used';

    protected const string KEY_TYPE_CUSTOMER_INDEX = 'consent-index';

    protected const string VALUE_TOMBSTONE = 'revoked';

    protected const string VALUE_LAST_USED_MARKER = '1';

    protected const string LOG_MESSAGE_READ_FAILED = 'agentic_commerce consent cache could not be read, so Zed decides.';

    protected const string LOG_MESSAGE_INVALIDATION_FAILED = 'agentic_commerce consent cache invalidation failed; the entries expire with the cache TTL.';

    protected const string LOG_MESSAGE_FILL_FAILED = 'agentic_commerce consent cache could not be filled; the next request asks Zed again.';

    public function __construct(
        protected AgenticCommerceRedisWrapperInterface $redisWrapper,
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected LoggerInterface $logger
    ) {
    }

    public function find(string $storeName, string $consentUuid): ?AgenticCommerceConsentUsageResponseTransfer
    {
        try {
            $value = $this->redisWrapper->get($this->buildKey(static::KEY_TYPE_ENTRY, $storeName, $consentUuid));
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error(static::LOG_MESSAGE_READ_FAILED, ['exception' => $exception]);

            return null;
        }

        if ($value === null || $value === static::VALUE_TOMBSTONE) {
            return null;
        }

        try {
            $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->logger->error(static::LOG_MESSAGE_READ_FAILED, ['exception' => $exception]);

            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $agenticCommerceConsentUsageResponseTransfer = (new AgenticCommerceConsentUsageResponseTransfer())->fromArray($data, true);

        return $agenticCommerceConsentUsageResponseTransfer->getIsValid() ? $agenticCommerceConsentUsageResponseTransfer : null;
    }

    public function save(
        string $storeName,
        string $consentUuid,
        string $customerReference,
        AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer
    ): void {
        $ttlSeconds = $this->agenticCommerceConfig->getConsentCacheTtlSeconds();

        if ($ttlSeconds <= 0 || !$agenticCommerceConsentUsageResponseTransfer->getIsValid()) {
            return;
        }

        try {
            $this->redisWrapper->setIfAbsent(
                $this->buildKey(static::KEY_TYPE_ENTRY, $storeName, $consentUuid),
                json_encode($agenticCommerceConsentUsageResponseTransfer->toArray(true, true), JSON_THROW_ON_ERROR),
                $ttlSeconds,
            );
            $this->redisWrapper->setIfAbsent(
                $this->buildKey(static::KEY_TYPE_LAST_USED_MARKER, $storeName, $consentUuid),
                static::VALUE_LAST_USED_MARKER,
                $this->agenticCommerceConfig->getLastUsedAtWriteIntervalInSeconds(),
            );
            $this->addToCustomerIndex($storeName, $customerReference, $consentUuid, $ttlSeconds);
        } catch (AgenticCommerceStorageUnavailableException | JsonException $exception) {
            $this->logger->error(static::LOG_MESSAGE_FILL_FAILED, ['exception' => $exception, 'storeName' => $storeName]);
        }
    }

    public function invalidate(string $storeName, array $consentUuids): void
    {
        try {
            $this->writeTombstones($storeName, $consentUuids);
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error(static::LOG_MESSAGE_INVALIDATION_FAILED, ['exception' => $exception, 'storeName' => $storeName]);
        }
    }

    public function invalidateByCustomerReference(string $storeName, string $customerReference): void
    {
        try {
            $this->writeTombstones($storeName, $this->readCustomerIndex($storeName, $customerReference));
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error(static::LOG_MESSAGE_INVALIDATION_FAILED, ['exception' => $exception, 'storeName' => $storeName]);
        }
    }

    public function shouldTouchLastUsedAt(string $storeName, string $consentUuid): bool
    {
        try {
            return $this->redisWrapper->setIfAbsent(
                $this->buildKey(static::KEY_TYPE_LAST_USED_MARKER, $storeName, $consentUuid),
                static::VALUE_LAST_USED_MARKER,
                $this->agenticCommerceConfig->getLastUsedAtWriteIntervalInSeconds(),
            );
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error(static::LOG_MESSAGE_READ_FAILED, ['exception' => $exception]);

            return true;
        }
    }

    /**
     * @param list<string> $consentUuids
     */
    protected function writeTombstones(string $storeName, array $consentUuids): void
    {
        $tombstoneTtlSeconds = $this->agenticCommerceConfig->getConsentCacheTombstoneTtlSeconds();

        foreach (array_unique($consentUuids) as $consentUuid) {
            $this->redisWrapper->setex($this->buildKey(static::KEY_TYPE_ENTRY, $storeName, $consentUuid), $tombstoneTtlSeconds, static::VALUE_TOMBSTONE);
        }
    }

    /**
     * Rewritten on every save, so the index never expires before an entry it lists. A concurrent save of another consent
     * of the customer can still drop a uuid from the list; that entry then stays cached until its TTL ends.
     */
    protected function addToCustomerIndex(string $storeName, string $customerReference, string $consentUuid, int $ttlSeconds): void
    {
        $consentUuids = $this->readCustomerIndex($storeName, $customerReference);

        if (!in_array($consentUuid, $consentUuids, true)) {
            $consentUuids[] = $consentUuid;
        }

        $this->redisWrapper->setex(
            $this->buildKey(static::KEY_TYPE_CUSTOMER_INDEX, $storeName, $customerReference),
            $ttlSeconds,
            json_encode($consentUuids, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return list<string>
     */
    protected function readCustomerIndex(string $storeName, string $customerReference): array
    {
        $value = $this->redisWrapper->get($this->buildKey(static::KEY_TYPE_CUSTOMER_INDEX, $storeName, $customerReference));

        if ($value === null) {
            return [];
        }

        $consentUuids = json_decode($value, true);

        if (!is_array($consentUuids)) {
            return [];
        }

        return array_values(array_filter($consentUuids, 'is_string'));
    }

    protected function buildKey(string $keyType, string $storeName, string $identifier): string
    {
        return implode(SharedAgenticCommerceConfig::REDIS_KEY_SEPARATOR, [SharedAgenticCommerceConfig::REDIS_KEY_PREFIX, $keyType, $storeName, $identifier]);
    }
}
