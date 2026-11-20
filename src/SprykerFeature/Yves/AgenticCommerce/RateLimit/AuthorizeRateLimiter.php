<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\RateLimit;

use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Controller\AbstractAuthorizationController;
use SprykerFeature\Yves\AgenticCommerce\KillSwitch\KillSwitchRouteGuard;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class AuthorizeRateLimiter implements AuthorizeRateLimiterInterface
{
    protected const string REQUEST_ATTRIBUTE_ROUTE = '_route';

    protected const string REQUEST_ATTRIBUTE_CONTROLLER = '_controller';

    protected const string REQUEST_ATTRIBUTE_RETRY_AFTER = '_agentic_commerce_retry_after';

    protected const string HEADER_RETRY_AFTER = 'Retry-After';

    protected const string ERROR_RATE_LIMITED = 'rate_limited';

    protected const string GLOSSARY_KEY_RATE_LIMITED = 'agentic_commerce.authorize.rate_limited';

    public function __construct(protected AgenticCommerceClientInterface $agenticCommerceClient)
    {
    }

    public function handleRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();

        if (
            !$requestEvent->isMainRequest()
            || $request->attributes->get(static::REQUEST_ATTRIBUTE_ROUTE) !== AgenticCommerceRouteProviderPlugin::ROUTE_NAME_AUTHORIZE
            || $request->attributes->get(KillSwitchRouteGuard::REQUEST_ATTRIBUTE_UNAVAILABLE) === true
        ) {
            return;
        }

        $securityCheckAuthResponseTransfer = $this->agenticCommerceClient
            ->checkAgenticCommerceRateLimit(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_AUTHORIZE, $request->getClientIp() ?? '', null);

        if (!$securityCheckAuthResponseTransfer->getIsBlocked()) {
            return;
        }

        $request->attributes->set(static::REQUEST_ATTRIBUTE_RETRY_AFTER, (int)$securityCheckAuthResponseTransfer->getBlockedFor());
        $request->attributes->set(static::REQUEST_ATTRIBUTE_CONTROLLER, static fn (): View => new View([
            AbstractAuthorizationController::VIEW_PARAMETER_ERROR => static::ERROR_RATE_LIMITED,
            AbstractAuthorizationController::VIEW_PARAMETER_MESSAGE => static::GLOSSARY_KEY_RATE_LIMITED,
        ], [], AbstractAuthorizationController::TEMPLATE_AUTHORIZATION_ERROR));
    }

    public function handleResponse(ResponseEvent $responseEvent): void
    {
        $retryAfterSeconds = $responseEvent->getRequest()->attributes->get(static::REQUEST_ATTRIBUTE_RETRY_AFTER);

        $response = $responseEvent->getResponse();

        if ($retryAfterSeconds === null || !$response->isSuccessful()) {
            return;
        }

        $response->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS);
        $response->headers->set(static::HEADER_RETRY_AFTER, (string)$retryAfterSeconds);
    }
}
