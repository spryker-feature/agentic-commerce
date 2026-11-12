<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group FindProtectedResourceTest
 * Add your own group annotations below this line
 */
class FindProtectedResourceTest extends Unit
{
    protected const string MCP_RESOURCE_URI = 'https://glue.example/mcp';

    protected AgenticCommerceServiceTester $tester;

    public function testGivenTwoResourcesWhenFindingByPathThenTheLongestWholeSegmentPrefixWins(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => static::MCP_RESOURCE_URI, 'scopes' => ['catalog:read']],
            ['name' => 'mcp-admin', 'pathPrefix' => '/mcp/admin', 'resourceUri' => 'https://glue.example/mcp/admin', 'scopes' => ['catalog:read']],
        ])]);

        // Act
        $nestedResourceTransfer = $this->tester->getService()->findProtectedResourceByPath('/mcp/admin/tools');
        $plainResourceTransfer = $this->tester->getService()->findProtectedResourceByPath('/mcp');
        $lookalikeResourceTransfer = $this->tester->getService()->findProtectedResourceByPath('/mcpx');

        // Assert
        $this->assertSame('mcp-admin', $nestedResourceTransfer->getName());
        $this->assertSame('mcp', $plainResourceTransfer->getName());
        $this->assertNull($lookalikeResourceTransfer);
    }

    public function testGivenAResourceWhenBuildingItsMetadataUrlThenThePathComesFromTheResourceUriNotFromTheName(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'tools', 'pathPrefix' => '/mcp', 'resourceUri' => static::MCP_RESOURCE_URI, 'scopes' => ['catalog:read']],
        ])]);
        $resourceTransfer = $this->tester->getService()->findProtectedResourceByResourceUri(static::MCP_RESOURCE_URI);

        // Act
        $metadataUrl = $this->tester->getService()->getProtectedResourceMetadataUrl($resourceTransfer);
        $resolvedResourceTransfer = $this->tester->getService()->findProtectedResourceByMetadataPath('mcp');

        // Assert
        $this->assertSame('https://glue.example/.well-known/oauth-protected-resource/mcp', $metadataUrl);
        $this->assertSame('tools', $resolvedResourceTransfer->getName());
        $this->assertNull($this->tester->getService()->findProtectedResourceByMetadataPath('tools'));
    }

    /**
     * @dataProvider invalidResourceDataProvider
     *
     * @param list<array<string, mixed>> $resources
     */
    public function testGivenAnInvalidResourceDeclarationWhenReadingResourcesThenAConfigurationExceptionIsThrown(array $resources): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin($resources)]);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $this->tester->getService()->getProtectedResources();
    }

    /**
     * @return array<string, list<list<array<string, mixed>>>>
     */
    public function invalidResourceDataProvider(): array
    {
        $mcp = ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => ['catalog:read']];

        return [
            'duplicate name' => [[$mcp, ['name' => 'mcp', 'pathPrefix' => '/other', 'resourceUri' => 'https://glue.example/other', 'scopes' => []]]],
            'duplicate resource uri' => [[$mcp, ['name' => 'other', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []]]],
            'name with a slash' => [[['name' => 'a/b', 'pathPrefix' => '/a', 'resourceUri' => 'https://glue.example/a', 'scopes' => []]]],
            'path without leading slash' => [[['name' => 'a', 'pathPrefix' => 'a', 'resourceUri' => 'https://glue.example/a', 'scopes' => []]]],
            'path with trailing slash' => [[['name' => 'a', 'pathPrefix' => '/a/', 'resourceUri' => 'https://glue.example/a/', 'scopes' => []]]],
            'uri path differs from prefix' => [[['name' => 'a', 'pathPrefix' => '/a', 'resourceUri' => 'https://glue.example/b', 'scopes' => []]]],
            'uri that is not absolute' => [[['name' => 'a', 'pathPrefix' => '/a', 'resourceUri' => '/a', 'scopes' => []]]],
            'uri with query' => [[['name' => 'a', 'pathPrefix' => '/a', 'resourceUri' => 'https://glue.example/a?x=1', 'scopes' => []]]],
            'uri with fragment' => [[['name' => 'a', 'pathPrefix' => '/a', 'resourceUri' => 'https://glue.example/a#x', 'scopes' => []]]],
            'uri with a user' => [[['name' => 'a', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://user@glue.example/mcp', 'scopes' => []]]],
            'uri with a user and a password' => [[['name' => 'a', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://user:secret@glue.example/mcp', 'scopes' => []]]],
            'uri with an upper case host' => [[['name' => 'a', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://Glue.example/mcp', 'scopes' => []]]],
            'uri with a trailing dot in the host' => [[['name' => 'a', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example./mcp', 'scopes' => []]]],
            'uri with a host that is not valid' => [[['name' => 'a', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue_1.example/mcp', 'scopes' => []]]],
            'plain http to a public host' => [[['name' => 'a', 'pathPrefix' => '/a', 'resourceUri' => 'http://glue.example/a', 'scopes' => []]]],
            'hyphen scope' => [[['name' => 'a', 'pathPrefix' => '/a', 'resourceUri' => 'https://glue.example/a', 'scopes' => ['catalog-read']]]],
            'prefix above the token endpoint' => [[['name' => 'a', 'pathPrefix' => '/oauth', 'resourceUri' => 'https://glue.example/oauth', 'scopes' => []]]],
            'prefix that is the token endpoint' => [[['name' => 'a', 'pathPrefix' => '/oauth/token', 'resourceUri' => 'https://glue.example/oauth/token', 'scopes' => []]]],
            'prefix that is the registration endpoint' => [[['name' => 'a', 'pathPrefix' => '/oauth/register', 'resourceUri' => 'https://glue.example/oauth/register', 'scopes' => []]]],
            'prefix above the metadata documents' => [[['name' => 'a', 'pathPrefix' => '/.well-known', 'resourceUri' => 'https://glue.example/.well-known', 'scopes' => []]]],
            'prefix under the metadata documents' => [[['name' => 'a', 'pathPrefix' => '/.well-known/oauth-x', 'resourceUri' => 'https://glue.example/.well-known/oauth-x', 'scopes' => []]]],
        ];
    }

    public function testGivenALocalDevelopmentHostWhenReadingResourcesThenPlainHttpIsAccepted(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'contract-probe', 'pathPrefix' => '/agentic-commerce/contract-probe', 'resourceUri' => 'http://glue-storefront.eu.spryker.local/agentic-commerce/contract-probe', 'scopes' => []],
        ])]);

        // Act
        $resourceTransfers = $this->tester->getService()->getProtectedResources();

        // Assert
        $this->assertCount(1, $resourceTransfers);
    }
}
