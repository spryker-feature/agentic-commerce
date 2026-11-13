<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use Exception;
use Predis\Connection\ConnectionException;
use Predis\Connection\Parameters;
use Predis\Connection\StreamConnection;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapper;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group AgenticCommerceRedisWrapperTest
 * Add your own group annotations below this line
 */
class AgenticCommerceRedisWrapperTest extends Unit
{
    protected const int TTL_SECONDS = 60;

    protected AgenticCommerceClientTester $tester;

    protected function tearDown(): void
    {
        $this->createRedisWrapper($this->createMock(RedisClientInterface::class))->resetAvailability();

        parent::tearDown();
    }

    public function testGivenTheRedisClientThrowsWhenReadingThenAStorageUnavailableExceptionIsThrown(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->method('get')->willThrowException(new ConnectionException(new StreamConnection(new Parameters()), 'down'));
        $redisWrapper = new AgenticCommerceRedisWrapper($redisClientMock, new AgenticCommerceConfig());

        // Assert
        $this->expectException(AgenticCommerceStorageUnavailableException::class);

        // Act
        $redisWrapper->get('agentic_commerce:disabled');
    }

    public function testGivenTheRedisConfigurationIsMissingWhenReadingThenAStorageUnavailableExceptionIsThrown(): void
    {
        // Arrange
        $configMock = $this->createMock(AgenticCommerceConfig::class);
        $configMock->method('getRedisConnectionConfiguration')->willThrowException(new Exception('Could not find config key "AGENTIC_COMMERCE:REDIS_HOST"'));
        $redisWrapper = new AgenticCommerceRedisWrapper($this->createMock(RedisClientInterface::class), $configMock);

        // Assert
        $this->expectException(AgenticCommerceStorageUnavailableException::class);

        // Act
        $redisWrapper->get('agentic_commerce:disabled');
    }

    public function testGivenAKeyWhenReadingTwiceThenTheConnectionIsSetUpOnce(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->expects($this->once())->method('setupConnection');
        $redisClientMock->method('get')->willReturn(null);
        $redisWrapper = new AgenticCommerceRedisWrapper($redisClientMock, new AgenticCommerceConfig());

        // Act
        $redisWrapper->get('a');
        $redisWrapper->get('b');

        // Assert: expectation on the mock
    }

    public function testGivenAnExistingKeyWhenSettingIfAbsentThenRedisIsAskedWithNxAndFalseIsReturned(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->expects($this->once())->method('set')
            ->with($this->anything(), $this->stringContains('consent:DE:consent-1'), 'payload', 'EX', static::TTL_SECONDS, 'NX')
            ->willReturn(false);
        $redisWrapper = new AgenticCommerceRedisWrapper($redisClientMock, new AgenticCommerceConfig());

        // Act
        $isSet = $redisWrapper->setIfAbsent('agentic_commerce:consent:DE:consent-1', 'payload', static::TTL_SECONDS);

        // Assert
        $this->assertFalse($isSet);
    }

    public function testGivenAFailedCallWhenAnotherCallFollowsThenItFailsFastWithoutTouchingTheRedisClient(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->expects($this->once())->method('get')->willThrowException($this->createConnectionException());
        $redisClientMock->expects($this->never())->method('setex');
        $redisWrapper = $this->createRedisWrapper($redisClientMock);
        $this->callIgnoringUnavailability(fn (): ?string => $redisWrapper->get('agentic_commerce:disabled'));

        // Assert
        $this->expectException(AgenticCommerceStorageUnavailableException::class);

        // Act
        $redisWrapper->setex('agentic_commerce:consent:DE:consent-1', static::TTL_SECONDS, 'payload');
    }

    public function testGivenAFailedCallWhenAnotherWrapperIsCalledInTheSameProcessThenItFailsFastToo(): void
    {
        // Arrange
        $failingRedisClientMock = $this->createMock(RedisClientInterface::class);
        $failingRedisClientMock->method('get')->willThrowException($this->createConnectionException());
        $this->callIgnoringUnavailability(fn (): ?string => $this->createRedisWrapper($failingRedisClientMock)->get('agentic_commerce:disabled'));
        $otherRedisClientMock = $this->createMock(RedisClientInterface::class);
        $otherRedisClientMock->expects($this->never())->method('setupConnection');
        $otherRedisClientMock->expects($this->never())->method('get');

        // Assert
        $this->expectException(AgenticCommerceStorageUnavailableException::class);

        // Act
        $this->createRedisWrapper($otherRedisClientMock)->get('agentic_commerce:disabled');
    }

    public function testGivenAFailedCallWhenTheAvailabilityIsResetThenTheNextCallReachesRedisAgain(): void
    {
        // Arrange
        $callCount = 0;
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->expects($this->exactly(2))->method('get')->willReturnCallback(function () use (&$callCount): string {
            if (++$callCount === 1) {
                throw $this->createConnectionException();
            }

            return '1';
        });
        $redisWrapper = $this->createRedisWrapper($redisClientMock);
        $this->callIgnoringUnavailability(fn (): ?string => $redisWrapper->get('agentic_commerce:disabled'));

        // Act
        $redisWrapper->resetAvailability();
        $value = $redisWrapper->get('agentic_commerce:disabled');

        // Assert
        $this->assertSame('1', $value);
    }

    protected function createRedisWrapper(RedisClientInterface $redisClient): AgenticCommerceRedisWrapper
    {
        return new AgenticCommerceRedisWrapper($redisClient, new AgenticCommerceConfig());
    }

    protected function createConnectionException(): ConnectionException
    {
        return new ConnectionException(new StreamConnection(new Parameters()), 'down');
    }

    protected function callIgnoringUnavailability(callable $call): void
    {
        try {
            $call();
        } catch (AgenticCommerceStorageUnavailableException) {
        }
    }
}
