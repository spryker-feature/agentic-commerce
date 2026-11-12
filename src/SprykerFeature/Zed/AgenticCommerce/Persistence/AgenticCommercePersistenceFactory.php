<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEventQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Zed\AgenticCommerce\Persistence\Propel\Mapper\AgenticCommerceMapper;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface getEntityManager()
 */
class AgenticCommercePersistenceFactory extends AbstractPersistenceFactory
{
    public function createAgenticCommerceClientQuery(): SpyAgenticCommerceClientQuery
    {
        return SpyAgenticCommerceClientQuery::create();
    }

    public function createAgenticCommerceConsentQuery(): SpyAgenticCommerceConsentQuery
    {
        return SpyAgenticCommerceConsentQuery::create();
    }

    public function createAgenticCommerceConsentEventQuery(): SpyAgenticCommerceConsentEventQuery
    {
        return SpyAgenticCommerceConsentEventQuery::create();
    }

    public function createAgenticCommerceMapper(): AgenticCommerceMapper
    {
        return new AgenticCommerceMapper();
    }
}
