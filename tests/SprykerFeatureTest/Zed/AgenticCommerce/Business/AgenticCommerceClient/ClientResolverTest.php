<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentClientWriterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFailureCacheInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFetcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataMapperInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientResolver;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientResolverInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group AgenticCommerceClient
 * @group ClientResolverTest
 * Add your own group annotations below this line
 */
class ClientResolverTest extends Unit
{
    protected const string CLIENT_IDENTIFIER = 'https://vendor.example/oauth/client.json';

    protected const string CLIENT_HOST = 'vendor.example';

    protected const string OTHER_HOST = 'other.example';

    protected const string SOFTWARE_ID = 'procurement-assistant';

    protected const string REDIRECT_HOST = 'callback.example';

    protected const string REDIRECT_URI = 'https://callback.example/oauth/callback';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected AgenticCommerceBusinessTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->mockConfigMethod('isClientIdMetadataDocumentEnabled', true);
        $this->tester->mockConfigMethod('getClientIdMetadataDocumentHostAllowlist', []);
        $this->tester->mockConfigMethod('getClientDenylistHosts', []);
        $this->tester->mockConfigMethod('getClientDenylistSoftwareIds', []);
    }

    public function testGivenAFreshStoredClientAndNoConfiguredListsWhenResolvedThenTheStoredClientIsReturnedWithoutAFetch(): void
    {
        // Arrange
        $clientResolver = $this->createClientResolverWithFreshStoredClient();

        // Act
        $resolveResponseTransfer = $clientResolver->resolveClient(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertNull($resolveResponseTransfer->getFailureReason());
        $this->assertSame(static::CLIENT_IDENTIFIER, $resolveResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifier());
    }

    public function testGivenAFreshStoredClientWhoseHostIsNotOnTheAllowlistWhenResolvedThenItIsRefusedWithoutAFetch(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getClientIdMetadataDocumentHostAllowlist', [static::OTHER_HOST]);
        $clientResolver = $this->createClientResolverWithFreshStoredClient();

        // Act
        $resolveResponseTransfer = $clientResolver->resolveClient(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_NOT_ALLOWED, $resolveResponseTransfer->getFailureReason());
        $this->assertNull($resolveResponseTransfer->getAgenticCommerceClient());
    }

    public function testGivenAFreshStoredClientWhoseHostIsDenylistedWhenResolvedThenItIsRefusedWithoutAFetch(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getClientDenylistHosts', [static::CLIENT_HOST]);
        $clientResolver = $this->createClientResolverWithFreshStoredClient();

        // Act
        $resolveResponseTransfer = $clientResolver->resolveClient(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED, $resolveResponseTransfer->getFailureReason());
        $this->assertNull($resolveResponseTransfer->getAgenticCommerceClient());
    }

    public function testGivenAFreshStoredClientWhoseSoftwareIdIsDenylistedWhenResolvedThenItIsRefusedWithoutAFetch(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getClientDenylistSoftwareIds', [static::SOFTWARE_ID]);
        $clientResolver = $this->createClientResolverWithFreshStoredClient();

        // Act
        $resolveResponseTransfer = $clientResolver->resolveClient(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_SOFTWARE_ID_BLOCKED, $resolveResponseTransfer->getFailureReason());
        $this->assertNull($resolveResponseTransfer->getAgenticCommerceClient());
    }

    public function testGivenAFreshStoredClientWhoseRedirectHostIsDenylistedWhenResolvedThenItIsRefusedWithoutAFetch(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getClientDenylistHosts', [static::REDIRECT_HOST]);
        $clientResolver = $this->createClientResolverWithFreshStoredClient();

        // Act
        $resolveResponseTransfer = $clientResolver->resolveClient(static::CLIENT_IDENTIFIER);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_REDIRECT_HOST_BLOCKED, $resolveResponseTransfer->getFailureReason());
        $this->assertNull($resolveResponseTransfer->getAgenticCommerceClient());
    }

    protected function createClientResolverWithFreshStoredClient(): ClientResolverInterface
    {
        $storedClientTransfer = (new AgenticCommerceClientTransfer())
            ->setClientIdentifier(static::CLIENT_IDENTIFIER)
            ->setRegistrationType(SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT)
            ->setSoftwareId(static::SOFTWARE_ID)
            ->setRedirectUris([static::REDIRECT_URI])
            ->setMetadataExpiresAt((new DateTimeImmutable('+1 hour'))->format(static::DATE_TIME_FORMAT));

        $repositoryMock = $this->createMock(AgenticCommerceRepositoryInterface::class);
        $repositoryMock->method('findAgenticCommerceClientByClientIdentifier')->willReturn($storedClientTransfer);

        $fetcherMock = $this->createMock(ClientMetadataDocumentFetcherInterface::class);
        $fetcherMock->expects($this->never())->method('fetch');

        /** @var \SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceBusinessFactory $businessFactory */
        $businessFactory = $this->tester->getFactory();
        /** @var \SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig $agenticCommerceConfig */
        $agenticCommerceConfig = $this->tester->getModuleConfig();

        return new ClientResolver(
            $repositoryMock,
            $fetcherMock,
            $this->createMock(ClientMetadataMapperInterface::class),
            $this->createMock(ClientMetadataValidatorInterface::class),
            $this->createMock(ClientMetadataDocumentClientWriterInterface::class),
            $businessFactory->createBlockedClientMatcher(),
            $this->createMock(ClientMetadataDocumentFailureCacheInterface::class),
            $businessFactory->getAgenticCommerceClient(),
            $businessFactory->getAgenticCommerceService(),
            $agenticCommerceConfig,
        );
    }
}
