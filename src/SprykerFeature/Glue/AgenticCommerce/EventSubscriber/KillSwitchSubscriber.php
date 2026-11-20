<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilderInterface;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceRequestMatcher;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KillSwitchSubscriber implements EventSubscriberInterface
{
    protected const int PRIORITY = 20;

    protected const int RETRY_AFTER_SECONDS = 300;

    protected const string PATH_PREFIX_METADATA = '/.well-known/';

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected AgenticCommerceRequestMatcher $agenticCommerceRequestMatcher,
        protected OauthProtocolResponseBuilderInterface $oauthProtocolResponseBuilder,
        protected StoreClientInterface $storeClient,
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', static::PRIORITY],
        ];
    }

    public function onKernelRequest(RequestEvent $requestEvent): void
    {
        if (!$requestEvent->isMainRequest() || !$this->isSwitchedRequest($requestEvent->getRequest())) {
            return;
        }

        if ($this->isAgenticCommerceEnabled($requestEvent->getRequest(), $this->storeClient->getCurrentStore()->getNameOrFail())) {
            return;
        }

        $requestEvent->setResponse($this->oauthProtocolResponseBuilder->createDelegatedAccessDisabledResponse(static::RETRY_AFTER_SECONDS));
    }

    protected function isAgenticCommerceEnabled(Request $request, string $storeName): bool
    {
        if (!$this->isIssuingRequest($request)) {
            return $this->agenticCommerceClient->isAgenticCommerceEnabled($storeName);
        }

        try {
            return $this->agenticCommerceClient->isAgenticCommerceEnabledStrict($storeName);
        } catch (AgenticCommerceStorageUnavailableException) {
            return false;
        }
    }

    protected function isIssuingRequest(Request $request): bool
    {
        $path = rawurldecode($request->getPathInfo());

        return $request->isMethod(Request::METHOD_POST)
            && ($path === AgenticCommerceConfig::TOKEN_ENDPOINT_PATH || $path === AgenticCommerceConfig::REGISTRATION_ENDPOINT_PATH);
    }

    protected function isSwitchedRequest(Request $request): bool
    {
        $path = rawurldecode($request->getPathInfo());

        if ($path === AgenticCommerceConfig::REVOCATION_ENDPOINT_PATH || str_starts_with($path, static::PATH_PREFIX_METADATA)) {
            return false;
        }

        if ($path === AgenticCommerceConfig::TOKEN_ENDPOINT_PATH || $path === AgenticCommerceConfig::REGISTRATION_ENDPOINT_PATH) {
            return $request->isMethod(Request::METHOD_POST);
        }

        return $this->agenticCommerceRequestMatcher->isDelegatedResourceRequest($request);
    }
}
