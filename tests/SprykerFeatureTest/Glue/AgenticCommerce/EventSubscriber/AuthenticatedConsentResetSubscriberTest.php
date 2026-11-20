<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Test\Unit;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AuthenticatedConsentResetSubscriber;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\KillSwitchSubscriber;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Bundle\SecurityBundle\EventListener\FirewallListener;
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
 * @group AuthenticatedConsentResetSubscriberTest
 * Add your own group annotations below this line
 */
class AuthenticatedConsentResetSubscriberTest extends Unit
{
    protected const string CONSENT_UUID = 'e0c1b2a3-0000-4000-8000-000000000001';

    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected AgenticCommerceGlueTester $tester;

    public function testGivenTheHolderIsFilledWhenANewMainRequestStartsThenItIsCleared(): void
    {
        // Arrange
        $this->tester->getClient()->setAuthenticatedConsentUuid(static::CONSENT_UUID);

        // Act
        $this->createSubscriber()->onKernelRequest($this->createRequestEvent(HttpKernelInterface::MAIN_REQUEST));

        // Assert
        $this->assertNull($this->tester->getClient()->findAuthenticatedConsentUuid());
    }

    public function testGivenTheHolderIsFilledWhenASubRequestStartsThenItKeepsTheConsentOfTheMainRequest(): void
    {
        // Arrange
        $this->tester->getClient()->setAuthenticatedConsentUuid(static::CONSENT_UUID);

        // Act
        $this->createSubscriber()->onKernelRequest($this->createRequestEvent(HttpKernelInterface::SUB_REQUEST));

        // Assert
        $this->assertSame(static::CONSENT_UUID, $this->tester->getClient()->findAuthenticatedConsentUuid());
    }

    public function testGivenAMainRequestWhenItStartsThenTheFailureLatchesOfTheClientAreReset(): void
    {
        // Arrange
        $agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $agenticCommerceClientMock->expects($this->once())->method('resetAgenticCommerceFailureLatches');

        // Act
        (new AuthenticatedConsentResetSubscriber($agenticCommerceClientMock))->onKernelRequest($this->createRequestEvent(HttpKernelInterface::MAIN_REQUEST));

        // Assert
    }

    public function testGivenASubRequestWhenItStartsThenTheFailureLatchesOfTheMainRequestStay(): void
    {
        // Arrange
        $agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $agenticCommerceClientMock->expects($this->never())->method('resetAgenticCommerceFailureLatches');

        // Act
        (new AuthenticatedConsentResetSubscriber($agenticCommerceClientMock))->onKernelRequest($this->createRequestEvent(HttpKernelInterface::SUB_REQUEST));

        // Assert
    }

    public function testGivenTheSubscriberWhenItsEventsAreReadThenItListensToTheRequestAboveTheKillSwitchAndTheFirewall(): void
    {
        // Arrange
        $killSwitchPriority = KillSwitchSubscriber::getSubscribedEvents()[KernelEvents::REQUEST][1];
        $firewallPriority = FirewallListener::getSubscribedEvents()[KernelEvents::REQUEST][1][1];

        // Act
        $subscribedEvents = AuthenticatedConsentResetSubscriber::getSubscribedEvents();

        // Assert
        $this->assertGreaterThan($killSwitchPriority, $subscribedEvents[KernelEvents::REQUEST][1]);
        $this->assertGreaterThan($firewallPriority, $subscribedEvents[KernelEvents::REQUEST][1]);
    }

    protected function createSubscriber(): AuthenticatedConsentResetSubscriber
    {
        return new AuthenticatedConsentResetSubscriber($this->tester->getClient());
    }

    protected function createRequestEvent(int $requestType): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create(static::PROTECTED_PATH), $requestType);
    }
}
