<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AgenticCommerceRateLimitSubscriber implements EventSubscriberInterface
{
    protected const int PRIORITY = 16;

    protected const string PARAMETER_CLIENT_ID = 'client_id';

    protected const string HEADER_RETRY_AFTER = 'Retry-After';

    protected const string ERROR_SLOW_DOWN = 'slow_down';

    protected const string ERROR_DESCRIPTION_TOO_MANY_REQUESTS = 'Too many requests.';

    protected const array FAILED_TOKEN_GRANT_STATUS_CODES = [Response::HTTP_BAD_REQUEST, Response::HTTP_UNAUTHORIZED];

    public function __construct(protected AgenticCommerceClientInterface $agenticCommerceClient)
    {
    }

    /**
     * @return array<string, array{string, int}|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', static::PRIORITY],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();

        if (!$requestEvent->isMainRequest()) {
            return;
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            return;
        }

        $retryAfterSeconds = match (rawurldecode($request->getPathInfo())) {
            AgenticCommerceConfig::REGISTRATION_ENDPOINT_PATH => $this->findBlockedSeconds([[AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_REGISTER, null]], $request),
            AgenticCommerceConfig::TOKEN_ENDPOINT_PATH => $this->findBlockedSeconds([
                [AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE, null],
                [AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, $this->findClientId($request)],
            ], $request),
            default => null,
        };

        if ($retryAfterSeconds === null) {
            return;
        }

        $requestEvent->setResponse(new JsonResponse(
            ['error' => static::ERROR_SLOW_DOWN, 'error_description' => static::ERROR_DESCRIPTION_TOO_MANY_REQUESTS],
            Response::HTTP_TOO_MANY_REQUESTS,
            [static::HEADER_RETRY_AFTER => (string)$retryAfterSeconds],
        ));
    }

    public function onKernelResponse(ResponseEvent $responseEvent): void
    {
        $request = $responseEvent->getRequest();

        if (
            !$responseEvent->isMainRequest()
            || !$request->isMethod(Request::METHOD_POST)
            || rawurldecode($request->getPathInfo()) !== AgenticCommerceConfig::TOKEN_ENDPOINT_PATH
            || !in_array($responseEvent->getResponse()->getStatusCode(), static::FAILED_TOKEN_GRANT_STATUS_CODES, true)
        ) {
            return;
        }

        $this->agenticCommerceClient->incrementAgenticCommerceFailedTokenAttempt($request->getClientIp() ?? '', $this->findClientId($request));
    }

    /**
     * @param list<array{string, string|null}> $typesAndAccounts
     */
    protected function findBlockedSeconds(array $typesAndAccounts, Request $request): ?int
    {
        foreach ($typesAndAccounts as [$type, $account]) {
            $securityCheckAuthResponseTransfer = $this->agenticCommerceClient->checkAgenticCommerceRateLimit($type, $request->getClientIp() ?? '', $account);

            if ($securityCheckAuthResponseTransfer->getIsBlocked()) {
                return (int)$securityCheckAuthResponseTransfer->getBlockedFor();
            }
        }

        return null;
    }

    protected function findClientId(Request $request): ?string
    {
        $clientId = $request->request->all()[static::PARAMETER_CLIENT_ID] ?? null;

        return is_string($clientId) && $clientId !== '' ? $clientId : null;
    }
}
