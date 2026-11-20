<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Provider;

use ApiPlatform\Metadata\Get;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Provider\OauthProtectedResourceMetadataStorefrontProvider;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilder;
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
 * @group OauthProtectedResourceMetadataStorefrontProviderTest
 * Add your own group annotations below this line
 */
class OauthProtectedResourceMetadataStorefrontProviderTest extends Unit
{
    protected const string ISSUER = 'https://glue.example';

    protected const string RESOURCE_PATH = 'mcp';

    protected const string RESOURCE_URI = 'https://glue.example/mcp';

    public function testGivenADeclaredResourceWhenItsMetadataIsRequestedThenTheDocumentNamesTheResourceIssuerScopesAndHeaderBearerMethod(): void
    {
        // Arrange
        $serviceMock = $this->createMock(AgenticCommerceServiceInterface::class);
        $serviceMock->method('findProtectedResourceByMetadataPath')->with(static::RESOURCE_PATH)->willReturn(
            (new AgenticCommerceProtectedResourceTransfer())->setResourceUri(static::RESOURCE_URI)->setScopes(['catalog:read', 'cart:read']),
        );

        // Act
        $response = $this->createProvider($serviceMock, isConfigured: true)->provide(new Get(), ['resourcePath' => static::RESOURCE_PATH]);

        // Assert
        $document = json_decode((string)$response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(static::RESOURCE_URI, $document['resource']);
        $this->assertSame([static::ISSUER], $document['authorization_servers']);
        $this->assertSame(['catalog:read', 'cart:read'], $document['scopes_supported']);
        $this->assertSame(['header'], $document['bearer_methods_supported']);
    }

    public function testGivenAPathOfNoDeclaredResourceWhenItsMetadataIsRequestedThenItAnswers404(): void
    {
        // Arrange
        $serviceMock = $this->createMock(AgenticCommerceServiceInterface::class);
        $serviceMock->method('findProtectedResourceByMetadataPath')->willReturn(null);

        // Act
        $response = $this->createProvider($serviceMock, isConfigured: true)->provide(new Get(), ['resourcePath' => 'unknown']);

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testGivenDelegatedAccessIsNotConfiguredWhenMetadataIsRequestedThenItAnswers404WithoutLookingUpTheResource(): void
    {
        // Arrange
        $serviceMock = $this->createMock(AgenticCommerceServiceInterface::class);

        // Assert
        $serviceMock->expects($this->never())->method('findProtectedResourceByMetadataPath');

        // Act
        $response = $this->createProvider($serviceMock, isConfigured: false)->provide(new Get(), ['resourcePath' => static::RESOURCE_PATH]);

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    protected function createProvider(
        AgenticCommerceServiceInterface $agenticCommerceService,
        bool $isConfigured,
    ): OauthProtectedResourceMetadataStorefrontProvider {
        return new OauthProtectedResourceMetadataStorefrontProvider(
            $agenticCommerceService,
            new OauthProtocolResponseBuilder(),
            $this->createConfiguredMock(AgenticCommerceConfig::class, ['isConfigured' => $isConfigured, 'getIssuer' => static::ISSUER]),
        );
    }
}
