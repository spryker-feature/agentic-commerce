<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilder;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceRateLimitSubscriber;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\KillSwitchSubscriber;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceRequestMatcher;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group EventSubscriber
 * @group KillSwitchSubscriberTest
 * Add your own group annotations below this line
 */
class KillSwitchSubscriberTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    protected const string EXPECTED_BODY = '{"error":"temporarily_unavailable","error_description":"Delegated access is temporarily disabled."}';

    protected AgenticCommerceGlueTester $tester;

    /**
     * @dataProvider blockedWhileDisabledDataProvider
     */
    public function testGivenDelegatedAccessIsDisabledWhenADelegatedEndpointIsCalledThenItAnswers503(
        string $method,
        string $path,
        bool $isDelegatedResource
    ): void {
        // Arrange
        $event = $this->createRequestEvent(Request::create($path, $method));

        // Act
        $this->createSubscriber(isEnabled: false, isDelegatedResource: $isDelegatedResource)->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $event->getResponse()->getStatusCode());
        $this->assertSame('300', $event->getResponse()->headers->get('Retry-After'));
        $this->assertSame(static::EXPECTED_BODY, $event->getResponse()->getContent());
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public function blockedWhileDisabledDataProvider(): array
    {
        return [
            'protected resource' => ['GET', '/agentic-commerce/principal', true],
            'marked operation' => ['POST', '/carts/cart-1/quote-approvals', true],
            'token' => ['POST', '/oauth/token', false],
            'register' => ['POST', '/oauth/register', false],
            'encoded token path' => ['POST', '/oauth/%74oken', false],
        ];
    }

    /**
     * @dataProvider openWhileDisabledDataProvider
     */
    public function testGivenDelegatedAccessIsDisabledWhenRevokeOrMetadataIsCalledThenItStillWorks(string $method, string $path): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('isAgenticCommerceEnabled');
        $event = $this->createRequestEvent(Request::create($path, $method));

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: true)->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function openWhileDisabledDataProvider(): array
    {
        return [
            'revoke' => ['POST', '/oauth/revoke'],
            'metadata' => ['GET', '/.well-known/oauth-protected-resource/agentic-commerce'],
            'authorization server metadata' => ['GET', '/.well-known/oauth-authorization-server'],
        ];
    }

    public function testGivenDelegatedAccessIsDisabledWhenAStorefrontPathIsRequestedThenTheSwitchIsNotRead(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('isAgenticCommerceEnabled');
        $event = $this->createRequestEvent(Request::create('/carts', Request::METHOD_GET));

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: false)->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenAGetToTheTokenPathWhenRequestedThenTheSwitchIsNotRead(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('isAgenticCommerceEnabled');
        $event = $this->createRequestEvent(Request::create('/oauth/token', Request::METHOD_GET));

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: false)->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenDelegatedAccessIsDisabledWhenASubRequestArrivesThenTheSwitchIsNotRead(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('isAgenticCommerceEnabled');
        $event = $this->createRequestEvent(Request::create('/carts/cart-1/quote-approvals', Request::METHOD_POST), HttpKernelInterface::SUB_REQUEST);

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: true)->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenDelegatedAccessIsEnabledWhenADelegatedEndpointIsCalledThenTheRequestPasses(): void
    {
        // Arrange
        $event = $this->createRequestEvent(Request::create('/oauth/token', Request::METHOD_POST));

        // Act
        $this->createSubscriber(isEnabled: true, isDelegatedResource: false)->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenTheCurrentStoreWhenADelegatedEndpointIsCalledThenTheSwitchOfThatStoreIsRead(): void
    {
        // Arrange
        $event = $this->createRequestEvent(Request::create('/oauth/token', 'POST'));
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        // Assert
        $clientMock->expects($this->once())->method('isAgenticCommerceEnabledStrict')->with(static::STORE_NAME)->willReturn(true);

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: false)->onKernelRequest($event);
    }

    public function testGivenRedisUnreachableWhenATokenRequestArrivesThenItIsAnswered503(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('isAgenticCommerceEnabledStrict')->willThrowException(new AgenticCommerceStorageUnavailableException());
        $clientMock->method('isAgenticCommerceEnabled')->willReturn(true);
        $event = $this->createRequestEvent(Request::create('/oauth/token', Request::METHOD_POST));

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: false)->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $event->getResponse()->getStatusCode());
        $this->assertSame(static::EXPECTED_BODY, $event->getResponse()->getContent());
    }

    public function testGivenRedisUnreachableWhenADelegatedResourceRequestArrivesThenItPasses(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('isAgenticCommerceEnabledStrict')->willThrowException(new AgenticCommerceStorageUnavailableException());
        $clientMock->method('isAgenticCommerceEnabled')->willReturn(true);
        $event = $this->createRequestEvent(Request::create('/agentic-commerce/principal', Request::METHOD_GET));

        // Act
        $this->createSubscriberWithClient($clientMock, isDelegatedResource: true)->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenTheSubscriberWhenItsEventsAreReadThenItListensToTheRequestAfterTheRouterAndBeforeTheRateLimit(): void
    {
        // Arrange
        $routerPriority = RouterListener::getSubscribedEvents()[KernelEvents::REQUEST][0][1];
        $rateLimitPriority = AgenticCommerceRateLimitSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];

        // Act
        $subscribedEvents = KillSwitchSubscriber::getSubscribedEvents();

        // Assert
        $this->assertLessThan($routerPriority, $subscribedEvents[KernelEvents::REQUEST][1]);
        $this->assertGreaterThan($rateLimitPriority, $subscribedEvents[KernelEvents::REQUEST][1]);
    }

    protected function createSubscriber(bool $isEnabled, bool $isDelegatedResource): KillSwitchSubscriber
    {
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('isAgenticCommerceEnabled')->willReturn($isEnabled);
        $clientMock->method('isAgenticCommerceEnabledStrict')->willReturn($isEnabled);

        return $this->createSubscriberWithClient($clientMock, $isDelegatedResource);
    }

    protected function createSubscriberWithClient(AgenticCommerceClientInterface $client, bool $isDelegatedResource): KillSwitchSubscriber
    {
        $requestMatcherMock = $this->createMock(AgenticCommerceRequestMatcher::class);
        $requestMatcherMock->method('isDelegatedResourceRequest')->willReturn($isDelegatedResource);

        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturn((new StoreTransfer())->setName(static::STORE_NAME));

        return new KillSwitchSubscriber($client, $requestMatcherMock, new OauthProtocolResponseBuilder(), $storeClientMock);
    }

    protected function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }
}
