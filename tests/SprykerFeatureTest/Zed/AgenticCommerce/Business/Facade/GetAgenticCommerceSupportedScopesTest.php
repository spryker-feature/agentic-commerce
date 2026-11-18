<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReader;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group GetAgenticCommerceSupportedScopesTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceSupportedScopesTest extends Unit
{
    protected AgenticCommerceBusinessTester $tester;

    public function testGivenTwoProvidersAndTheConfigKeyWhenReadingSupportedScopesThenTheUnionIsReturnedOnceInOrder(): void
    {
        // Arrange
        $this->tester->setScopeProviderPlugins([
            $this->tester->createScopeProviderPlugin(['catalog:read' => 'first.catalog', 'cart:write' => 'first.cart']),
            $this->tester->createScopeProviderPlugin(['catalog:read' => 'second.catalog', 'cart:read' => 'second.cart']),
        ]);
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, ['cart:read', 'legacy:read']);

        // Act
        $scopeTransfers = $this->tester->getFacade()->getAgenticCommerceSupportedScopes();

        // Assert
        $this->assertSame(['catalog:read', 'cart:write', 'cart:read', 'legacy:read'], array_map(fn (AgenticCommerceScopeTransfer $scopeTransfer): string => $scopeTransfer->getNameOrFail(), $scopeTransfers));
        $this->assertSame('first.catalog', $scopeTransfers[0]->getGlossaryKey());
        $this->assertSame('second.cart', $scopeTransfers[2]->getGlossaryKey());
        $this->assertSame('agentic_commerce.scope.legacy.read', $scopeTransfers[3]->getGlossaryKey());
    }

    /**
     * @dataProvider invalidScopeNameDataProvider
     */
    public function testGivenAProviderWithAnInvalidScopeNameWhenReadingSupportedScopesThenAConfigurationExceptionNamesIt(string $invalidScopeName): void
    {
        // Arrange
        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([$invalidScopeName => 'any.key'])]);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);
        $this->expectExceptionMessageMatches(sprintf('/"%s"/', preg_quote($invalidScopeName, '/')));

        // Act
        $this->tester->getFacade()->getAgenticCommerceSupportedScopes();
    }

    /**
     * @return array<string, list<string>>
     */
    public function invalidScopeNameDataProvider(): array
    {
        return [
            'hyphen instead of colon' => ['catalog-read'],
            'space' => ['cart: write'],
            'slash' => ['cart/write'],
            'upper case' => ['Cart:Write'],
            'storefront user type' => ['customer'],
            'empty' => [''],
        ];
    }

    public function testGivenTwoScopesThatMapToTheSameRoleWhenReadingSupportedScopesThenAConfigurationExceptionNamesTheSecond(): void
    {
        // Arrange
        $agenticCommerceServiceStub = $this->createStub(AgenticCommerceServiceInterface::class);
        $agenticCommerceServiceStub->method('isDelegatedAccessScope')->willReturn(true);
        $agenticCommerceServiceStub->method('mapScopeToRole')->willReturn('ROLE_SCOPE_CART_READ');
        $supportedScopeReader = new SupportedScopeReader(
            [
                $this->tester->createScopeProviderPlugin(['cart:read' => 'first.key']),
                $this->tester->createScopeProviderPlugin(['cart:view' => 'second.key']),
            ],
            $agenticCommerceServiceStub,
            $this->tester->getModuleConfig(),
        );

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);
        $this->expectExceptionMessage('"cart:view"');

        // Act
        $supportedScopeReader->getSupportedScopes();
    }

    public function testGivenAnInvalidScopeInTheDeprecatedConfigKeyWhenReadingSupportedScopesThenAConfigurationExceptionIsThrown(): void
    {
        // Arrange
        $this->tester->setScopeProviderPlugins([]);
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, ['agentic-commerce-test-read']);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);
        $this->expectExceptionMessageMatches('/AgenticCommerceConstants::SUPPORTED_SCOPES.*"agentic-commerce-test-read"/');

        // Act
        $this->tester->getFacade()->getAgenticCommerceSupportedScopes();
    }

    public function testGivenNoProviderAndNoConfigWhenReadingSupportedScopesThenTheListIsEmpty(): void
    {
        // Arrange
        $this->tester->setScopeProviderPlugins([]);
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, []);

        // Act
        $scopeTransfers = $this->tester->getFacade()->getAgenticCommerceSupportedScopes();

        // Assert
        $this->assertSame([], $scopeTransfers);
    }

    public function testGivenAMapperThatReturnsAReservedRoleWhenReadingSupportedScopesThenAConfigurationExceptionIsThrown(): void
    {
        // Arrange
        $agenticCommerceServiceStub = $this->createStub(AgenticCommerceServiceInterface::class);
        $agenticCommerceServiceStub->method('isDelegatedAccessScope')->willReturn(true);
        $agenticCommerceServiceStub->method('mapScopeToRole')->willReturn('ROLE_CUSTOMER');
        $supportedScopeReader = new SupportedScopeReader(
            [$this->tester->createScopeProviderPlugin(['cart:read' => 'any.key'])],
            $agenticCommerceServiceStub,
            $this->tester->getModuleConfig(),
        );

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $supportedScopeReader->getSupportedScopes();
    }
}
