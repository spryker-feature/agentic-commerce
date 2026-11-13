<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class AgenticCommerceDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_ZED_REQUEST = 'CLIENT_ZED_REQUEST';

    public const string CLIENT_REDIS = 'CLIENT_REDIS';

    public const string CLIENT_SECURITY_BLOCKER = 'CLIENT_SECURITY_BLOCKER';

    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public const string SERVICE_AGENTIC_COMMERCE = 'SERVICE_AGENTIC_COMMERCE';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addZedRequestClient($container);
        $container = $this->addAgenticCommerceService($container);
        $container = $this->addRedisClient($container);
        $container = $this->addSecurityBlockerClient($container);
        $container = $this->addCustomerClient($container);

        return $container;
    }

    protected function addAgenticCommerceService(Container $container): Container
    {
        $container->set(static::SERVICE_AGENTIC_COMMERCE, function (Container $container) {
            return $container->getLocator()->agenticCommerce()->service();
        });

        return $container;
    }

    protected function addRedisClient(Container $container): Container
    {
        $container->set(static::CLIENT_REDIS, function (Container $container) {
            return $container->getLocator()->redis()->client();
        });

        return $container;
    }

    protected function addSecurityBlockerClient(Container $container): Container
    {
        $container->set(static::CLIENT_SECURITY_BLOCKER, function (Container $container) {
            return $container->getLocator()->securityBlocker()->client();
        });

        return $container;
    }

    protected function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }

    protected function addZedRequestClient(Container $container): Container
    {
        $container->set(static::CLIENT_ZED_REQUEST, function (Container $container) {
            return $container->getLocator()->zedRequest()->client();
        });

        return $container;
    }
}
