<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceCartHandoffQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientRedirectHostQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentConstraintQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEventQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentLockQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceSalesOrderQuery;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderTotalsQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
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

    public function createAgenticCommerceClientRedirectHostQuery(): SpyAgenticCommerceClientRedirectHostQuery
    {
        return SpyAgenticCommerceClientRedirectHostQuery::create();
    }

    public function createAgenticCommerceConsentQuery(): SpyAgenticCommerceConsentQuery
    {
        return SpyAgenticCommerceConsentQuery::create();
    }

    public function createAgenticCommerceConsentEventQuery(): SpyAgenticCommerceConsentEventQuery
    {
        return SpyAgenticCommerceConsentEventQuery::create();
    }

    public function createAgenticCommerceConsentLockQuery(): SpyAgenticCommerceConsentLockQuery
    {
        return SpyAgenticCommerceConsentLockQuery::create();
    }

    public function createAgenticCommerceConsentConstraintQuery(): SpyAgenticCommerceConsentConstraintQuery
    {
        return SpyAgenticCommerceConsentConstraintQuery::create();
    }

    public function createAgenticCommerceSalesOrderQuery(): SpyAgenticCommerceSalesOrderQuery
    {
        return SpyAgenticCommerceSalesOrderQuery::create();
    }

    public function createSalesOrderTotalsQuery(): SpySalesOrderTotalsQuery
    {
        return SpySalesOrderTotalsQuery::create();
    }

    public function createAgenticCommerceCartHandoffQuery(): SpyAgenticCommerceCartHandoffQuery
    {
        return SpyAgenticCommerceCartHandoffQuery::create();
    }

    public function createAgenticCommerceRetentionReader(): AgenticCommerceRetentionReaderInterface
    {
        return new AgenticCommerceRetentionReader($this);
    }

    public function createAgenticCommerceMapper(): AgenticCommerceMapper
    {
        return new AgenticCommerceMapper();
    }

    public function getOauthClientPropelQuery(): SpyOauthClientQuery
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PROPEL_QUERY_OAUTH_CLIENT);
    }

    public function getAgenticCommerceService(): AgenticCommerceServiceInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::SERVICE_AGENTIC_COMMERCE);
    }
}
