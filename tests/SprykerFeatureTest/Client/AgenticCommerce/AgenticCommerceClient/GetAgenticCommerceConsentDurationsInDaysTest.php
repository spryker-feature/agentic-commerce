<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClient;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group GetAgenticCommerceConsentDurationsInDaysTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceConsentDurationsInDaysTest extends Unit
{
    protected AgenticCommerceClientTester $tester;

    public function testGivenTheSharedConsentDurationsWhenTheClientIsAskedThenItReturnsThemInOrder(): void
    {
        // Arrange
        $expectedConsentDurationsInDays = (new SharedAgenticCommerceConfig())->getConsentDurationsInDays();

        // Act
        $consentDurationsInDays = (new AgenticCommerceClient())->getAgenticCommerceConsentDurationsInDays();

        // Assert
        $this->assertSame($expectedConsentDurationsInDays, $consentDurationsInDays);
        $this->assertNotSame([], $consentDurationsInDays);
    }
}
