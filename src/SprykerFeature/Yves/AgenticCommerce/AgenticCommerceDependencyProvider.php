<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce;

use Spryker\Yves\Kernel\AbstractBundleDependencyProvider;
use Spryker\Yves\Kernel\Container;

class AgenticCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_AGENTIC_COMMERCE = 'CLIENT_AGENTIC_COMMERCE';

    public const string CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    public const string CLIENT_MULTI_CART = 'CLIENT_MULTI_CART';

    public const string CLIENT_SESSION = 'CLIENT_SESSION';

    public const string CLIENT_STORE = 'CLIENT_STORE';

    public const string CLIENT_CURRENCY = 'CLIENT_CURRENCY';

    public const string CLIENT_GLOSSARY_STORAGE = 'CLIENT_GLOSSARY_STORAGE';

    public const string CLIENT_PERMISSION = 'CLIENT_PERMISSION';

    /**
     * @uses \Spryker\Yves\Router\Plugin\Application\RouterApplicationPlugin::SERVICE_ROUTER
     */
    public const string SERVICE_ROUTER = 'routers';

    public function provideDependencies(Container $container): Container
    {
        $container = parent::provideDependencies($container);
        $container = $this->addAgenticCommerceClient($container);
        $container = $this->addCustomerClient($container);
        $container = $this->addMultiCartClient($container);
        $container = $this->addSessionClient($container);
        $container = $this->addStoreClient($container);
        $container = $this->addCurrencyClient($container);
        $container = $this->addGlossaryStorageClient($container);
        $container = $this->addPermissionClient($container);
        $container = $this->addRouter($container);

        return $container;
    }

    protected function addAgenticCommerceClient(Container $container): Container
    {
        $container->set(static::CLIENT_AGENTIC_COMMERCE, static function (Container $container) {
            return $container->getLocator()->agenticCommerce()->client();
        });

        return $container;
    }

    protected function addCustomerClient(Container $container): Container
    {
        $container->set(static::CLIENT_CUSTOMER, static function (Container $container) {
            return $container->getLocator()->customer()->client();
        });

        return $container;
    }

    protected function addMultiCartClient(Container $container): Container
    {
        $container->set(static::CLIENT_MULTI_CART, static function (Container $container) {
            return $container->getLocator()->multiCart()->client();
        });

        return $container;
    }

    protected function addSessionClient(Container $container): Container
    {
        $container->set(static::CLIENT_SESSION, static function (Container $container) {
            return $container->getLocator()->session()->client();
        });

        return $container;
    }

    protected function addStoreClient(Container $container): Container
    {
        $container->set(static::CLIENT_STORE, static function (Container $container) {
            return $container->getLocator()->store()->client();
        });

        return $container;
    }

    protected function addCurrencyClient(Container $container): Container
    {
        $container->set(static::CLIENT_CURRENCY, static function (Container $container) {
            return $container->getLocator()->currency()->client();
        });

        return $container;
    }

    protected function addGlossaryStorageClient(Container $container): Container
    {
        $container->set(static::CLIENT_GLOSSARY_STORAGE, static function (Container $container) {
            return $container->getLocator()->glossaryStorage()->client();
        });

        return $container;
    }

    protected function addPermissionClient(Container $container): Container
    {
        $container->set(static::CLIENT_PERMISSION, static function (Container $container) {
            return $container->getLocator()->permission()->client();
        });

        return $container;
    }

    protected function addRouter(Container $container): Container
    {
        $container->set(static::SERVICE_ROUTER, static function (Container $container) {
            return $container->getApplicationService(static::SERVICE_ROUTER);
        });

        return $container;
    }
}
