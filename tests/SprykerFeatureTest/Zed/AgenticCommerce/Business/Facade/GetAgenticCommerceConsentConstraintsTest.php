<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group GetAgenticCommerceConsentConstraintsTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceConsentConstraintsTest extends Unit
{
    protected const int LIMIT_AMOUNT = 50000;

    protected const string CURRENCY_CODE = 'EUR';

    protected const string CONSTRAINT_TYPE = 'order_total_limit';

    protected const string RETIRED_CONSTRAINT_TYPE = 'retired_type';

    protected const int RETIRED_VALUE = 1;

    protected const string UNKNOWN_CONSENT_UUID = '00000000-0000-0000-0000-000000000000';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenAStoredConstraintWithAPluginWhenReadingThenItsTypeAndValueAreReturned(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([$this->tester->createConstraintPlugin(static::CONSTRAINT_TYPE, isApplicable: true, isRequired: true)]);
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->haveAgenticCommerceConsentConstraint($consentTransfer, static::CONSTRAINT_TYPE, ['amount' => static::LIMIT_AMOUNT, 'currency' => static::CURRENCY_CODE]);

        // Act
        $constraintTransfers = $this->tester->getFacade()->getAgenticCommerceConsentConstraints($consentTransfer->getUuidOrFail());

        // Assert
        $this->assertCount(1, $constraintTransfers);
        $this->assertSame(static::CONSTRAINT_TYPE, $constraintTransfers[0]->getType());
        $this->assertSame(['amount' => static::LIMIT_AMOUNT, 'currency' => static::CURRENCY_CODE], $constraintTransfers[0]->getValue());
    }

    public function testGivenAConstraintTypeWithoutPluginInStorageWhenReadingThenItIsSkippedAndKeptInStorage(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->haveAgenticCommerceConsentConstraint($consentTransfer, static::RETIRED_CONSTRAINT_TYPE, ['a' => static::RETIRED_VALUE]);
        $this->tester->setConsentConstraintPlugins([]);

        // Act
        $constraintTransfers = $this->tester->getFacade()->getAgenticCommerceConsentConstraints($consentTransfer->getUuidOrFail());

        // Assert
        $this->assertSame([], $constraintTransfers);
        $this->assertSame(1, $this->tester->countConsentConstraintRows($consentTransfer->getIdAgenticCommerceConsentOrFail()));
    }

    public function testGivenAnUnknownConsentUuidWhenReadingThenNoConstraintIsReturned(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([$this->tester->createConstraintPlugin(static::CONSTRAINT_TYPE, isApplicable: true, isRequired: true)]);

        // Act
        $constraintTransfers = $this->tester->getFacade()->getAgenticCommerceConsentConstraints(static::UNKNOWN_CONSENT_UUID);

        // Assert
        $this->assertSame([], $constraintTransfers);
    }
}
