<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeature\Service\AgenticCommerce\Plugin\AgenticCommerce\McpAgenticCommerceProtectedResourceProviderPlugin;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group McpProtectedResourceTest
 * Add your own group annotations below this line
 */
class McpProtectedResourceTest extends Unit
{
    protected const string ISSUER = 'https://glue.example';

    protected const string MCP_RESOURCE_URI = self::ISSUER . '/mcp';

    protected const string MCP_METADATA_PATH = 'mcp';

    protected AgenticCommerceServiceTester $tester;

    public function testGivenAConfiguredMcpResourceUriWhenDeclaringResourcesThenMcpIsDeclaredWithTheFiveScopes(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::MCP_RESOURCE_URI, static::MCP_RESOURCE_URI);

        // Act
        $protectedResourceTransfers = (new McpAgenticCommerceProtectedResourceProviderPlugin())->getProtectedResources();

        // Assert
        $this->assertCount(1, $protectedResourceTransfers);
        $this->assertSame('mcp', $protectedResourceTransfers[0]->getName());
        $this->assertSame('/mcp', $protectedResourceTransfers[0]->getPathPrefix());
        $this->assertSame(static::MCP_RESOURCE_URI, $protectedResourceTransfers[0]->getResourceUri());
        $this->assertSame(
            [
                AgenticCommerceConfig::SCOPE_CATALOG_READ,
                AgenticCommerceConfig::SCOPE_COMPANY_READ,
                AgenticCommerceConfig::SCOPE_CART_READ,
                AgenticCommerceConfig::SCOPE_CART_WRITE,
                AgenticCommerceConfig::SCOPE_ORDER_PLACE,
            ],
            $protectedResourceTransfers[0]->getScopes(),
        );
    }

    public function testGivenNoMcpResourceUriWhenDeclaringResourcesThenNothingIsDeclared(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::MCP_RESOURCE_URI, '');

        // Act
        $protectedResourceTransfers = (new McpAgenticCommerceProtectedResourceProviderPlugin())->getProtectedResources();

        // Assert
        $this->assertSame([], $protectedResourceTransfers);
    }

    public function testGivenTheMcpResourceIsDeclaredWhenLookingUpItsMetadataPathThenTheResourceAndItsMetadataUrlAreFound(): void
    {
        // Arrange
        $this->declareTheMcpResource();
        $service = $this->tester->getService();

        // Act
        $protectedResourceTransfer = $service->findProtectedResourceByMetadataPath(static::MCP_METADATA_PATH);

        // Assert
        $this->assertNotNull($protectedResourceTransfer);
        $this->assertSame(static::MCP_RESOURCE_URI, $protectedResourceTransfer->getResourceUri());
        $this->assertSame(static::ISSUER . '/.well-known/oauth-protected-resource/mcp', $service->getProtectedResourceMetadataUrl($protectedResourceTransfer));
        $this->assertSame(
            ['catalog:read', 'company:read', 'cart:read', 'cart:write', 'order:place'],
            $protectedResourceTransfer->getScopes(),
        );
    }

    public function testGivenTheMcpResourceIsDeclaredWhenBuildingTheAuthorizationServerMetadataThenItAdvertisesTheFiveScopes(): void
    {
        // Arrange
        $this->declareTheMcpResource();

        // Act
        $authorizationServerMetadataTransfer = $this->tester->getService()->buildAuthorizationServerMetadata();

        // Assert
        $this->assertSame(
            ['catalog:read', 'company:read', 'cart:read', 'cart:write', 'order:place'],
            $authorizationServerMetadataTransfer->getScopesSupported(),
        );
    }

    protected function declareTheMcpResource(): void
    {
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
        $this->tester->setConfig(AgenticCommerceConstants::MCP_RESOURCE_URI, static::MCP_RESOURCE_URI);
        $this->tester->setProtectedResourceProviderPlugins([new McpAgenticCommerceProtectedResourceProviderPlugin()]);
    }
}
