<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Plugin\EventDispatcher;

use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\EventDispatcher\EventDispatcherInterface;
use Spryker\Shared\EventDispatcherExtension\Dependency\Plugin\EventDispatcherPluginInterface;
use Spryker\Yves\Kernel\AbstractPlugin;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceFramingProtectionEventDispatcherPlugin extends AbstractPlugin implements EventDispatcherPluginInterface
{
    protected const int LISTENER_PRIORITY = -16;

    /**
     * {@inheritDoc}
     * - Adds a response listener for the authorize, consent and re-login pages of delegated access.
     * - Sets `X-Frame-Options: DENY` and replaces the `frame-ancestors` directive of `Content-Security-Policy` with `frame-ancestors 'none'`, keeping every other directive.
     * - Adds `no-store` to `Cache-Control`: the page that returns to the client holds the authorization code.
     * - Leaves the responses of every other page unchanged.
     *
     * @api
     */
    public function extend(EventDispatcherInterface $eventDispatcher, ContainerInterface $container): EventDispatcherInterface
    {
        $eventDispatcher->addListener(KernelEvents::RESPONSE, function (ResponseEvent $responseEvent): void {
            $this->getFactory()->createFramingProtectionHeaderExpander()->expand($responseEvent->getRequest(), $responseEvent->getResponse());
        }, static::LISTENER_PRIORITY);

        return $eventDispatcher;
    }
}
