<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use JsonException;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig as GlueAgenticCommerceConfig;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AgenticCommerceMcpToolScopeSubscriber implements EventSubscriberInterface
{
    protected const int PRIORITY = 4;

    protected const string METHOD_TOOLS_CALL = 'tools/call';

    protected const string KEY_METHOD = 'method';

    protected const string KEY_PARAMS = 'params';

    protected const string KEY_NAME = 'name';

    protected const string JSON_RPC_VERSION = '2.0';

    protected const int JSON_RPC_ERROR_INVALID_REQUEST = -32600;

    protected const string JSON_RPC_ERROR_MESSAGE_BATCH_NOT_SUPPORTED = 'Invalid Request: JSON-RPC batches are not supported.';

    public function __construct(
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected Security $security
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', static::PRIORITY]];
    }

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        $request = $requestEvent->getRequest();

        if (!$requestEvent->isMainRequest()) {
            return;
        }

        $agenticCommerceProtectedResourceTransfer = $this->agenticCommerceService->findProtectedResourceByPath(rawurldecode($request->getPathInfo()));

        if ($agenticCommerceProtectedResourceTransfer?->getName() !== AgenticCommerceConfig::RESOURCE_NAME_MCP) {
            return;
        }

        try {
            $body = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (!is_array($body)) {
            return;
        }

        if (array_is_list($body) && $body !== []) {
            $requestEvent->setResponse($this->createBatchNotSupportedResponse());

            return;
        }

        if (($body[static::KEY_METHOD] ?? null) !== static::METHOD_TOOLS_CALL) {
            return;
        }

        $this->denyAccessUnlessToolGranted($request, $body[static::KEY_PARAMS] ?? null);
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    protected function denyAccessUnlessToolGranted(Request $request, mixed $params): void
    {
        $toolName = is_array($params) ? ($params[static::KEY_NAME] ?? null) : null;
        $toolScopes = $this->agenticCommerceService->getMcpToolScopes();

        if (!is_string($toolName) || !isset($toolScopes[$toolName])) {
            $request->attributes->set(GlueAgenticCommerceConfig::ATTRIBUTE_REFUSED_MCP_TOOL, is_string($toolName) ? $toolName : null);

            throw new AccessDeniedException();
        }

        if ($toolScopes[$toolName] === '') {
            return;
        }

        $role = $this->agenticCommerceService->mapScopeToRole($toolScopes[$toolName]);

        if ($this->security->isGranted($role)) {
            return;
        }

        $request->attributes->set(GlueAgenticCommerceConfig::ATTRIBUTE_REFUSED_MCP_TOOL, $toolName);
        $accessDeniedException = new AccessDeniedException();
        $accessDeniedException->setAttributes([$role]);

        throw $accessDeniedException;
    }

    protected function createBatchNotSupportedResponse(): Response
    {
        return new JsonResponse(
            [
                'jsonrpc' => static::JSON_RPC_VERSION,
                'id' => null,
                'error' => ['code' => static::JSON_RPC_ERROR_INVALID_REQUEST, 'message' => static::JSON_RPC_ERROR_MESSAGE_BATCH_NOT_SUPPORTED],
            ],
            Response::HTTP_BAD_REQUEST,
        );
    }
}
