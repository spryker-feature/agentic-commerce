<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeature\Service\AgenticCommerce\Plugin\AgenticCommerce\CommerceAgenticCommerceMcpToolScopeProviderPlugin;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group GetMcpToolScopesTest
 * Add your own group annotations below this line
 */
class GetMcpToolScopesTest extends Unit
{
    protected const string SCOPE_PATTERN = '/^[a-z]+(-[a-z]+)*:[a-z]+(-[a-z]+)*$/D';

    protected AgenticCommerceServiceTester $tester;

    public function testGivenTheCommercePluginWhenGettingTheToolScopesThenEveryToolOfTheCommerceSetHasTheScopeOfItsGroup(): void
    {
        // Arrange
        $this->tester->setMcpToolScopeProviderPlugins([new CommerceAgenticCommerceMcpToolScopeProviderPlugin()]);

        // Act
        $toolScopes = $this->tester->getService()->getMcpToolScopes();

        // Assert
        $this->assertSame([
            'get-company-context' => AgenticCommerceConfig::SCOPE_COMPANY_READ,
            'search-products' => AgenticCommerceConfig::SCOPE_CATALOG_READ,
            'get-product' => AgenticCommerceConfig::SCOPE_CATALOG_READ,
            'list-orders' => AgenticCommerceConfig::SCOPE_CART_READ,
            'get-order' => AgenticCommerceConfig::SCOPE_CART_READ,
            'reorder' => AgenticCommerceConfig::SCOPE_CART_WRITE,
            'list-shopping-lists' => AgenticCommerceConfig::SCOPE_CART_READ,
            'add-shopping-list-to-cart' => AgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-cart' => AgenticCommerceConfig::SCOPE_CART_READ,
            'update-cart' => AgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-checkout-options' => AgenticCommerceConfig::SCOPE_ORDER_PLACE,
            'place-order' => AgenticCommerceConfig::SCOPE_ORDER_PLACE,
            'request-approval' => AgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-approval-status' => AgenticCommerceConfig::SCOPE_CART_READ,
            'create-quote-request' => AgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-cart-handoff-link' => AgenticCommerceConfig::SCOPE_CART_WRITE,
            'health-check' => '',
        ], $toolScopes);
    }

    public function testGivenTheCommercePluginWhenGettingTheToolScopesThenEveryScopeIsEmptyOrAScopeOfTheMcpResource(): void
    {
        // Arrange
        $this->tester->setMcpToolScopeProviderPlugins([new CommerceAgenticCommerceMcpToolScopeProviderPlugin()]);
        $declaredScopes = [
            AgenticCommerceConfig::SCOPE_CATALOG_READ,
            AgenticCommerceConfig::SCOPE_COMPANY_READ,
            AgenticCommerceConfig::SCOPE_CART_READ,
            AgenticCommerceConfig::SCOPE_CART_WRITE,
            AgenticCommerceConfig::SCOPE_ORDER_PLACE,
        ];

        // Act
        $toolScopes = $this->tester->getService()->getMcpToolScopes();

        // Assert
        foreach ($toolScopes as $toolName => $scope) {
            $this->assertTrue($scope === '' || in_array($scope, $declaredScopes, true), $toolName);
            $this->assertTrue($scope === '' || preg_match(static::SCOPE_PATTERN, $scope) === 1, $toolName);
        }
    }

    public function testGivenTwoPluginsWhenGettingTheToolScopesThenTheirToolsAreMerged(): void
    {
        // Arrange
        $this->tester->setMcpToolScopeProviderPlugins([
            $this->tester->createMcpToolScopeProviderPlugin(['first-tool' => AgenticCommerceConfig::SCOPE_CART_READ, 'shared-tool' => AgenticCommerceConfig::SCOPE_CART_READ]),
            $this->tester->createMcpToolScopeProviderPlugin(['second-tool' => '', 'shared-tool' => AgenticCommerceConfig::SCOPE_CART_READ]),
        ]);

        // Act
        $toolScopes = $this->tester->getService()->getMcpToolScopes();

        // Assert
        $this->assertSame(
            ['first-tool' => AgenticCommerceConfig::SCOPE_CART_READ, 'shared-tool' => AgenticCommerceConfig::SCOPE_CART_READ, 'second-tool' => ''],
            $toolScopes,
        );
    }

    public function testGivenTwoPluginsDeclaringTheSameToolWithDifferentScopesWhenGettingTheToolScopesThenAConfigurationExceptionIsThrown(): void
    {
        // Arrange
        $this->tester->setMcpToolScopeProviderPlugins([
            $this->tester->createMcpToolScopeProviderPlugin(['get-cart' => AgenticCommerceConfig::SCOPE_CART_READ]),
            $this->tester->createMcpToolScopeProviderPlugin(['get-cart' => AgenticCommerceConfig::SCOPE_CART_WRITE]),
        ]);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $this->tester->getService()->getMcpToolScopes();
    }
}
