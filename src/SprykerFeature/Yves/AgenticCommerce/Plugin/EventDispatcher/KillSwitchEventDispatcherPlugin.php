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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Fails open: when the kill switch cannot be read, the pages stay available, as in Glue; every grant still passes the Zed validation.
 *
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class KillSwitchEventDispatcherPlugin extends AbstractPlugin implements EventDispatcherPluginInterface
{
    protected const int REQUEST_LISTENER_PRIORITY = 20;

    /**
     * {@inheritDoc}
     * - Adds a request listener that resets the failure latches of the delegated access client at the start of every main request (see `AgenticCommerceClientInterface::resetAgenticCommerceFailureLatches()`).
     * - For the authorize, consent and cart hand-off routes of delegated access, the listener then reads the kill switch of the current store.
     * - Replaces the controller of a request made while delegated access is switched off with the page `@AgenticCommerce/views/temporarily-unavailable/temporarily-unavailable.twig`, which shows the glossary keys `agentic_commerce.temporarily_unavailable.heading` and `agentic_commerce.temporarily_unavailable.text`.
     * - Answers such a request with status `503` when the page rendered, never replacing an error status, so a consent submitted after the switch went off creates no consent.
     * - Leaves every other route, among them the connected apps and their disconnect, and every request while delegated access is switched on unchanged.
     *
     * @api
     */
    public function extend(EventDispatcherInterface $eventDispatcher, ContainerInterface $container): EventDispatcherInterface
    {
        $eventDispatcher->addListener(KernelEvents::REQUEST, function (RequestEvent $requestEvent): void {
            $this->getFactory()->createKillSwitchRouteGuard()->handleRequest($requestEvent);
        }, static::REQUEST_LISTENER_PRIORITY);
        $eventDispatcher->addListener(KernelEvents::RESPONSE, function (ResponseEvent $responseEvent): void {
            $this->getFactory()->createKillSwitchRouteGuard()->handleResponse($responseEvent);
        });

        return $eventDispatcher;
    }
}
