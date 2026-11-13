<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Client\AgenticCommerce\Plugin\ZedRequest\AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPlugin;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPluginTest
 * Add your own group annotations below this line
 */
class AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPluginTest extends Unit
{
    protected const string COMPANY_USER_UUID = 'b7d1c2a3-0000-4000-8000-000000000001';

    protected AgenticCommerceClientTester $tester;

    public function testGivenACustomerSessionWithACompanyUserWhenZedRequestMetaDataIsBuiltThenItCarriesTheCompanyUserUuid(): void
    {
        // Arrange
        $this->haveSessionCustomer((new CustomerTransfer())->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID)));

        // Act
        $metaDataTransfer = (new AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPlugin())->getRequestMetaData(new QuoteTransfer());

        // Assert
        $this->assertSame(static::COMPANY_USER_UUID, $metaDataTransfer->getCompanyUserUuid());
    }

    public function testGivenACustomerSessionWithoutACompanyUserWhenZedRequestMetaDataIsBuiltThenItIsEmpty(): void
    {
        // Arrange
        $this->haveSessionCustomer(new CustomerTransfer());

        // Act
        $metaDataTransfer = (new AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPlugin())->getRequestMetaData(new QuoteTransfer());

        // Assert
        $this->assertNull($metaDataTransfer->getCompanyUserUuid());
    }

    public function testGivenNoCustomerSessionWhenZedRequestMetaDataIsBuiltThenItIsEmpty(): void
    {
        // Arrange
        $this->haveSessionCustomer(null);

        // Act
        $metaDataTransfer = (new AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPlugin())->getRequestMetaData(new QuoteTransfer());

        // Assert
        $this->assertNull($metaDataTransfer->getCompanyUserUuid());
    }

    protected function haveSessionCustomer(?CustomerTransfer $customerTransfer): void
    {
        $this->tester->setDependency(
            AgenticCommerceDependencyProvider::CLIENT_CUSTOMER,
            Stub::makeEmpty(CustomerClientInterface::class, ['findCustomerRawData' => $customerTransfer]),
        );
    }
}
