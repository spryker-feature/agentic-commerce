<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Service\Container\Container;
use Spryker\Shared\EventDispatcher\EventDispatcher;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Plugin\EventDispatcher\AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin;
use SprykerFeature\Yves\AgenticCommerce\Plugin\EventDispatcher\KillSwitchEventDispatcherPlugin;
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
 * @group KillSwitchEventDispatcherPluginTest
 * Add your own group annotations below this line
 */
class KillSwitchEventDispatcherPluginTest extends Unit
{
    protected const string TEMPLATE_TEMPORARILY_UNAVAILABLE = '@AgenticCommerce/views/temporarily-unavailable/temporarily-unavailable.twig';

    protected const string STORE_NAME = 'DE';

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected EventDispatcher $eventDispatcher;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
    }

    /**
     * @dataProvider switchedRouteDataProvider
     */
    public function testGivenDelegatedAccessIsDisabledWhenRequestingASwitchedRouteThenTheUnavailablePageIsRenderedWithStatus503(string $routeName): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('isAgenticCommerceEnabled')->willReturn(false);
        $request = $this->createRequest($routeName);

        // Act
        $requestEvent = $this->dispatchRequest($request);
        $responseEvent = $this->dispatchResponse($request);

        // Assert
        $view = ($request->attributes->get('_controller'))();
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(static::TEMPLATE_TEMPORARILY_UNAVAILABLE, $view->getTemplate());
        $this->assertNull($requestEvent->getResponse());
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $responseEvent->getResponse()->getStatusCode());
        $this->assertFalse($responseEvent->getResponse()->headers->has('Location'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function switchedRouteDataProvider(): array
    {
        return [
            'authorize' => ['agentic-commerce/authorize'],
            'consent' => ['agentic-commerce/consent'],
            'cart hand-off' => ['agentic-commerce/cart-handoff'],
        ];
    }

    public function testGivenDelegatedAccessIsSwitchedOffWhileAConsentPageIsOpenWhenTheConsentIsSubmittedThenThe503PageIsShownAndNoConsentIsCreated(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('isAgenticCommerceEnabled')->willReturn(false);
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');
        $isConsentControllerCalled = false;
        $request = $this->createRequest('agentic-commerce/consent', Request::METHOD_POST);
        $request->attributes->set('_controller', function () use (&$isConsentControllerCalled): void {
            $isConsentControllerCalled = true;
        });

        // Act
        $this->dispatchRequest($request);
        $responseEvent = $this->dispatchResponse($request);
        ($request->attributes->get('_controller'))();

        // Assert
        $this->assertFalse($isConsentControllerCalled);
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $responseEvent->getResponse()->getStatusCode());
    }

    public function testGivenDelegatedAccessIsDisabledWhenTheControllerFailsThenTheErrorStatusStays(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('isAgenticCommerceEnabled')->willReturn(false);
        $request = $this->createRequest('agentic-commerce/consent');
        $this->dispatchRequest($request);
        $responseEvent = new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response('', Response::HTTP_INTERNAL_SERVER_ERROR));

        // Act
        $this->eventDispatcher->dispatch($responseEvent, KernelEvents::RESPONSE);

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $responseEvent->getResponse()->getStatusCode());
    }

    public function testGivenDelegatedAccessIsDisabledAndTheCallerIsBlockedWhenRequestingTheAuthorizeRouteThenTheAnswerIs503AndTheRateLimitIsNotChecked(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('isAgenticCommerceEnabled')->willReturn(false);
        $this->agenticCommerceClientMock->expects($this->never())->method('checkAgenticCommerceRateLimit');
        $this->agenticCommerceClientMock->method('checkAgenticCommerceRateLimit')
            ->willReturn((new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(300));
        $request = $this->createRequest('agentic-commerce/authorize');
        $factory = $this->createFactory();
        $killSwitchPlugin = new KillSwitchEventDispatcherPlugin();
        $killSwitchPlugin->setFactory($factory);
        $rateLimitPlugin = new AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin();
        $rateLimitPlugin->setFactory($factory);
        $this->eventDispatcher = $rateLimitPlugin->extend($killSwitchPlugin->extend(new EventDispatcher(), new Container()), new Container());

        // Act
        $this->eventDispatcher->dispatch(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST), KernelEvents::REQUEST);
        $responseEvent = $this->dispatchResponse($request);

        // Assert
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $responseEvent->getResponse()->getStatusCode());
        $this->assertFalse($responseEvent->getResponse()->headers->has('Retry-After'));
    }

    public function testGivenDelegatedAccessIsEnabledWhenRequestingASwitchedRouteThenTheRouteIsUntouched(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('isAgenticCommerceEnabled')->willReturn(true);
        $request = $this->createRequest('agentic-commerce/consent');
        $request->attributes->set('_controller', 'original');

        // Act
        $this->dispatchRequest($request);
        $responseEvent = $this->dispatchResponse($request);

        // Assert
        $this->assertSame('original', $request->attributes->get('_controller'));
        $this->assertSame(Response::HTTP_OK, $responseEvent->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider unswitchedRouteDataProvider
     */
    public function testGivenDelegatedAccessIsDisabledWhenRequestingAnotherRouteThenTheSwitchIsNotRead(string $routeName): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('isAgenticCommerceEnabled');
        $request = $this->createRequest($routeName);
        $request->attributes->set('_controller', 'original');

        // Act
        $this->dispatchRequest($request);

        // Assert
        $this->assertSame('original', $request->attributes->get('_controller'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function unswitchedRouteDataProvider(): array
    {
        return [
            'connected apps' => ['customer/connected-apps'],
            'disconnect' => ['customer/connected-apps/disconnect'],
            'relogin' => ['agentic-commerce/relogin'],
        ];
    }

    public function testGivenASubRequestWhenItReachesASwitchedRouteThenTheSwitchIsNotRead(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('isAgenticCommerceEnabled');
        $request = $this->createRequest('agentic-commerce/consent');

        // Act
        $this->dispatchRequest($request, HttpKernelInterface::SUB_REQUEST);

        // Assert: expectation on the mock
    }

    /**
     * @dataProvider anyRouteDataProvider
     */
    public function testGivenAMainRequestOnAnyRouteWhenItStartsThenTheFailureLatchesOfTheClientAreResetBeforeTheSwitchIsRead(string $routeName): void
    {
        // Arrange
        $calls = [];
        $this->agenticCommerceClientMock->expects($this->once())->method('resetAgenticCommerceFailureLatches')
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'reset';
            });
        $this->agenticCommerceClientMock->method('isAgenticCommerceEnabled')->willReturnCallback(function () use (&$calls): bool {
            $calls[] = 'read';

            return true;
        });

        // Act
        $this->dispatchRequest($this->createRequest($routeName));

        // Assert
        $this->assertSame('reset', $calls[0]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function anyRouteDataProvider(): array
    {
        return [
            'switched' => ['agentic-commerce/consent'],
            'unswitched' => ['customer/connected-apps'],
        ];
    }

    public function testGivenASwitchedRouteWhenTheSwitchIsReadThenTheCurrentStoreIsAsked(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->once())->method('isAgenticCommerceEnabled')
            ->with(static::STORE_NAME)
            ->willReturn(true);

        // Act
        $this->dispatchRequest($this->createRequest('agentic-commerce/consent'));

        // Assert: expectation on the mock
    }

    public function testGivenASubRequestWhenItStartsThenTheFailureLatchesOfTheMainRequestStay(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('resetAgenticCommerceFailureLatches');

        // Act
        $this->dispatchRequest($this->createRequest('agentic-commerce/consent'), HttpKernelInterface::SUB_REQUEST);

        // Assert: expectation on the mock
    }

    public function testGivenThePluginWhenItExtendsTheDispatcherThenItsRequestListenerRunsAfterTheRouterAndBeforeTheRateLimit(): void
    {
        // Arrange
        $routerPriority = RouterListener::getSubscribedEvents()[KernelEvents::REQUEST][0][1];
        $rateLimitEventDispatcher = (new AgenticCommerceAuthorizeRateLimitEventDispatcherPlugin())->extend(new EventDispatcher(), new Container());
        $rateLimitPriority = $rateLimitEventDispatcher->getListenerPriority(KernelEvents::REQUEST, $rateLimitEventDispatcher->getListeners(KernelEvents::REQUEST)[0]);

        // Act
        $eventDispatcher = (new KillSwitchEventDispatcherPlugin())->extend(new EventDispatcher(), new Container());

        // Assert
        $priority = $eventDispatcher->getListenerPriority(KernelEvents::REQUEST, $eventDispatcher->getListeners(KernelEvents::REQUEST)[0]);
        $this->assertLessThan($routerPriority, $priority);
        $this->assertGreaterThan($rateLimitPriority, $priority);
    }

    protected function createRequest(string $routeName, string $method = Request::METHOD_GET): Request
    {
        $request = Request::create('/connect/authorize', $method);
        $request->attributes->set('_route', $routeName);

        return $request;
    }

    protected function createEventDispatcher(): EventDispatcher
    {
        $plugin = new KillSwitchEventDispatcherPlugin();
        $plugin->setFactory($this->createFactory());

        return $plugin->extend(new EventDispatcher(), new Container());
    }

    protected function createFactory(): AgenticCommerceFactory
    {
        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturn((new StoreTransfer())->setName(static::STORE_NAME));

        return $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getStoreClient' => $storeClientMock,
        ]);
    }

    protected function dispatchRequest(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $this->eventDispatcher = $this->createEventDispatcher();
        $requestEvent = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
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
