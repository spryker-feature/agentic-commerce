<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceScopeRoleMapper;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceService;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceScopeRoleMapperTest
 * Add your own group annotations below this line
 */
class AgenticCommerceScopeRoleMapperTest extends Unit
{
    /**
     * @dataProvider scopeRoleDataProvider
     *
     * @param list<string> $scopes
     * @param list<string> $expectedRoles
     */
    public function testGivenGrantedScopesAndAPrincipalTypeWhenMappingThenEachWellFormedNonIdentityScopeBecomesAScopeRoleAndThePrincipalTypeAddsItsUserTypeRoles(
        array $scopes,
        string $principalType,
        array $expectedRoles
    ): void {
        // Act
        $roles = (new AgenticCommerceScopeRoleMapper(new AgenticCommerceService()))->mapScopesToRoles($scopes, $principalType);

        // Assert
        $this->assertEqualsCanonicalizing($expectedRoles, $roles);
    }

    /**
     * @return array<string, array{list<string>, string, list<string>}>
     */
    public static function scopeRoleDataProvider(): array
    {
        return [
            'customer with cart write' => [['cart:write'], 'customer', ['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_CART_WRITE', 'ROLE_CUSTOMER']],
            'principal scope with hyphens' => [['agentic-commerce:principal-read'], 'customer', ['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ', 'ROLE_CUSTOMER']],
            'company user gets both user-type roles' => [['order:place'], 'company_user', ['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_ORDER_PLACE', 'ROLE_CUSTOMER', 'ROLE_COMPANY_USER']],
            'storefront identity scopes are ignored' => [['customer', 'company_user', 'catalog:read'], 'customer', ['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_CATALOG_READ', 'ROLE_CUSTOMER']],
            'scope of another shape is ignored' => [['Catalog:Read', 'catalog-read'], 'customer', ['ROLE_AGENTIC_COMMERCE', 'ROLE_CUSTOMER']],
            'unknown principal type gets no user-type role' => [['catalog:read'], 'merchant_user', ['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_CATALOG_READ']],
        ];
    }

    public function testGivenAScopeGrantedTwiceWhenMappingThenItsRoleIsListedOnce(): void
    {
        // Act
        $roles = (new AgenticCommerceScopeRoleMapper(new AgenticCommerceService()))->mapScopesToRoles(['cart:read', 'cart:read'], 'customer');

        // Assert
        $this->assertSame(['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_CART_READ', 'ROLE_CUSTOMER'], $roles);
    }
}
