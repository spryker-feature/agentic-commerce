<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Store;

use Spryker\Service\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class AgenticCommerceStoreServiceExpander implements AgenticCommerceStoreServiceExpanderInterface
{
    protected const string SERVICE_STORE = 'store';

    public function __construct(protected AgenticCommerceStoreResolverInterface $agenticCommerceStoreResolver)
    {
    }

    public function expand(ContainerInterface $container, Request $request): ContainerInterface
    {
        $storeName = $this->agenticCommerceStoreResolver->findTokenStoreNameWhenRequestNamesNoStore($request);

        if ($storeName === null) {
            return $container;
        }

        $container->set(static::SERVICE_STORE, function () use ($storeName): string {
            return $storeName;
        });

        return $container;
    }
}
