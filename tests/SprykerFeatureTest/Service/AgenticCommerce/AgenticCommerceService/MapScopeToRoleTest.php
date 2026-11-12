<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group MapScopeToRoleTest
 * Add your own group annotations below this line
 */
class MapScopeToRoleTest extends Unit
{
    protected AgenticCommerceServiceTester $tester;

    /**
     * @dataProvider scopeRoleDataProvider
     */
    public function testGivenADelegatedScopeWhenMappedThenTheRoleFollowsTheDocumentedRule(string $scope, string $expectedRole): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->getService();

        // Act
        $role = $agenticCommerceService->mapScopeToRole($scope);

        // Assert
        $this->assertSame($expectedRole, $role);
    }

    /**
     * @return array<string, list<string>>
     */
    public function scopeRoleDataProvider(): array
    {
        return [
            'catalog read' => ['catalog:read', 'ROLE_SCOPE_CATALOG_READ'],
            'cart write' => ['cart:write', 'ROLE_SCOPE_CART_WRITE'],
            'order place' => ['order:place', 'ROLE_SCOPE_ORDER_PLACE'],
            'hyphenated resource and action' => ['agentic-commerce:principal-read', 'ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ'],
            'hyphenated resource' => ['a-b:c', 'ROLE_SCOPE_A__B_C'],
            'hyphenated action' => ['a:b-c', 'ROLE_SCOPE_A_B__C'],
        ];
    }

    public function testGivenTwoScopesThatDifferOnlyInWhichSeparatorIsWhereWhenMappedThenTheirRolesDiffer(): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->getService();

        // Act
        $hyphenatedResourceRole = $agenticCommerceService->mapScopeToRole('a-b:c');
        $hyphenatedActionRole = $agenticCommerceService->mapScopeToRole('a:b-c');

        // Assert
        $this->assertNotSame($hyphenatedResourceRole, $hyphenatedActionRole);
    }

    /**
     * @dataProvider scopeOutsideTheDelegatedShapeDataProvider
     */
    public function testGivenAScopeOutsideTheDelegatedShapeWhenMappedThenTheRoleIsOneNoDelegatedAccessUserHolds(string $scope, string $expectedRole): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->getService();

        // Act
        $role = $agenticCommerceService->mapScopeToRole($scope);
        $isDelegatedAccessScope = $agenticCommerceService->isDelegatedAccessScope($scope);

        // Assert
        $this->assertSame($expectedRole, $role);
        $this->assertFalse($isDelegatedAccessScope);
    }

    /**
     * @return array<string, list<string>>
     */
    public function scopeOutsideTheDelegatedShapeDataProvider(): array
    {
        return [
            'storefront user type' => ['customer', 'ROLE_SCOPE_CUSTOMER'],
            'empty' => ['', 'ROLE_SCOPE_'],
            'two colons' => ['cart:write:all', 'ROLE_SCOPE_CART_WRITE_ALL'],
        ];
    }

    /**
     * @dataProvider scopeShapeDataProvider
     */
    public function testGivenAScopeWhenCheckingItsShapeThenOnlyResourceColonActionIsADelegatedScope(string $scope, bool $expectedIsDelegatedAccessScope): void
    {
        // Act
        $isDelegatedAccessScope = $this->tester->getService()->isDelegatedAccessScope($scope);

        // Assert
        $this->assertSame($expectedIsDelegatedAccessScope, $isDelegatedAccessScope);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function scopeShapeDataProvider(): array
    {
        return [
            'resource and action' => ['cart:write', true],
            'hyphenated words' => ['agentic-commerce:principal-read', true],
            'storefront user type' => ['customer', false],
            'storefront user type with underscore' => ['company_user', false],
            'hyphen instead of colon' => ['catalog-read', false],
            'upper case' => ['Cart:write', false],
            'two colons' => ['cart:write:all', false],
            'trailing hyphen' => ['cart-:write', false],
            'dot' => ['cart.write', false],
            'empty' => ['', false],
        ];
    }
}
