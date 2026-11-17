<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Plugin;

use Generated\Shared\Transfer\CustomerTransfer;
use Orm\Zed\Customer\Persistence\SpyCustomerQuery;
use Spryker\Zed\Customer\Dependency\Plugin\CustomerAnonymizerPluginInterface;

class FirstNameWritingCustomerAnonymizerPlugin implements CustomerAnonymizerPluginInterface
{
    public const string WRITTEN_FIRST_NAME = 'written-by-plugin';

    public function process(CustomerTransfer $customerTransfer): void
    {
        SpyCustomerQuery::create()
            ->findOneByIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setFirstName(static::WRITTEN_FIRST_NAME)
            ->save();
    }
}
