<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceGlueRestPluginStackOptOutSubscriber;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceMcpToolScopeSubscriber;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Bundle\SecurityBundle\EventListener\FirewallListener;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group EventSubscriber
 * @group AgenticCommerceMcpToolScopeSubscriberTest
 * Add your own group annotations below this line
 */
class AgenticCommerceMcpToolScopeSubscriberTest extends Unit
{
    public function testGivenTheSubscriberWhenItsEventsAreReadThenItListensToTheRequestAfterTheFirewallAndTheGlueRestPluginStackOptOut(): void
    {
        // Arrange
        $firewallPriority = FirewallListener::getSubscribedEvents()[KernelEvents::REQUEST][1][1];
        $optOutPriority = AgenticCommerceGlueRestPluginStackOptOutSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];

        // Act
        $mcpToolScopePriority = AgenticCommerceMcpToolScopeSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];

        // Assert
        $this->assertLessThan($firewallPriority, $mcpToolScopePriority);
        $this->assertLessThan($optOutPriority, $mcpToolScopePriority);
    }

    public function testGivenAToolCallWithoutADeclaredScopeWhenItArrivesThenItIsRefusedWithAccessDenied(): void
    {
        // Arrange
        $event = $this->createMcpRequestEvent(['method' => 'tools/call', 'params' => ['name' => 'undeclared_tool']]);

        // Assert
        $this->expectException(AccessDeniedException::class);

        // Act
        $this->createSubscriber()->onKernelRequest($event);
    }

    public function testGivenAToolCallWhoseToolNameIsNotAStringWhenItArrivesThenItIsRefusedWithAccessDenied(): void
    {
        // Arrange
        $event = $this->createMcpRequestEvent(['method' => 'tools/call', 'params' => ['name' => ['cart_add']]]);

        // Assert
        $this->expectException(AccessDeniedException::class);

        // Act
        $this->createSubscriber()->onKernelRequest($event);
    }

    public function testGivenAJsonRpcBatchWhenItArrivesThenItIsAnsweredWith400(): void
    {
        // Arrange
        $event = $this->createMcpRequestEvent([['method' => 'tools/call', 'params' => ['name' => 'cart_add']]]);

        // Act
        $this->createSubscriber()->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $event->getResponse()->getStatusCode());
    }

    protected function createSubscriber(): AgenticCommerceMcpToolScopeSubscriber
    {
        $agenticCommerceServiceStub = $this->createStub(AgenticCommerceServiceInterface::class);
        $agenticCommerceServiceStub->method('findProtectedResourceByPath')->willReturn(
            (new AgenticCommerceProtectedResourceTransfer())->setName(AgenticCommerceConfig::RESOURCE_NAME_MCP),
        );
        $agenticCommerceServiceStub->method('getMcpToolScopes')->willReturn(['cart_add' => 'cart:write']);

        return new AgenticCommerceMcpToolScopeSubscriber($agenticCommerceServiceStub, $this->createStub(Security::class));
    }

    /**
     * @param array<mixed> $body
     */
    protected function createMcpRequestEvent(array $body): RequestEvent
    {
        $request = Request::create('/mcp', Request::METHOD_POST, content: (string)json_encode($body));

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
