<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AuthenticatedConsentResetSubscriber implements EventSubscriberInterface
{
    protected const int PRIORITY = 24;

    public function __construct(protected AgenticCommerceClientInterface $agenticCommerceClient)
    {
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
        if (!$requestEvent->isMainRequest()) {
            return;
        }

        $this->agenticCommerceClient->setAuthenticatedConsentUuid(null);
        $this->agenticCommerceClient->resetAgenticCommerceFailureLatches();
    }
}
