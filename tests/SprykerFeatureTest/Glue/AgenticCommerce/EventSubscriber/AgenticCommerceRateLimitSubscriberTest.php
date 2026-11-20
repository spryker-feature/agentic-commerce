<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Client\SecurityBlocker\Exception\SecurityBlockerException;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClient;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig as ClientAgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Client\AgenticCommerce\SecurityBlocker\RateLimitChecker;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceRateLimitSubscriber;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AuthenticatedConsentResetSubscriber;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Bundle\SecurityBundle\EventListener\FirewallListener;
use Symfony\Component\HttpFoundation\JsonResponse;
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
 * @group Glue
 * @group AgenticCommerce
 * @group EventSubscriber
 * @group AgenticCommerceRateLimitSubscriberTest
 * Add your own group annotations below this line
 */
class AgenticCommerceRateLimitSubscriberTest extends Unit
{
    protected const string IP = '203.0.113.7';

    protected const int FAILURE_BLOCK_SECONDS = 900;

    protected const int REGISTER_BLOCK_SECONDS = 3600;

    protected AgenticCommerceGlueTester $tester;

    public function testGivenABlockedCallerWhenPostingToRegisterThenTheResponseIs429WithRetryAfter(): void
    {
        // Arrange
        $subscriber = $this->createSubscriberBlockingType(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_REGISTER, static::REGISTER_BLOCK_SECONDS);
        $event = $this->createRequestEvent(Request::create('/oauth/register', Request::METHOD_POST, server: ['REMOTE_ADDR' => static::IP]));

        // Act
        $subscriber->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $event->getResponse()->getStatusCode());
        $this->assertSame((string)static::REGISTER_BLOCK_SECONDS, $event->getResponse()->headers->get('Retry-After'));
        $this->assertSame('slow_down', json_decode((string)$event->getResponse()->getContent(), true)['error']);
    }

    public function testGivenABlockedCallerWhenPostingToAnEncodedTokenPathThenTheResponseIs429(): void
    {
        // Arrange
        $subscriber = $this->createSubscriberBlockingType(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE, static::FAILURE_BLOCK_SECONDS);
        $event = $this->createRequestEvent(Request::create('/oauth/%74oken', Request::METHOD_POST, server: ['REMOTE_ADDR' => static::IP]));

        // Act
        $subscriber->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $event->getResponse()->getStatusCode());
    }

    public function testGivenAPathOutsideTheThrottledEndpointsWhenRequestedThenTheSubscriberDoesNothing(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('checkAgenticCommerceRateLimit');
        $event = $this->createRequestEvent(Request::create('/carts'));

        // Act
        (new AgenticCommerceRateLimitSubscriber($clientMock))->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenAGetToTheTokenPathWhenRequestedThenNothingIsCounted(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('checkAgenticCommerceRateLimit');
        $event = $this->createRequestEvent(Request::create('/oauth/token', Request::METHOD_GET));

        // Act
        (new AgenticCommerceRateLimitSubscriber($clientMock))->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenASubRequestWhenPostingToRegisterThenNothingIsCounted(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('checkAgenticCommerceRateLimit');
        $event = $this->createRequestEvent(Request::create('/oauth/register', Request::METHOD_POST), HttpKernelInterface::SUB_REQUEST);

        // Act
        (new AgenticCommerceRateLimitSubscriber($clientMock))->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenAFailedTokenGrantWhenTheResponseIsSentThenAnExtraAttemptIsCounted(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->once())->method('incrementAgenticCommerceFailedTokenAttempt')->with(static::IP, 'client-1');
        $subscriber = new AgenticCommerceRateLimitSubscriber($clientMock);
        $request = Request::create('/oauth/token', Request::METHOD_POST, ['client_id' => 'client-1'], server: ['REMOTE_ADDR' => static::IP]);

        // Act
        $subscriber->onKernelResponse($this->createResponseEvent($request, new JsonResponse(['error' => 'invalid_grant'], Response::HTTP_BAD_REQUEST)));

        // Assert
    }

    public function testGivenASuccessfulTokenGrantWhenTheResponseIsSentThenNothingIsCounted(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('incrementAgenticCommerceFailedTokenAttempt');
        $request = Request::create('/oauth/token', Request::METHOD_POST, ['client_id' => 'client-1'], server: ['REMOTE_ADDR' => static::IP]);

        // Act
        (new AgenticCommerceRateLimitSubscriber($clientMock))->onKernelResponse($this->createResponseEvent($request, new JsonResponse(['access_token' => 'x'])));

        // Assert
    }

    public function testGivenAnIpWhoseFailureBucketIsFullWhenPostingWithANewClientIdThenTheResponseIs429(): void
    {
        // Arrange
        $subscriber = $this->createSubscriberBlockingType(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE, static::FAILURE_BLOCK_SECONDS);
        $event = $this->createRequestEvent(Request::create('/oauth/token', Request::METHOD_POST, ['client_id' => 'never-seen-before'], server: ['REMOTE_ADDR' => static::IP]));

        // Act
        $subscriber->onKernelRequest($event);

        // Assert
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $event->getResponse()->getStatusCode());
        $this->assertSame((string)static::FAILURE_BLOCK_SECONDS, $event->getResponse()->headers->get('Retry-After'));
    }

    public function testGivenTheFailureBucketIsBlockedWhenPostingToTheTokenPathThenTheLaterBucketsAreNotChecked(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('checkAgenticCommerceRateLimit')->willReturn(
            (new SecurityCheckAuthResponseTransfer())->setIsBlocked(true)->setBlockedFor(static::FAILURE_BLOCK_SECONDS),
        );
        $event = $this->createRequestEvent(Request::create('/oauth/token', Request::METHOD_POST, ['client_id' => 'any-client'], server: ['REMOTE_ADDR' => static::IP]));

        // Assert
        $clientMock->expects($this->once())->method('checkAgenticCommerceRateLimit');

        // Act
        (new AgenticCommerceRateLimitSubscriber($clientMock))->onKernelRequest($event);
    }

    public function testGivenTheSubscriberWhenItsEventsAreReadThenItListensToTheRequestBetweenRouterAndFirewall(): void
    {
        // Arrange
        $routerPriority = RouterListener::getSubscribedEvents()[KernelEvents::REQUEST][0][1];
        $firewallPriority = FirewallListener::getSubscribedEvents()[KernelEvents::REQUEST][1][1];

        // Act
        $subscribedEvents = AgenticCommerceRateLimitSubscriber::getSubscribedEvents();

        // Assert
        $this->assertLessThan($routerPriority, $subscribedEvents[KernelEvents::REQUEST][1]);
        $this->assertGreaterThan($firewallPriority, $subscribedEvents[KernelEvents::REQUEST][1]);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $subscribedEvents);
    }

    public function testGivenTheSecurityBlockerIsDownWhenAFailedTokenGrantIsHandledThenExactlyOneErrorIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $subscriber = $this->createSubscriberWithFailingSecurityBlocker($loggerMock);
        $request = Request::create('/oauth/token', Request::METHOD_POST, ['client_id' => 'client-1'], server: ['REMOTE_ADDR' => static::IP]);

        // Act
        $subscriber->onKernelRequest($this->createRequestEvent($request));
        $subscriber->onKernelResponse($this->createResponseEvent($request, new JsonResponse(['error' => 'invalid_grant'], Response::HTTP_BAD_REQUEST)));

        // Assert
    }

    public function testGivenTheSecurityBlockerIsDownWhenRegisteringThenTheRequestPassesAndExactlyOneErrorIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $subscriber = $this->createSubscriberWithFailingSecurityBlocker($loggerMock);
        $event = $this->createRequestEvent(Request::create('/oauth/register', Request::METHOD_POST, server: ['REMOTE_ADDR' => static::IP]));

        // Act
        $subscriber->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    public function testGivenTheSecurityBlockerWasDownForAnEarlierRequestWhenANewRequestStartsThenItLogsItsOwnError(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))->method('error');
        $client = $this->createClientWithFailingSecurityBlocker($loggerMock);
        $authenticatedConsentResetSubscriber = new AuthenticatedConsentResetSubscriber($client);
        $rateLimitSubscriber = new AgenticCommerceRateLimitSubscriber($client);

        // Act
        foreach ([1, 2] as $requestNumber) {
            $requestEvent = $this->createRequestEvent(Request::create('/oauth/register', Request::METHOD_POST));
            $authenticatedConsentResetSubscriber->onKernelRequest($requestEvent);
            $rateLimitSubscriber->onKernelRequest($requestEvent);
        }

        // Assert
    }

    public function testGivenTheSecurityBlockerIsDownWhenTheSameRequestIsCheckedTwiceThenOnlyOneErrorIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $rateLimitSubscriber = $this->createSubscriberWithFailingSecurityBlocker($loggerMock);
        $requestEvent = $this->createRequestEvent(Request::create('/oauth/register', Request::METHOD_POST));

        // Act
        $rateLimitSubscriber->onKernelRequest($requestEvent);
        $rateLimitSubscriber->onKernelRequest($requestEvent);

        // Assert
    }

    public function testGivenTheRateLimitIsOffWhenARegisterCallerIsOverTheLimitThenTheRequestPassesAndSecurityBlockerIsNotCalled(): void
    {
        // Arrange
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->expects($this->never())->method('isAccountBlocked');
        $securityBlockerClientMock->expects($this->never())->method('incrementLoginAttemptCount');
        $subscriber = $this->createSubscriberWithSecurityBlocker($securityBlockerClientMock, $this->createMock(LoggerInterface::class), false);
        $event = $this->createRequestEvent(Request::create('/oauth/register', Request::METHOD_POST, server: ['REMOTE_ADDR' => static::IP]));

        // Act
        $subscriber->onKernelRequest($event);

        // Assert
        $this->assertNull($event->getResponse());
    }

    protected function createSubscriberWithFailingSecurityBlocker(LoggerInterface $logger): AgenticCommerceRateLimitSubscriber
    {
        return new AgenticCommerceRateLimitSubscriber($this->createClientWithFailingSecurityBlocker($logger));
    }

    protected function createClientWithFailingSecurityBlocker(LoggerInterface $logger): AgenticCommerceClientInterface
    {
        $securityBlockerClientMock = $this->createMock(SecurityBlockerClientInterface::class);
        $securityBlockerClientMock->method('isAccountBlocked')->willThrowException(new SecurityBlockerException('Redis unavailable'));
        $securityBlockerClientMock->method('incrementLoginAttemptCount')->willThrowException(new SecurityBlockerException('Redis unavailable'));

        return $this->createClientWithSecurityBlocker($securityBlockerClientMock, $logger, true);
    }

    protected function createSubscriberWithSecurityBlocker(
        SecurityBlockerClientInterface $securityBlockerClient,
        LoggerInterface $logger,
        bool $isRateLimitEnabled
    ): AgenticCommerceRateLimitSubscriber {
        return new AgenticCommerceRateLimitSubscriber($this->createClientWithSecurityBlocker($securityBlockerClient, $logger, $isRateLimitEnabled));
    }

    protected function createClientWithSecurityBlocker(
        SecurityBlockerClientInterface $securityBlockerClient,
        LoggerInterface $logger,
        bool $isRateLimitEnabled
    ): AgenticCommerceClientInterface {
        $configMock = $this->createMock(ClientAgenticCommerceConfig::class);
        $configMock->method('isRateLimitEnabled')->willReturn($isRateLimitEnabled);
        $rateLimitChecker = new RateLimitChecker($securityBlockerClient, $configMock, $logger);
        $rateLimitChecker->forgetFailure();
        $client = new AgenticCommerceClient();
        $client->setFactory(Stub::make(AgenticCommerceFactory::class, [
            'createRateLimitChecker' => $rateLimitChecker,
            'createAgenticCommerceRedisWrapper' => $this->createMock(AgenticCommerceRedisWrapperInterface::class),
        ]));

        return $client;
    }

    protected function createSubscriberBlockingType(string $blockedType, int $blockedFor): AgenticCommerceRateLimitSubscriber
    {
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('checkAgenticCommerceRateLimit')->willReturnCallback(
            static fn (string $type): SecurityCheckAuthResponseTransfer => (new SecurityCheckAuthResponseTransfer())
                ->setIsBlocked($type === $blockedType)
                ->setBlockedFor($type === $blockedType ? $blockedFor : null),
        );

        return new AgenticCommerceRateLimitSubscriber($clientMock);
    }

    protected function createRequestEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }

    protected function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
