<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceScopeConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group GetAgenticCommerceScopeCollectionTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceScopeCollectionTest extends Unit
{
    protected AgenticCommerceBusinessTester $tester;

    public function testGivenProvidedAndUndeclaredScopeNamesWhenGettingTheScopeCollectionThenEachNameGetsItsGlossaryKeyInRequestOrder(): void
    {
        // Arrange
        $this->tester->setScopeProviderPlugins([
            $this->tester->createScopeProviderPlugin(['catalog:read' => 'provided.catalog', 'cart:write' => 'provided.cart']),
        ]);
        $this->tester->setSupportedScopes([]);

        // Act
        $agenticCommerceScopeCollectionTransfer = $this->tester->getFacade()->getAgenticCommerceScopeCollection(
            (new AgenticCommerceScopeCriteriaTransfer())->setAgenticCommerceScopeConditions(
                (new AgenticCommerceScopeConditionsTransfer())->setNames(['cart:write', 'order:place', 'catalog:read']),
            ),
        );

        // Assert
        $this->assertSame(
            [
                ['cart:write', 'provided.cart'],
                ['order:place', 'agentic_commerce.scope.order.place'],
                ['catalog:read', 'provided.catalog'],
            ],
            array_map(
                static fn (AgenticCommerceScopeTransfer $scopeTransfer): array => [$scopeTransfer->getNameOrFail(), $scopeTransfer->getGlossaryKeyOrFail()],
                $agenticCommerceScopeCollectionTransfer->getScopes()->getArrayCopy(),
            ),
        );
    }

    public function testGivenNoScopeNamesWhenGettingTheScopeCollectionThenItIsEmpty(): void
    {
        // Act
        $agenticCommerceScopeCollectionTransfer = $this->tester->getFacade()->getAgenticCommerceScopeCollection(
            (new AgenticCommerceScopeCriteriaTransfer())->setAgenticCommerceScopeConditions(new AgenticCommerceScopeConditionsTransfer()),
        );

        // Assert
        $this->assertCount(0, $agenticCommerceScopeCollectionTransfer->getScopes());
    }
}
