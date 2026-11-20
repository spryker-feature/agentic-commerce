<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\KillSwitch;

use Spryker\Client\Store\StoreClientInterface;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class KillSwitchRouteGuard implements KillSwitchRouteGuardInterface
{
    public const string REQUEST_ATTRIBUTE_UNAVAILABLE = '_agentic_commerce_unavailable';

    protected const string REQUEST_ATTRIBUTE_ROUTE = '_route';

    protected const string REQUEST_ATTRIBUTE_CONTROLLER = '_controller';

    protected const string TEMPLATE_TEMPORARILY_UNAVAILABLE = '@AgenticCommerce/views/temporarily-unavailable/temporarily-unavailable.twig';

    protected const array SWITCHED_ROUTE_NAMES = [
        AgenticCommerceRouteProviderPlugin::ROUTE_NAME_AUTHORIZE,
        AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CONSENT,
        AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CART_HANDOFF,
    ];

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected StoreClientInterface $storeClient,
    ) {
    }

    public function handleRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();

        if (!$requestEvent->isMainRequest()) {
            return;
        }

        $this->agenticCommerceClient->resetAgenticCommerceFailureLatches();

        if (!in_array($request->attributes->get(static::REQUEST_ATTRIBUTE_ROUTE), static::SWITCHED_ROUTE_NAMES, true)) {
            return;
        }

        if ($this->agenticCommerceClient->isAgenticCommerceEnabled($this->storeClient->getCurrentStore()->getNameOrFail())) {
            return;
        }

        $request->attributes->set(static::REQUEST_ATTRIBUTE_UNAVAILABLE, true);
        $request->attributes->set(static::REQUEST_ATTRIBUTE_CONTROLLER, static fn (): View => new View([], [], static::TEMPLATE_TEMPORARILY_UNAVAILABLE));
    }

    public function handleResponse(ResponseEvent $responseEvent): void
    {
        if ($responseEvent->getRequest()->attributes->get(static::REQUEST_ATTRIBUTE_UNAVAILABLE) !== true || !$responseEvent->getResponse()->isSuccessful()) {
            return;
        }

        $responseEvent->getResponse()->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
