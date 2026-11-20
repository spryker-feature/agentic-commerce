<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AgenticCommerceGlueRestPluginStackOptOutSubscriber implements EventSubscriberInterface
{
    protected const int PRIORITY = 6;

    /**
     * @uses \Spryker\Glue\GlueApplication\Compatibility\EventSubscriber\GlueRestPluginStackSubscriber::ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS
     */
    protected const string ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS = '_skip_glue_rest_plugin_stacks';

    public function __construct(
        protected Security $security,
        protected AgenticCommerceConfig $agenticCommerceConfig
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

        if ($this->security->getFirewallConfig($request)?->getName() !== $this->agenticCommerceConfig->getFirewallName()) {
            return;
        }

        $request->attributes->set(static::ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS, true);
    }
}
