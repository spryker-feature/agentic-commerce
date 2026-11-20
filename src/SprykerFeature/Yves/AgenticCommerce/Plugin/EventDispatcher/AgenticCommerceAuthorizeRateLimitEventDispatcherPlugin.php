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
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin extends AbstractPlugin implements EventDispatcherPluginInterface
{
    protected const int REQUEST_LISTENER_PRIORITY = 16;

    /**
     * {@inheritDoc}
     * - Adds a request listener for the authorize route of delegated access that counts the request per caller IP.
     * - Replaces the controller of a blocked request with the authorization error page, which shows the glossary key `agentic_commerce.authorize.rate_limited`.
     * - Answers a blocked request with status `429` and `Retry-After`, never with a redirect.
     * - Skips the check for a request the kill switch already answers, and never replaces an error status.
     * - Leaves every other route and every request within the limit unchanged.
     *
     * @api
     */
    public function extend(EventDispatcherInterface $eventDispatcher, ContainerInterface $container): EventDispatcherInterface
    {
        $eventDispatcher->addListener(KernelEvents::REQUEST, function (RequestEvent $requestEvent): void {
            $this->getFactory()->createAuthorizeRateLimiter()->handleRequest($requestEvent);
        }, static::REQUEST_LISTENER_PRIORITY);
        $eventDispatcher->addListener(KernelEvents::RESPONSE, function (ResponseEvent $responseEvent): void {
            $this->getFactory()->createAuthorizeRateLimiter()->handleResponse($responseEvent);
        });

        return $eventDispatcher;
    }
}
