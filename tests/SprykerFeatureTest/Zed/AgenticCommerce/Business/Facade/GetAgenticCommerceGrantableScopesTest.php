<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakeScopeFilterPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group GetAgenticCommerceGrantableScopesTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceGrantableScopesTest extends Unit
{
    protected const string PRINCIPAL_REFERENCE = 'DE--grantable-scopes';

    protected const string UNREQUESTED_SCOPE = 'test:scope-c';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenNoScopeFilterWhenGettingGrantableScopesThenTheRequestedScopesAreReturned(): void
    {
        // Arrange
        $this->tester->setScopeFilterPlugins([]);

        // Act
        $grantableScopesResponseTransfer = $this->tester->getFacade()->getAgenticCommerceGrantableScopes(
            $this->createGrantableScopesRequest([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B]),
        );

        // Assert
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B], $grantableScopesResponseTransfer->getGrantableScopes());
    }

    public function testGivenTwoScopeFiltersEachRemovingOneOfTheTwoRequestedScopesWhenGettingGrantableScopesThenNoScopeIsGrantable(): void
    {
        // Arrange
        $this->tester->setScopeFilterPlugins([
            new FakeScopeFilterPlugin([AgenticCommerceHelper::TEST_SCOPE_A]),
            new FakeScopeFilterPlugin([AgenticCommerceHelper::TEST_SCOPE_B]),
        ]);

        // Act
        $grantableScopesResponseTransfer = $this->tester->getFacade()->getAgenticCommerceGrantableScopes(
            $this->createGrantableScopesRequest([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B]),
        );

        // Assert
        $this->assertSame([], $grantableScopesResponseTransfer->getGrantableScopes());
    }

    public function testGivenAScopeFilterThatAddsAScopeWhenGettingGrantableScopesThenTheAddedScopeIsDropped(): void
    {
        // Arrange
        $this->tester->setScopeFilterPlugins([
            new FakeScopeFilterPlugin([AgenticCommerceHelper::TEST_SCOPE_B], [static::UNREQUESTED_SCOPE]),
        ]);

        // Act
        $grantableScopesResponseTransfer = $this->tester->getFacade()->getAgenticCommerceGrantableScopes(
            $this->createGrantableScopesRequest([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B]),
        );

        // Assert
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $grantableScopesResponseTransfer->getGrantableScopes());
    }

    public function testGivenProvidedAndConfiguredScopesWhenGettingGrantableScopesThenTheScopeTransfersCarryTheGlossaryKeysInTheOrderOfTheGrantableScopes(): void
    {
        // Arrange
        $this->tester->setScopeFilterPlugins([]);
        $this->tester->setScopeProviderPlugins([
            $this->tester->createScopeProviderPlugin(['catalog:read' => 'provided.catalog', 'cart:write' => 'provided.cart']),
        ]);
        $this->tester->setSupportedScopes(['legacy:read']);

        // Act
        $grantableScopesResponseTransfer = $this->tester->getFacade()->getAgenticCommerceGrantableScopes(
            $this->createGrantableScopesRequest(['legacy:read', 'cart:write', 'catalog:read']),
        );

        // Assert
        $this->assertSame(['legacy:read', 'cart:write', 'catalog:read'], $grantableScopesResponseTransfer->getGrantableScopes());
        $this->assertSame(
            [
                ['legacy:read', 'agentic_commerce.scope.legacy.read'],
                ['cart:write', 'provided.cart'],
                ['catalog:read', 'provided.catalog'],
            ],
            array_map(
                static fn (AgenticCommerceScopeTransfer $scopeTransfer): array => [$scopeTransfer->getNameOrFail(), $scopeTransfer->getGlossaryKeyOrFail()],
                $grantableScopesResponseTransfer->getScopes()->getArrayCopy(),
            ),
        );
    }

    /**
     * @param list<string> $requestedScopes
     */
    protected function createGrantableScopesRequest(array $requestedScopes): AgenticCommerceGrantableScopesRequestTransfer
    {
        return (new AgenticCommerceGrantableScopesRequestTransfer())
            ->setPrincipal((new AgenticCommercePrincipalTransfer())
                ->setPrincipalType(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
                ->setPrincipalReference(static::PRINCIPAL_REFERENCE)
                ->setCustomerReference(static::PRINCIPAL_REFERENCE))
            ->setRequestedScopes($requestedScopes);
    }
}
