<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Client\AgenticCommerce\KillSwitch\KillSwitch;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group KillSwitchTest
 * Add your own group annotations below this line
 */
class KillSwitchTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    protected const string OTHER_STORE_NAME = 'AT';

    protected const string KEY_DISABLED = 'agentic_commerce:disabled:DE';

    protected const string OTHER_KEY_DISABLED = 'agentic_commerce:disabled:AT';

    protected AgenticCommerceClientTester $tester;

    protected AgenticCommerceRedisWrapperInterface&MockObject $redisWrapperMock;

    protected LoggerInterface&MockObject $loggerMock;

    protected function _before(): void
    {
        $this->redisWrapperMock = $this->createMock(AgenticCommerceRedisWrapperInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    public function testGivenTheSwitchKeyDoesNotExistWhenReadingTheSwitchThenDelegatedAccessIsEnabled(): void
    {
        // Arrange
        $this->redisWrapperMock->method('get')->with(static::KEY_DISABLED)->willReturn(null);

        // Act
        $isEnabled = $this->createKillSwitch()->isEnabled(static::STORE_NAME);

        // Assert
        $this->assertTrue($isEnabled);
    }

    public function testGivenTheSwitchKeyExistsWhenReadingTheSwitchThenDelegatedAccessIsDisabled(): void
    {
        // Arrange
        $this->redisWrapperMock->method('get')->with(static::KEY_DISABLED)->willReturn('1');

        // Act
        $isEnabled = $this->createKillSwitch()->isEnabled(static::STORE_NAME);

        // Assert
        $this->assertFalse($isEnabled);
    }

    public function testGivenRedisIsUnavailableWhenReadingTheSwitchThenDelegatedAccessCountsAsEnabledAndOneErrorIsLogged(): void
    {
        // Arrange
        $this->redisWrapperMock->method('get')->willThrowException(new AgenticCommerceStorageUnavailableException('down'));
        $this->loggerMock->expects($this->once())->method('error');

        // Act
        $isEnabled = $this->createKillSwitch()->isEnabled(static::STORE_NAME);

        // Assert
        $this->assertTrue($isEnabled);
    }

    public function testGivenRedisIsUnavailableWhenReadingTheSwitchStrictlyThenTheStorageUnavailableExceptionReachesTheCallerAndNothingIsLogged(): void
    {
        // Arrange
        $this->redisWrapperMock->method('get')->willThrowException(new AgenticCommerceStorageUnavailableException('down'));
        $this->loggerMock->expects($this->never())->method('error');

        // Assert
        $this->expectException(AgenticCommerceStorageUnavailableException::class);

        // Act
        $this->createKillSwitch()->readIsEnabled(static::STORE_NAME);
    }

    public function testGivenDelegatedAccessIsEnabledWhenDisablingThenTheSwitchKeyIsStoredWithoutExpiry(): void
    {
        // Arrange
        $this->redisWrapperMock->expects($this->once())->method('set')->with(static::KEY_DISABLED, '1')->willReturn(true);
        $this->redisWrapperMock->expects($this->never())->method('setex');

        // Act
        $this->createKillSwitch()->disable(static::STORE_NAME);

        // Assert: expectations on the mock
    }

    public function testGivenDelegatedAccessIsDisabledWhenEnablingThenTheSwitchKeyIsDeleted(): void
    {
        // Arrange
        $this->redisWrapperMock->expects($this->once())->method('del')->with([static::KEY_DISABLED])->willReturn(1);

        // Act
        $this->createKillSwitch()->enable(static::STORE_NAME);

        // Assert: expectation on the mock
    }

    public function testGivenRedisIsUnavailableWhenDisablingThenTheStorageUnavailableExceptionReachesTheCaller(): void
    {
        // Arrange
        $this->redisWrapperMock->method('set')->willThrowException(new AgenticCommerceStorageUnavailableException('down'));

        // Assert
        $this->expectException(AgenticCommerceStorageUnavailableException::class);

        // Act
        $this->createKillSwitch()->disable(static::STORE_NAME);
    }

    public function testGivenOneStoreIsDisabledWhenReadingTheSwitchOfAnotherStoreThenDelegatedAccessOfTheOtherStoreIsEnabled(): void
    {
        // Arrange
        $this->redisWrapperMock->method('get')->willReturnMap([
            [static::KEY_DISABLED, '1'],
            [static::OTHER_KEY_DISABLED, null],
        ]);

        $killSwitch = $this->createKillSwitch();

        // Act
        $isStoreEnabled = $killSwitch->isEnabled(static::STORE_NAME);
        $isOtherStoreEnabled = $killSwitch->isEnabled(static::OTHER_STORE_NAME);

        // Assert
        $this->assertFalse($isStoreEnabled);
        $this->assertTrue($isOtherStoreEnabled);
    }

    public function testGivenTwoStoresWhenDisablingOneStoreThenOnlyTheSwitchKeyOfThatStoreIsStored(): void
    {
        // Arrange
        $this->redisWrapperMock->expects($this->once())->method('set')->with(static::OTHER_KEY_DISABLED, '1')->willReturn(true);

        // Act
        $this->createKillSwitch()->disable(static::OTHER_STORE_NAME);

        // Assert: expectation on the mock
    }

    protected function createKillSwitch(): KillSwitch
    {
        return new KillSwitch($this->redisWrapperMock, $this->loggerMock);
    }
}
