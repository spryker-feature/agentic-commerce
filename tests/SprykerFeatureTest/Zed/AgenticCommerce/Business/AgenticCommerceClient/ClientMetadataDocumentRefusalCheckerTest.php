<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentRefusalCheckerInterface;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group AgenticCommerceClient
 * @group ClientMetadataDocumentRefusalCheckerTest
 * Add your own group annotations below this line
 */
class ClientMetadataDocumentRefusalCheckerTest extends Unit
{
    protected const string CLIENT_IDENTIFIER = 'https://vendor.example/oauth/client.json';

    protected const string CLIENT_HOST = 'vendor.example';

    protected const int FAILURE_CACHE_TTL_SECONDS = 300;

    protected const int OAUTH_CLIENT_IDENTIFIER_COLUMN_MAX_LENGTH = 1024;

    protected AgenticCommerceBusinessTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->mockConfigMethod('isClientIdMetadataDocumentEnabled', true);
        $this->tester->mockConfigMethod('getClientIdMetadataDocumentHostAllowlist', []);
        $this->tester->mockConfigMethod('getClientDenylistHosts', []);
        $this->tester->mockConfigMethod('getClientDenylistSoftwareIds', []);
        $this->tester->mockConfigMethod('getClientIdMetadataDocumentFailureCacheTtlSeconds', static::FAILURE_CACHE_TTL_SECONDS);
    }

    public function testGivenARecentFetchFailureIsCachedWhenCheckingTheFetchThenItIsRefusedAsRecentlyFailed(): void
    {
        // Arrange
        $refusalChecker = $this->createRefusalChecker(isFailureCached: true, isRateLimited: false);

        // Act
        $refusalReason = $refusalChecker->findFetchRefusalReason(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_RECENTLY_FAILED, $refusalReason);
    }

    public function testGivenThePerHostFetchLimitIsReachedWhenCheckingTheFetchThenItIsRefusedAsRateLimited(): void
    {
        // Arrange
        $agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $agenticCommerceClientMock->method('hasAgenticCommerceMetadataDocumentFailure')->willReturn(false);
        $this->tester->setDependency(AgenticCommerceDependencyProvider::CLIENT_AGENTIC_COMMERCE, $agenticCommerceClientMock);

        // Assert
        $agenticCommerceClientMock->expects($this->once())
            ->method('checkAgenticCommerceRateLimit')
            ->with(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_METADATA_FETCH, '', static::CLIENT_HOST)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true));

        // Act
        $refusalReason = $this->tester->getFactory()->createClientMetadataDocumentRefusalChecker()->findFetchRefusalReason(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_RATE_LIMITED, $refusalReason);
    }

    public function testGivenNoCachedFailureAndNoReachedLimitWhenCheckingTheFetchThenItIsAllowed(): void
    {
        // Arrange
        $refusalChecker = $this->createRefusalChecker(isFailureCached: false, isRateLimited: false);

        // Act
        $refusalReason = $refusalChecker->findFetchRefusalReason(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertNull($refusalReason);
    }

    /**
     * @dataProvider invalidMetadataDocumentUrlDataProvider
     */
    public function testGivenAnInvalidMetadataDocumentUrlWhenCheckingTheConfigurationThenItIsRefusedAsInvalidUrl(string $clientIdentifier): void
    {
        // Arrange
        $refusalChecker = $this->createRefusalChecker(isFailureCached: false, isRateLimited: false);

        // Act
        $refusalReason = $refusalChecker->findConfigurationRefusalReason($clientIdentifier, null);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_URL, $refusalReason);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidMetadataDocumentUrlDataProvider(): array
    {
        return [
            'http scheme' => ['http://vendor.example/oauth/client.json'],
            'user information' => ['https://user:secret@vendor.example/oauth/client.json'],
            'fragment' => ['https://vendor.example/oauth/client.json#part'],
            'current directory segment' => ['https://vendor.example/oauth/./client.json'],
            'parent directory segment' => ['https://vendor.example/oauth/../client.json'],
            'root path only' => ['https://vendor.example/'],
            'longer than the identifier column' => ['https://vendor.example/' . str_repeat('a', static::OAUTH_CLIENT_IDENTIFIER_COLUMN_MAX_LENGTH)],
        ];
    }

    public function testGivenAValidHttpsMetadataDocumentUrlWhenCheckingTheConfigurationThenItIsNotRefused(): void
    {
        // Arrange
        $refusalChecker = $this->createRefusalChecker(isFailureCached: false, isRateLimited: false);

        // Act
        $refusalReason = $refusalChecker->findConfigurationRefusalReason(static::CLIENT_IDENTIFIER, null);

        // Assert
        $this->assertNull($refusalReason);
    }

    public function testGivenADenylistWithAnInvalidEntryWhenCheckingTheConfigurationThenAConfigurationExceptionIsThrown(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getClientDenylistHosts', [static::CLIENT_HOST, 'not a host']);
        $refusalChecker = $this->createRefusalChecker(isFailureCached: false, isRateLimited: false);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $refusalChecker->findConfigurationRefusalReason(static::CLIENT_IDENTIFIER, null);
    }

    public function testGivenAnAllowlistWithAnInvalidEntryWhenCheckingTheConfigurationThenAConfigurationExceptionIsThrown(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getClientIdMetadataDocumentHostAllowlist', [static::CLIENT_HOST, 'not a host']);
        $refusalChecker = $this->createRefusalChecker(isFailureCached: false, isRateLimited: false);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $refusalChecker->findConfigurationRefusalReason(static::CLIENT_IDENTIFIER, null);
    }

    protected function createRefusalChecker(bool $isFailureCached, bool $isRateLimited): ClientMetadataDocumentRefusalCheckerInterface
    {
        $agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $agenticCommerceClientMock->method('hasAgenticCommerceMetadataDocumentFailure')->willReturn($isFailureCached);
        $agenticCommerceClientMock->method('checkAgenticCommerceRateLimit')->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked($isRateLimited));
        $this->tester->setDependency(AgenticCommerceDependencyProvider::CLIENT_AGENTIC_COMMERCE, $agenticCommerceClientMock);

        return $this->tester->getFactory()->createClientMetadataDocumentRefusalChecker();
    }
}
