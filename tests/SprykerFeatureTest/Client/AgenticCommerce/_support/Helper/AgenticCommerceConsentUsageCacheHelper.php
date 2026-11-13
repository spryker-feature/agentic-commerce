<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\Helper;

use Codeception\Module;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\Cache\ConsentUsageCache;
use SprykerFeature\Client\AgenticCommerce\Cache\ConsentUsageCacheInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;

class AgenticCommerceConsentUsageCacheHelper extends Module
{
    use ConfigHelperTrait;

    protected ?RecordingLogger $logger = null;

    public function createFakeClock(): FakeClock
    {
        return new FakeClock();
    }

    public function createConsentUsageCacheWithInMemoryRedis(?int $ttlSeconds = null, ?FakeClock $clock = null): ConsentUsageCacheInterface
    {
        if ($ttlSeconds !== null) {
            $this->getConfigHelper()->setConfig(AgenticCommerceConstants::CONSENT_CACHE_TTL_SECONDS, $ttlSeconds);
        }

        return $this->createConsentUsageCache(new InMemoryAgenticCommerceRedisWrapper($clock ?? new FakeClock()));
    }

    public function createConsentUsageCacheWithFailingRedis(): ConsentUsageCacheInterface
    {
        $inMemoryRedisWrapper = new InMemoryAgenticCommerceRedisWrapper();
        $inMemoryRedisWrapper->failFromNowOn();

        return $this->createConsentUsageCache($inMemoryRedisWrapper);
    }

    public function createConsentUsageCacheWithStoredEntry(string $key, string $value): ConsentUsageCacheInterface
    {
        $inMemoryRedisWrapper = new InMemoryAgenticCommerceRedisWrapper();
        $inMemoryRedisWrapper->set($key, $value);

        return $this->createConsentUsageCache($inMemoryRedisWrapper);
    }

    /**
     * @return list<string>
     */
    public function grabLoggedConsentCacheErrors(): array
    {
        return $this->logger?->getErrorMessages() ?? [];
    }

    protected function createConsentUsageCache(InMemoryAgenticCommerceRedisWrapper $inMemoryRedisWrapper): ConsentUsageCacheInterface
    {
        $this->logger = new RecordingLogger();

        return new ConsentUsageCache($inMemoryRedisWrapper, new AgenticCommerceConfig(), $this->logger);
    }
}
