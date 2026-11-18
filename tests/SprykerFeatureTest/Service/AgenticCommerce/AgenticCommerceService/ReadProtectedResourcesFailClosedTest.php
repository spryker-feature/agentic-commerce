<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group ReadProtectedResourcesFailClosedTest
 * Add your own group annotations below this line
 */
class ReadProtectedResourcesFailClosedTest extends Unit
{
    protected AgenticCommerceServiceTester $tester;

    /**
     * @dataProvider invalidDeclarationDataProvider
     *
     * @param list<array<string, mixed>> $resources
     */
    public function testGivenAnInvalidDeclarationWhenBuildingTheDelegatedAccessPathPatternThenItCoversOnlyThePublicProtocolEndpoints(array $resources): void
    {
        // Arrange
        $this->declareResources($resources);

        // Act
        $pathPattern = $this->tester->getService()->getDelegatedAccessPathPattern();

        // Assert
        $this->assertSame(1, preg_match('{' . $pathPattern . '}i', '/oauth/token'));
        $this->assertSame(0, preg_match('{' . $pathPattern . '}i', '/mcp'));
        $this->assertSame(0, preg_match('{' . $pathPattern . '}i', '/carts'));
    }

    /**
     * @dataProvider invalidDeclarationDataProvider
     *
     * @param list<array<string, mixed>> $resources
     */
    public function testGivenAnInvalidDeclarationWhenFindingAResourceByPathThenNoneIsFound(array $resources): void
    {
        // Arrange
        $this->declareResources($resources);

        // Act
        $protectedResourceTransfer = $this->tester->getService()->findProtectedResourceByPath('/mcp');

        // Assert
        $this->assertNull($protectedResourceTransfer);
    }

    /**
     * @dataProvider invalidDeclarationDataProvider
     *
     * @param list<array<string, mixed>> $resources
     */
    public function testGivenAnInvalidDeclarationWhenFindingAResourceByMetadataPathThenNoneIsFound(array $resources): void
    {
        // Arrange
        $this->declareResources($resources);

        // Act
        $protectedResourceTransfer = $this->tester->getService()->findProtectedResourceByMetadataPath('mcp');

        // Assert
        $this->assertNull($protectedResourceTransfer);
    }

    /**
     * @dataProvider invalidDeclarationDataProvider
     *
     * @param list<array<string, mixed>> $resources
     */
    public function testGivenAnInvalidDeclarationWhenReadingResourcesFailClosedThenNoResourceIsDeclared(array $resources): void
    {
        // Arrange
        $this->declareResources($resources);

        // Act
        $protectedResourceTransfers = $this->tester->getService()->getProtectedResourcesFailClosed();

        // Assert
        $this->assertSame([], $protectedResourceTransfers);
    }

    public function testGivenAnInvalidDeclarationWhenReadByEveryStorefrontMethodThenTheErrorIsLoggedOnce(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'http://glue.example/mcp', 'scopes' => []],
        ])]);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->tester->mockFactoryMethod('getLogger', $loggerMock);
        $agenticCommerceService = $this->tester->getService();

        // Assert
        $loggerMock->expects($this->once())->method('error')->with($this->stringContains('http://glue.example/mcp'));

        // Act
        $agenticCommerceService->getDelegatedAccessPathPattern();
        $agenticCommerceService->findProtectedResourceByPath('/mcp');
        $agenticCommerceService->findProtectedResourceByMetadataPath('mcp');
        $agenticCommerceService->getProtectedResourcesFailClosed();
    }

    public function testGivenAPluginThatThrowsAnotherExceptionWhenReadingFailClosedThenTheExceptionIsNotSwallowed(): void
    {
        // Arrange
        $protectedResourceProviderPluginMock = $this->createMock(AgenticCommerceProtectedResourceProviderPluginInterface::class);
        $protectedResourceProviderPluginMock->method('getProtectedResources')->willThrowException(new RuntimeException('The resource source is unavailable.'));
        $this->tester->setProtectedResourceProviderPlugins([$protectedResourceProviderPluginMock]);
        $this->tester->mockFactoryMethod('getLogger', $this->createMock(LoggerInterface::class));

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        $this->tester->getService()->getProtectedResourcesFailClosed();
    }

    public function testGivenAnInvalidDeclarationWhenAuthorizationReadsResourcesThenTheConfigurationExceptionIsStillThrown(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'http://glue.example/mcp', 'scopes' => []],
        ])]);
        $this->tester->mockFactoryMethod('getLogger', $this->createMock(LoggerInterface::class));

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $this->tester->getService()->findProtectedResourceByResourceUri('http://glue.example/mcp');
    }

    public function testGivenAValidDeclarationWhenTheStorefrontReadsResourcesThenTheDeclaredResourcesAreReturnedAndNothingIsLogged(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
        ])]);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $this->tester->mockFactoryMethod('getLogger', $loggerMock);

        // Assert
        $loggerMock->expects($this->never())->method('error');

        // Act
        $protectedResourceTransfers = $this->tester->getService()->getProtectedResourcesFailClosed();

        // Assert
        $this->assertSame('mcp', $protectedResourceTransfers[0]->getName());
    }

    /**
     * @return array<string, array{list<array<string, mixed>>}>
     */
    public function invalidDeclarationDataProvider(): array
    {
        return [
            'plain http for a public host' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'http://glue.example/mcp', 'scopes' => []],
            ]],
            'empty name' => [[
                ['name' => '', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
            ]],
            'empty path prefix' => [[
                ['name' => 'mcp', 'pathPrefix' => '', 'resourceUri' => 'https://glue.example', 'scopes' => []],
            ]],
            'duplicate name' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
                ['name' => 'mcp', 'pathPrefix' => '/tools', 'resourceUri' => 'https://glue.example/tools', 'scopes' => []],
            ]],
            'duplicate path prefix' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
                ['name' => 'tools', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://tools.example/mcp', 'scopes' => []],
            ]],
            'duplicate resource URI' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
                ['name' => 'tools', 'pathPrefix' => '/tools', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
            ]],
            'name with upper-case letters' => [[
                ['name' => 'Mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
            ]],
            'path prefix without leading slash' => [[
                ['name' => 'mcp', 'pathPrefix' => 'mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
            ]],
            'path prefix with trailing slash' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp/', 'resourceUri' => 'https://glue.example/mcp/', 'scopes' => []],
            ]],
            'resource URI with a query' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp?a=1', 'scopes' => []],
            ]],
            'resource URI with a fragment' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp#a', 'scopes' => []],
            ]],
            'resource URI with user information' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://user@glue.example/mcp', 'scopes' => []],
            ]],
            'resource URI path differs from the path prefix' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/other', 'scopes' => []],
            ]],
            'resource URI host not in normalized form' => [[
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://GLUE.example/mcp', 'scopes' => []],
            ]],
        ];
    }

    /**
     * @param list<array<string, mixed>> $resources
     */
    protected function declareResources(array $resources): void
    {
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin($resources)]);
        $this->tester->mockFactoryMethod('getLogger', $this->createMock(LoggerInterface::class));
    }
}
