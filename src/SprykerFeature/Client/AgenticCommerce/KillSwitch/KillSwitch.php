<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\KillSwitch;

use Psr\Log\LoggerInterface;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class KillSwitch implements KillSwitchInterface
{
    protected const string KEY_DISABLED = 'disabled';

    protected const string VALUE_DISABLED = '1';

    public function __construct(
        protected AgenticCommerceRedisWrapperInterface $redisWrapper,
        protected LoggerInterface $logger,
    ) {
    }

    public function isEnabled(string $storeName): bool
    {
        try {
            return $this->readIsEnabled($storeName);
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error('The delegated access kill switch could not be read, so delegated access stays enabled.', ['exception' => $exception]);

            return true;
        }
    }

    public function readIsEnabled(string $storeName): bool
    {
        return $this->redisWrapper->get($this->buildDisabledKey($storeName)) === null;
    }

    public function disable(string $storeName): void
    {
        $this->redisWrapper->set($this->buildDisabledKey($storeName), static::VALUE_DISABLED);
    }

    public function enable(string $storeName): void
    {
        $this->redisWrapper->del([$this->buildDisabledKey($storeName)]);
    }

    protected function buildDisabledKey(string $storeName): string
    {
        return SharedAgenticCommerceConfig::REDIS_KEY_PREFIX
            . SharedAgenticCommerceConfig::REDIS_KEY_SEPARATOR
            . static::KEY_DISABLED
            . SharedAgenticCommerceConfig::REDIS_KEY_SEPARATOR
            . $storeName;
    }
}
