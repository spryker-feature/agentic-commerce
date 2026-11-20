<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Logger\AgenticCommerceDenialAuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AgenticCommerceDenialAuditSubscriber implements EventSubscriberInterface
{
    protected const array DENIAL_STATUS_CODES = [
        Response::HTTP_UNAUTHORIZED,
        Response::HTTP_FORBIDDEN,
    ];

    public function __construct(
        protected Security $security,
        protected AgenticCommerceDenialAuditLogger $agenticCommerceDenialAuditLogger,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $responseEvent): void
    {
        if (!$responseEvent->isMainRequest()) {
            return;
        }

        $response = $responseEvent->getResponse();

        if (!in_array($response->getStatusCode(), static::DENIAL_STATUS_CODES, true)) {
            return;
        }

        $request = $responseEvent->getRequest();

        if ($this->security->getFirewallConfig($request)?->getName() !== $this->agenticCommerceConfig->getFirewallName()) {
            return;
        }

        $this->agenticCommerceDenialAuditLogger->logDenial($request, $response, $this->security->getUser());
    }
}
