<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Service\Container\Container;
use Spryker\Shared\EventDispatcher\EventDispatcher;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Plugin\EventDispatcher\AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Plugin
 * @group AgenticCommerceAuthorizeRateLimitEventDispatcherPluginTest
 * Add your own group annotations below this line
 */
class AgenticCommerceAuthorizeRateLimitEventDispatcherPluginTest extends Unit
{
    protected const string IP = '203.0.113.7';

    protected const int BLOCK_SECONDS = 300;

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected EventDispatcher $eventDispatcher;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
    }

    public function testGivenABlockedCallerWhenRequestingTheAuthorizeRouteThenTheErrorPageIsRenderedWithStatus429AndNoRedirect(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('checkAgenticCommerceRateLimit')
            ->with(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_AUTHORIZE, static::IP, null)
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(static::BLOCK_SECONDS));
        $request = $this->createRequest('agentic-commerce/authorize');

        // Act
        $requestEvent = $this->dispatchRequest($request);
        $responseEvent = $this->dispatchResponse($request);

        // Assert
        $view = ($request->attributes->get('_controller'))();
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('@AgenticCommerce/views/authorization-error/authorization-error.twig', $view->getTemplate());
        $this->assertSame('agentic_commerce.authorize.rate_limited', $view->getData()['authorizationMessage']);
        $this->assertNull($requestEvent->getResponse());
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $responseEvent->getResponse()->getStatusCode());
        $this->assertSame((string)static::BLOCK_SECONDS, $responseEvent->getResponse()->headers->get('Retry-After'));
        $this->assertFalse($responseEvent->getResponse()->headers->has('Location'));
    }

    public function testGivenACallerWithinTheLimitWhenRequestingTheAuthorizeRouteThenTheRouteIsUntouched(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('checkAgenticCommerceRateLimit')
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(false));
        $request = $this->createRequest('agentic-commerce/authorize');
        $request->attributes->set('_controller', 'original');

        // Act
        $this->dispatchRequest($request);
        $responseEvent = $this->dispatchResponse($request);

        // Assert
        $this->assertSame('original', $request->attributes->get('_controller'));
        $this->assertSame(Response::HTTP_OK, $responseEvent->getResponse()->getStatusCode());
    }

    public function testGivenABlockedCallerWhenTheControllerFailsThenTheErrorStatusStays(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('checkAgenticCommerceRateLimit')
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(static::BLOCK_SECONDS));
        $request = $this->createRequest('agentic-commerce/authorize');
        $this->dispatchRequest($request);
        $responseEvent = new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response('', Response::HTTP_INTERNAL_SERVER_ERROR));

        // Act
        $this->eventDispatcher->dispatch($responseEvent, KernelEvents::RESPONSE);

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $responseEvent->getResponse()->getStatusCode());
        $this->assertFalse($responseEvent->getResponse()->headers->has('Retry-After'));
    }

    public function testGivenAnotherRouteWhenRequestedThenNothingIsCounted(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('checkAgenticCommerceRateLimit');

        // Act
        $this->dispatchRequest($this->createRequest('agentic-commerce/consent'));

        // Assert: expectation on the mock
    }

    public function testGivenThePluginWhenItExtendsTheDispatcherThenItsRequestListenerRunsAfterTheRouter(): void
    {
        // Arrange
        $routerPriority = RouterListener::getSubscribedEvents()[KernelEvents::REQUEST][0][1];

        // Act
        $eventDispatcher = (new AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin())->extend(new EventDispatcher(), new Container());

        // Assert
        $priority = $eventDispatcher->getListenerPriority(KernelEvents::REQUEST, $eventDispatcher->getListeners(KernelEvents::REQUEST)[0]);
        $this->assertLessThan($routerPriority, $priority);
    }

    protected function createRequest(string $routeName): Request
    {
        $request = Request::create('/connect/authorize', server: ['REMOTE_ADDR' => static::IP]);
        $request->attributes->set('_route', $routeName);

        return $request;
    }

    protected function createEventDispatcher(): EventDispatcher
    {
        $plugin = new AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin();
        $plugin->setFactory($this->tester->createFactory(['getAgenticCommerceClient' => $this->agenticCommerceClientMock]));

        return $plugin->extend(new EventDispatcher(), new Container());
    }

    protected function dispatchRequest(Request $request): RequestEvent
    {
        $this->eventDispatcher = $this->createEventDispatcher();
        $requestEvent = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $this->eventDispatcher->dispatch($requestEvent, KernelEvents::REQUEST);

        return $requestEvent;
    }

    protected function dispatchResponse(Request $request): ResponseEvent
    {
        $responseEvent = new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response());
        $this->eventDispatcher->dispatch($responseEvent, KernelEvents::RESPONSE);

        return $responseEvent;
    }
}
