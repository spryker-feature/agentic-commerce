<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Test\Unit;
use Spryker\Glue\GlueApplication\Compatibility\EventSubscriber\GlueRestPluginStackSubscriber;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceGlueRestPluginStackOptOutSubscriber;
use Symfony\Bundle\SecurityBundle\EventListener\FirewallListener;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group EventSubscriber
 * @group AgenticCommerceGlueRestPluginStackOptOutSubscriberTest
 * Add your own group annotations below this line
 */
class AgenticCommerceGlueRestPluginStackOptOutSubscriberTest extends Unit
{
    /**
     * @uses \Spryker\Glue\GlueApplication\Compatibility\EventSubscriber\GlueRestPluginStackSubscriber::ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS
     */
    protected const string ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS = '_skip_glue_rest_plugin_stacks';

    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected const string STOREFRONT_FIREWALL_NAME = 'main';

    protected const string USER_CHECKER = 'security.user_checker';

    public function testGivenARequestOnTheDelegatedAccessFirewallWhenItArrivesThenItOptsOutOfTheGlueRestPluginStacks(): void
    {
        // Arrange
        $request = Request::create(static::PROTECTED_PATH);

        // Act
        $this->createSubscriber((new AgenticCommerceConfig())->getFirewallName())->onKernelRequest($this->createRequestEvent($request));

        // Assert
        $this->assertTrue($request->attributes->getBoolean(static::ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS));
    }

    public function testGivenARequestOnTheStorefrontFirewallWhenItArrivesThenTheGlueRestPluginStacksStillRun(): void
    {
        // Arrange
        $request = Request::create(static::PROTECTED_PATH);

        // Act
        $this->createSubscriber(static::STOREFRONT_FIREWALL_NAME)->onKernelRequest($this->createRequestEvent($request));

        // Assert
        $this->assertFalse($request->attributes->has(static::ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS));
    }

    public function testGivenARequestOutsideEveryFirewallWhenItArrivesThenTheGlueRestPluginStacksStillRun(): void
    {
        // Arrange
        $request = Request::create(static::PROTECTED_PATH);

        // Act
        $this->createSubscriber(null)->onKernelRequest($this->createRequestEvent($request));

        // Assert
        $this->assertFalse($request->attributes->has(static::ATTRIBUTE_SKIP_GLUE_REST_PLUGIN_STACKS));
    }

    public function testGivenTheSubscriberWhenItsEventsAreReadThenItListensToTheRequestAfterTheFirewallAndBeforeTheGlueRestPluginStack(): void
    {
        // Arrange
        $firewallPriority = FirewallListener::getSubscribedEvents()[KernelEvents::REQUEST][1][1];
        $glueRestPluginStackPriority = GlueRestPluginStackSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];

        // Act
        $optOutPriority = AgenticCommerceGlueRestPluginStackOptOutSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];

        // Assert
        $this->assertLessThan($firewallPriority, $optOutPriority);
        $this->assertGreaterThan($glueRestPluginStackPriority, $optOutPriority);
    }

    protected function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    protected function createSubscriber(?string $firewallName): AgenticCommerceGlueRestPluginStackOptOutSubscriber
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getFirewallConfig')->willReturn(
            $firewallName === null ? null : new FirewallConfig($firewallName, static::USER_CHECKER),
        );

        return new AgenticCommerceGlueRestPluginStackOptOutSubscriber($securityMock, new AgenticCommerceConfig());
    }
}
