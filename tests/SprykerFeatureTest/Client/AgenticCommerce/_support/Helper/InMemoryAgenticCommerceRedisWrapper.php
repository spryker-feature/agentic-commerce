<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\Helper;

use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;

class InMemoryAgenticCommerceRedisWrapper implements AgenticCommerceRedisWrapperInterface
{
    /**
     * @var array<string, array{value: string, expiresAt: int|null}>
     */
    protected array $entries = [];

    protected bool $isFailing = false;

    public function __construct(protected FakeClock $clock = new FakeClock())
    {
    }

    public function failFromNowOn(): void
    {
        $this->isFailing = true;
    }

    public function resetAvailability(): void
    {
        $this->isFailing = false;
    }

    public function get(string $key): ?string
    {
        $this->assertReachable();

        if (!isset($this->entries[$key])) {
            return null;
        }

        $expiresAt = $this->entries[$key]['expiresAt'];

        if ($expiresAt !== null && $expiresAt <= $this->clock->getTimestamp()) {
            unset($this->entries[$key]);

            return null;
        }

        return $this->entries[$key]['value'];
    }

    public function setex(string $key, int $seconds, string $value): bool
    {
        $this->assertReachable();
        $this->entries[$key] = ['value' => $value, 'expiresAt' => $this->clock->getTimestamp() + $seconds];

        return true;
    }

    public function set(string $key, string $value): bool
    {
        $this->assertReachable();
        $this->entries[$key] = ['value' => $value, 'expiresAt' => null];

        return true;
    }

    public function setIfAbsent(string $key, string $value, int $seconds): bool
    {
        if ($this->get($key) !== null) {
            return false;
        }

        return $this->setex($key, $seconds, $value);
    }

    public function del(array $keys): int
    {
        $this->assertReachable();
        $deletedCount = 0;

        foreach ($keys as $key) {
            if ($this->get($key) !== null) {
                unset($this->entries[$key]);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    protected function assertReachable(): void
    {
        if ($this->isFailing) {
            throw new AgenticCommerceStorageUnavailableException('Redis is unreachable in this test.');
        }
    }
}
