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
 * @group FindAgenticCommerceRequestConsentUuidTest
 * Add your own group annotations below this line
 */
class FindAgenticCommerceRequestConsentUuidTest extends Unit
{
    protected const string CONSENT_UUID = 'e0c1b2a3-0000-4000-8000-000000000001';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenNoGatewayRequestWhenReadingTheRequestConsentUuidThenItIsNull(): void
    {
        // Act
        $consentUuid = $this->tester->getFacade()->findAgenticCommerceRequestConsentUuid();

        // Assert
        $this->assertNull($consentUuid);
    }

    public function testGivenADelegatedGatewayRequestWhenReadingTheRequestConsentUuidThenItIsTheConsentOfTheRequest(): void
    {
        // Arrange
        $this->tester->haveRequestConsentUuid(static::CONSENT_UUID);

        // Act
        $consentUuid = $this->tester->getFacade()->findAgenticCommerceRequestConsentUuid();

        // Assert
        $this->assertSame(static::CONSENT_UUID, $consentUuid);
    }
}
