<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spryker\Client\SecurityBlocker\Exception\SecurityBlockerException;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use Spryker\Client\SecurityBlocker\Storage\KeyBuilder\SecurityBlockerStorageKeyBuilder;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\Plugin\SecurityBlocker\AgenticCommerceSecurityBlockerConfigurationSettingsExpanderPlugin;
use SprykerFeature\Client\AgenticCommerce\SecurityBlocker\RateLimitChecker;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;
use TypeError;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group RateLimitCheckerTest
 * Add your own group annotations below this line
 */
class RateLimitCheckerTest extends Unit
{
    protected const string IP = '203.0.113.7';

    protected const string CLIENT_ID = 'client-1';

    protected const string METADATA_HOST = 'app.example';

    protected const int TOKEN_BLOCK_SECONDS = 300;

    protected AgenticCommerceClientTester $tester;

    public function testGivenTheTokenTypeWhenCheckingThenTheAccountIsTheClientIdAndTheIpIsTheCallerIp(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->expects($this->once())->method('isAccountBlocked')
            ->with($this->callback(fn (SecurityCheckAuthContextTransfer $context): bool => $context->getType() === 'agentic_commerce_token' && $context->getAccount() === static::CLIENT_ID && $context->getIp() === static::IP))
            ->willReturn($this->createResponse(false));
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willReturn($this->createResponse(false));

        // Act
        $this->createChecker($securityBlockerClientMock, $this->createLoggerMock())->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);

        // Assert: expectation on the mock
    }

    public function testGivenABlockedBucketWhenCheckingThenTheBlockedResponseIsReturnedAndNothingIsCounted(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willReturn($this->createResponse(true, static::TOKEN_BLOCK_SECONDS));
        $securityBlockerClientMock->expects($this->never())->method('incrementLoginAttemptCount');

        // Act
        $responseTransfer = $this->createChecker($securityBlockerClientMock, $this->createLoggerMock())->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);

        // Assert
        $this->assertTrue($responseTransfer->getIsBlocked());
        $this->assertSame(static::TOKEN_BLOCK_SECONDS, $responseTransfer->getBlockedFor());
    }

    public function testGivenTheFailureTypeWhenCheckingThenNothingIsCounted(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willReturn($this->createResponse(false));
        $securityBlockerClientMock->expects($this->never())->method('incrementLoginAttemptCount');

        // Act
        $responseTransfer = $this->createChecker($securityBlockerClientMock, $this->createLoggerMock())->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE, static::IP, null);

        // Assert
        $this->assertFalse($responseTransfer->getIsBlocked());
    }

    public function testGivenAFailedGrantWhenCountedThenBothTheClientBucketAndTheIpFailureBucketIncrease(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $countedTypes = [];
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willReturnCallback(function (SecurityCheckAuthContextTransfer $context) use (&$countedTypes): SecurityCheckAuthResponseTransfer {
            $countedTypes[] = [$context->getType(), $context->getAccount()];

            return $this->createResponse(false);
        });

        // Act
        $this->createChecker($securityBlockerClientMock, $this->createLoggerMock())->incrementFailedAttempt(static::IP, static::CLIENT_ID);

        // Assert
        $this->assertContains(['agentic_commerce_token', static::CLIENT_ID], $countedTypes);
        $this->assertContains(['agentic_commerce_token_failure', null], $countedTypes);
    }

    public function testGivenTheIncrementFillsTheBucketWhenCheckingThenThisRequestStillPasses(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willReturn($this->createResponse(false));
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willReturn($this->createResponse(true, static::TOKEN_BLOCK_SECONDS));

        // Act
        $responseTransfer = $this->createChecker($securityBlockerClientMock, $this->createLoggerMock())->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);

        // Assert
        $this->assertFalse($responseTransfer->getIsBlocked());
    }

    public function testGivenTheSecurityBlockerThrowsWhenCheckingThenTheRequestPassesAndAnErrorIsLogged(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willThrowException(new SecurityBlockerException('Redis unavailable'));
        $loggerMock = $this->createLoggerMock();
        $loggerMock->expects($this->once())->method('error');

        // Act
        $responseTransfer = $this->createChecker($securityBlockerClientMock, $loggerMock)->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);

        // Assert
        $this->assertFalse($responseTransfer->getIsBlocked());
    }

    public function testGivenTheSecurityBlockerThrowsWhenCountingAFailedGrantThenNothingIsThrownAndAnErrorIsLogged(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willThrowException(new SecurityBlockerException('Redis unavailable'));
        $loggerMock = $this->createLoggerMock();
        $loggerMock->expects($this->atLeastOnce())->method('error');

        // Act
        $this->createChecker($securityBlockerClientMock, $loggerMock)->incrementFailedAttempt(static::IP, static::CLIENT_ID);

        // Assert: nothing thrown
        $this->addToAssertionCount(1);
    }

    public function testGivenTheSecurityBlockerThrowsAnErrorWhenCheckingThenTheErrorIsNotSwallowed(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willThrowException(new TypeError('Programming error'));
        $loggerMock = $this->createLoggerMock();
        $loggerMock->expects($this->never())->method('error');
        $checker = $this->createChecker($securityBlockerClientMock, $loggerMock);

        // Assert
        $this->expectException(TypeError::class);

        // Act
        $checker->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);
    }

    public function testGivenALatchedFailureWhenANewCheckerChecksThenSecurityBlockerIsNotCalledUntilTheLatchIsReset(): void
    {
        // Arrange
        $failingSecurityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $failingSecurityBlockerClientMock->method('isAccountBlocked')->willThrowException(new SecurityBlockerException('Redis unavailable'));
        $this->createChecker($failingSecurityBlockerClientMock, $this->createLoggerMock())->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->expects($this->once())->method('isAccountBlocked')->willReturn($this->createResponse(false));
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willReturn($this->createResponse(false));
        $configMock = $this->createMock(AgenticCommerceConfig::class);
        $configMock->method('isRateLimitEnabled')->willReturn(true);
        $newChecker = new RateLimitChecker($securityBlockerClientMock, $configMock, $this->createLoggerMock());

        // Act
        $newChecker->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);
        $newChecker->forgetFailure();
        $newChecker->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);

        // Assert: isAccountBlocked is called once, after the reset
    }

    public function testGivenTheMetadataFetchTypeAndAnEmptyIpWhenCheckingThenTheBucketIsPerHostAndTheIpStaysEmpty(): void
    {
        // Arrange
        $contexts = [];
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willReturn($this->createResponse(false));
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willReturnCallback(function (SecurityCheckAuthContextTransfer $context) use (&$contexts): SecurityCheckAuthResponseTransfer {
            $contexts[] = $context;

            return $this->createResponse(false);
        });
        $checker = $this->createChecker($securityBlockerClientMock, $this->createLoggerMock());

        // Act
        $checker->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_METADATA_FETCH, '', static::METADATA_HOST);
        $checker->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_METADATA_FETCH, '', 'other.example');

        // Assert
        $keyBuilder = new SecurityBlockerStorageKeyBuilder();
        $this->assertSame('agentic_commerce_metadata_fetch::app.example', $keyBuilder->getStorageKey($contexts[0]));
        $this->assertNotSame($keyBuilder->getStorageKey($contexts[0]), $keyBuilder->getStorageKey($contexts[1]));
    }

    public function testGivenTheRateLimitIsOffWhenCheckingThenTheRequestIsNotBlockedAndSecurityBlockerIsNotCalled(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->expects($this->never())->method('isAccountBlocked');
        $securityBlockerClientMock->expects($this->never())->method('incrementLoginAttemptCount');
        $loggerMock = $this->createLoggerMock();
        $loggerMock->expects($this->never())->method($this->anything());

        // Act
        $responseTransfer = $this->createChecker($securityBlockerClientMock, $loggerMock, false)->checkRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, static::IP, static::CLIENT_ID);

        // Assert
        $this->assertFalse($responseTransfer->getIsBlocked());
    }

    public function testGivenTheRateLimitIsOffWhenCountingAFailedGrantThenSecurityBlockerIsNotCalled(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->expects($this->never())->method('incrementLoginAttemptCount');

        // Act
        $this->createChecker($securityBlockerClientMock, $this->createLoggerMock(), false)->incrementFailedAttempt(static::IP, static::CLIENT_ID);

        // Assert: expectation on the mock
    }

    public function testGivenTheConfigurationHasNoValueWhenAskingIfTheRateLimitIsEnabledThenItIsOff(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::RATE_LIMIT_ENABLED, null);

        // Act
        $isRateLimitEnabled = (new AgenticCommerceConfig())->isRateLimitEnabled();

        // Assert
        $this->assertFalse($isRateLimitEnabled);
    }

    public function testGivenTheExpanderPluginWhenExpandingThenEveryTypeGetsItsAttemptsTtlAndBlockDuration(): void
    {
        // Act
        $settings = (new AgenticCommerceSecurityBlockerConfigurationSettingsExpanderPlugin())->expand([]);

        // Assert
        $this->assertSame(
            [
                'agentic_commerce_register' => [10, 3600, 3600],
                'agentic_commerce_token' => [60, 60, 300],
                'agentic_commerce_token_failure' => [30, 600, 900],
                'agentic_commerce_authorize' => [30, 60, 300],
                'agentic_commerce_metadata_fetch' => [30, 600, 900],
            ],
            array_map(
                static fn (SecurityBlockerConfigurationSettingsTransfer $transfer): array => [$transfer->getNumberOfAttempts(), $transfer->getTtl(), $transfer->getBlockFor()],
                $settings,
            ),
        );
    }

    public function testGivenExistingSettingsWhenExpandingThenTheyAreKept(): void
    {
        // Arrange
        $existing = ['customer' => new SecurityBlockerConfigurationSettingsTransfer()];

        // Act
        $settings = (new AgenticCommerceSecurityBlockerConfigurationSettingsExpanderPlugin())->expand($existing);

        // Assert
        $this->assertArrayHasKey('customer', $settings);
    }

    protected function createChecker(
        SecurityBlockerClientInterface $securityBlockerClient,
        LoggerInterface $logger,
        bool $isRateLimitEnabled = true
    ): RateLimitChecker {
        $configMock = $this->createMock(AgenticCommerceConfig::class);
        $configMock->method('isRateLimitEnabled')->willReturn($isRateLimitEnabled);

        $rateLimitChecker = new RateLimitChecker($securityBlockerClient, $configMock, $logger);
        $rateLimitChecker->forgetFailure();

        return $rateLimitChecker;
    }

    protected function createResponse(bool $isBlocked, ?int $blockedFor = null): SecurityCheckAuthResponseTransfer
    {
        return (new SecurityCheckAuthResponseTransfer())->setIsBlocked($isBlocked)->setBlockedFor($blockedFor);
    }

    protected function createLoggerMock(): LoggerInterface&MockObject
    {
        return $this->createMock(LoggerInterface::class);
    }
}
