<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group GetAgenticCommerceConsentConstraintFieldsTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceConsentConstraintFieldsTest extends Unit
{
    protected const string CURRENCY_CODE = 'EUR';

    protected const string CONSTRAINT_TYPE = 'order_total_limit';

    protected const string OTHER_CONSTRAINT_TYPE = 'other_limit';

    protected const string NOT_APPLICABLE_CONSTRAINT_TYPE = 'not_applicable_limit';

    protected const string SCOPE_ORDER_PLACE = 'order:place';

    protected const string PRINCIPAL_REFERENCE = 'DE--1';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenApplicableAndNotApplicablePluginsWhenReadingTheFieldsThenOnlyTheApplicableOnesAreReturnedInPluginOrder(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([
            $this->tester->createConstraintPlugin(static::OTHER_CONSTRAINT_TYPE, isApplicable: true, isRequired: false),
            $this->tester->createConstraintPlugin(static::NOT_APPLICABLE_CONSTRAINT_TYPE, isApplicable: false, isRequired: true),
            $this->tester->createConstraintPlugin(static::CONSTRAINT_TYPE, isApplicable: true, isRequired: true),
        ]);

        // Act
        $fieldCollectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentConstraintFields($this->createFieldRequest([static::SCOPE_ORDER_PLACE]));

        // Assert
        $this->assertSame(
            [static::OTHER_CONSTRAINT_TYPE, static::CONSTRAINT_TYPE],
            array_map(
                static fn (AgenticCommerceConstraintFieldTransfer $fieldTransfer): ?string => $fieldTransfer->getType(),
                $fieldCollectionTransfer->getFields()->getArrayCopy(),
            ),
        );
    }

    public function testGivenARequiredPluginWhenReadingTheFieldsThenTheFieldCarriesItsTypeRequirementAndDefinition(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([$this->tester->createConstraintPlugin(static::CONSTRAINT_TYPE, isApplicable: true, isRequired: true)]);

        // Act
        $fieldCollectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentConstraintFields($this->createFieldRequest([static::SCOPE_ORDER_PLACE]));

        // Assert
        $fieldTransfer = $fieldCollectionTransfer->getFields()->offsetGet(0);
        $this->assertSame(static::CONSTRAINT_TYPE, $fieldTransfer->getType());
        $this->assertTrue($fieldTransfer->getIsRequired());
        $this->assertSame(sprintf(AgenticCommerceHelper::CONSTRAINT_LABEL_GLOSSARY_KEY_PATTERN, static::CONSTRAINT_TYPE), $fieldTransfer->getLabelGlossaryKey());
        $this->assertSame(static::CURRENCY_CODE, $fieldTransfer->getCurrencyCode());
    }

    public function testGivenAPluginApplicableOnlyForAScopeWhenTheScopeIsNotRequestedThenNoFieldIsReturned(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([
            $this->tester->createConstraintPlugin(static::CONSTRAINT_TYPE, isApplicable: true, isRequired: true, applicableScope: static::SCOPE_ORDER_PLACE),
        ]);

        // Act
        $fieldCollectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentConstraintFields($this->createFieldRequest([AgenticCommerceHelper::TEST_SCOPE_A]));

        // Assert
        $this->assertCount(0, $fieldCollectionTransfer->getFields());
    }

    /**
     * @param list<string> $scopes
     */
    protected function createFieldRequest(array $scopes): AgenticCommerceConstraintFieldRequestTransfer
    {
        return (new AgenticCommerceConstraintFieldRequestTransfer())
            ->setPrincipal(
                (new AgenticCommercePrincipalTransfer())
                    ->setPrincipalType(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
                    ->setPrincipalReference(static::PRINCIPAL_REFERENCE),
            )
            ->setScopes($scopes)
            ->setStoreName(AgenticCommerceHelper::STORE_NAME)
            ->setCurrencyCode(static::CURRENCY_CODE);
    }
}
