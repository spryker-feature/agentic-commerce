<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Redis;

use Predis\PredisException;
use RedisException;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use Throwable;

/**
 * The first failure of a process latches: every later call fails fast with the same exception, without a new
 * connection attempt that would wait for the full connect timeout again, until `resetAvailability()`.
 */
class AgenticCommerceRedisWrapper implements AgenticCommerceRedisWrapperInterface
{
    protected const string EXPIRE_RESOLUTION_SECONDS = 'EX';

    protected const string FLAG_ONLY_IF_ABSENT = 'NX';

    protected static ?AgenticCommerceStorageUnavailableException $unavailableException = null;

    protected bool $isConnectionSetUp = false;

    public function __construct(
        protected RedisClientInterface $redisClient,
        protected AgenticCommerceConfig $config,
    ) {
    }

    public function get(string $key): ?string
    {
        return $this->execute(fn (): ?string => $this->redisClient->get($this->config->getRedisConnectionKey(), $key));
    }

    public function setex(string $key, int $seconds, string $value): bool
    {
        return $this->execute(fn (): bool => $this->redisClient->setex($this->config->getRedisConnectionKey(), $key, $seconds, $value));
    }

    public function set(string $key, string $value): bool
    {
        return $this->execute(fn (): bool => $this->redisClient->set($this->config->getRedisConnectionKey(), $key, $value));
    }

    public function setIfAbsent(string $key, string $value, int $seconds): bool
    {
        return $this->execute(fn (): bool => $this->redisClient->set(
            $this->config->getRedisConnectionKey(),
            $key,
            $value,
            static::EXPIRE_RESOLUTION_SECONDS,
            $seconds,
            static::FLAG_ONLY_IF_ABSENT,
        ));
    }

    public function del(array $keys): int
    {
        return $this->execute(fn (): int => $this->redisClient->del($this->config->getRedisConnectionKey(), $keys));
    }

    public function resetAvailability(): void
    {
        static::$unavailableException = null;
    }

    /**
     * @template T
     *
     * @param callable(): T $command
     *
     * @return T
     */
    protected function execute(callable $command): mixed
    {
        if (static::$unavailableException !== null) {
            throw static::$unavailableException;
        }

        try {
            $this->setUpConnection();

            return $command();
        } catch (PredisException | RedisException $exception) {
            throw static::$unavailableException = new AgenticCommerceStorageUnavailableException($exception->getMessage(), 0, $exception);
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            throw static::$unavailableException = $exception;
        }
    }

    protected function setUpConnection(): void
    {
        if ($this->isConnectionSetUp) {
            return;
        }

        try {
            $this->redisClient->setupConnection(
                $this->config->getRedisConnectionKey(),
                $this->config->getRedisConnectionConfiguration(),
            );
        } catch (Throwable $throwable) {
            throw new AgenticCommerceStorageUnavailableException($throwable->getMessage(), 0, $throwable);
        }

        $this->isConnectionSetUp = true;
    }
}
