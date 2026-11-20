<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Provider;

use ApiPlatform\Metadata\Get;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationServerMetadataTransfer;
use Spryker\Shared\ErrorHandler\ErrorLoggerInterface;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Provider\OauthAuthorizationServerMetadataStorefrontProvider;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilder;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Api
 * @group Storefront
 * @group Provider
 * @group OauthAuthorizationServerMetadataStorefrontProviderTest
 * Add your own group annotations below this line
 */
class OauthAuthorizationServerMetadataStorefrontProviderTest extends Unit
{
    protected const string ISSUER = 'https://glue.example';

    protected const string TYPE_ORDER_TOTAL_LIMIT = 'https://spryker.com/oauth/authorization-details/order-total-limit';

    protected const string TYPE_ORDER_BUDGET = 'https://spryker.com/oauth/authorization-details/order-budget';

    public function testGivenTheWiredPluginsDeclareTypesWhenProvidingTheMetadataThenItAdvertisesExactlyThoseTypesInTheirOrder(): void
    {
        // Arrange
        $clientMock = $this->createClientReturning([static::TYPE_ORDER_TOTAL_LIMIT, static::TYPE_ORDER_BUDGET]);

        // Act
        $response = $this->createProvider($clientMock)->provide(new Get());

        // Assert
        $this->assertSame([static::TYPE_ORDER_TOTAL_LIMIT, static::TYPE_ORDER_BUDGET], $this->decodeDocument($response)['authorization_details_types_supported']);
    }

    public function testGivenOnlyTheOrderTotalLimitPluginIsWiredWhenProvidingTheMetadataThenItAdvertisesOnlyItsType(): void
    {
        // Arrange
        $clientMock = $this->createClientReturning([static::TYPE_ORDER_TOTAL_LIMIT]);

        // Act
        $response = $this->createProvider($clientMock)->provide(new Get());

        // Assert
        $this->assertSame([static::TYPE_ORDER_TOTAL_LIMIT], $this->decodeDocument($response)['authorization_details_types_supported']);
    }

    public function testGivenNoPluginDeclaresATypeWhenProvidingTheMetadataThenTheKeyIsLeftOut(): void
    {
        // Arrange
        $clientMock = $this->createClientReturning([]);

        // Act
        $response = $this->createProvider($clientMock)->provide(new Get());

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertArrayNotHasKey('authorization_details_types_supported', $this->decodeDocument($response));
    }

    public function testGivenZedFailsWhenTheDocumentIsRequestedThenServerErrorIsAnsweredAndTheFailureIsNotLoggedAgain(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('getAgenticCommerceAuthorizationDetailsTypeCollection')->willThrowException(new RequestException());
        $errorLoggerMock = $this->createMock(ErrorLoggerInterface::class);

        // Assert
        $errorLoggerMock->expects($this->never())->method('log');

        // Act
        $response = $this->createProvider($clientMock, $errorLoggerMock)->provide(new Get());

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('server_error', $this->decodeDocument($response)['error']);
    }

    /**
     * @param list<string> $authorizationDetailsTypes
     */
    protected function createClientReturning(array $authorizationDetailsTypes): AgenticCommerceClientInterface
    {
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('getAgenticCommerceAuthorizationDetailsTypeCollection')
            ->willReturn((new AgenticCommerceAuthorizationDetailsTypeCollectionTransfer())->setAuthorizationDetailsTypes($authorizationDetailsTypes));

        return $clientMock;
    }

    protected function createProvider(
        AgenticCommerceClientInterface $agenticCommerceClient,
        ?ErrorLoggerInterface $errorLogger = null
    ): OauthAuthorizationServerMetadataStorefrontProvider {
        $serviceMock = $this->createMock(AgenticCommerceServiceInterface::class);
        $serviceMock->method('buildAuthorizationServerMetadata')->willReturn((new AgenticCommerceAuthorizationServerMetadataTransfer())->setIssuer(static::ISSUER));

        return new OauthAuthorizationServerMetadataStorefrontProvider(
            $serviceMock,
            new OauthProtocolResponseBuilder(),
            $this->createConfiguredMock(AgenticCommerceConfig::class, ['isConfigured' => true]),
            $agenticCommerceClient,
            new ZedCallFailureLogger($errorLogger ?? $this->createMock(ErrorLoggerInterface::class)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeDocument(Response $response): array
    {
        return json_decode((string)$response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
