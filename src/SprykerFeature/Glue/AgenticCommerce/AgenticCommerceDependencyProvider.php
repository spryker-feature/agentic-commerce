<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce;

use Spryker\Glue\Kernel\AbstractBundleDependencyProvider;
use Spryker\Glue\Kernel\Container;

/**
 * @method \SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string SERVICE_AGENTIC_COMMERCE = 'SERVICE_AGENTIC_COMMERCE';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addAgenticCommerceService($container);

        return $container;
    }

    protected function addAgenticCommerceService(Container $container): Container
    {
        $container->set(static::SERVICE_AGENTIC_COMMERCE, function (Container $container) {
            return $container->getLocator()->agenticCommerce()->service();
        });

        return $container;
    }
}
