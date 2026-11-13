<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Client\AgenticCommerce\Plugin\ZedRequest\AgenticCommerceZedRequestMetaDataProviderPlugin;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group AgenticCommerceZedRequestMetaDataProviderPluginTest
 * Add your own group annotations below this line
 */
class AgenticCommerceZedRequestMetaDataProviderPluginTest extends Unit
{
    protected const string CONSENT_UUID = 'e0c1b2a3-0000-4000-8000-000000000001';

    protected AgenticCommerceClientTester $tester;

    public function testGivenAnAuthenticatedDelegatedRequestWhenZedRequestMetaDataIsBuiltThenItCarriesTheConsentUuid(): void
    {
        // Arrange
        $this->tester->getClient()->setAuthenticatedConsentUuid(static::CONSENT_UUID);

        // Act
        $metaDataTransfer = (new AgenticCommerceZedRequestMetaDataProviderPlugin())->getRequestMetaData(new QuoteTransfer());

        // Assert
        $this->assertSame(static::CONSENT_UUID, $metaDataTransfer->getConsentUuid());
    }

    public function testGivenNoDelegatedRequestWhenZedRequestMetaDataIsBuiltThenItIsEmpty(): void
    {
        // Arrange
        $this->tester->getClient()->setAuthenticatedConsentUuid(null);

        // Act
        $metaDataTransfer = (new AgenticCommerceZedRequestMetaDataProviderPlugin())->getRequestMetaData(new QuoteTransfer());

        // Assert
        $this->assertNull($metaDataTransfer->getConsentUuid());
    }
}
