<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Test\Unit;
use RuntimeException;
use Spryker\ApiPlatform\EventSubscriber\GlueApiExceptionSubscriber;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceSecurityExceptionSubscriber;
use SprykerFeature\Glue\AgenticCommerce\Exception\AgenticCommerceContextMissingException;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAccessDeniedHandler;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationEntryPoint;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group EventSubscriber
 * @group AgenticCommerceSecurityExceptionSubscriberTest
 * Add your own group annotations below this line
 */
class AgenticCommerceSecurityExceptionSubscriberTest extends Unit
{
    protected const string ISSUER = 'https://glue-storefront.shop.test';

    protected const string PROTECTED_RESOURCE_METADATA_URL = self::ISSUER . '/.well-known/oauth-protected-resource/agentic-commerce';

    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected const string STOREFRONT_FIREWALL_NAME = 'main';

    protected const string USER_CHECKER = 'security.user_checker';

    protected AgenticCommerceGlueTester $tester;

    protected function _before(): void
    {
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
    }

    public function testGivenAnAccessDenialWithoutAuthenticationWhenHandledThenTheResponseIsAChallengePointingToTheMetadata(): void
    {
        // Arrange
        $exceptionEvent = $this->createExceptionEvent(new AccessDeniedException());

        // Act
        $this->createSubscriber(false)->onKernelException($exceptionEvent);

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exceptionEvent->getResponse()?->getStatusCode());
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", scope="agentic-commerce:principal-read"', static::PROTECTED_RESOURCE_METADATA_URL),
            $exceptionEvent->getResponse()->headers->get('WWW-Authenticate'),
        );
    }

    public function testGivenAnAccessDenialOfAScopeRoleOnAnAuthenticatedCallWhenHandledThenTheResponseIsInsufficientScope(): void
    {
        // Arrange
        $accessDeniedException = new AccessDeniedException();
        $accessDeniedException->setAttributes(['ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ']);
        $exceptionEvent = $this->createExceptionEvent($accessDeniedException);

        // Act
        $this->createSubscriber(true)->onKernelException($exceptionEvent);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $exceptionEvent->getResponse()?->getStatusCode());
        $this->assertStringContainsString('error="insufficient_scope"', (string)$exceptionEvent->getResponse()->headers->get('WWW-Authenticate'));
        $this->assertSame('insufficient_scope', json_decode((string)$exceptionEvent->getResponse()->getContent(), true)['error']);
    }

    public function testGivenAnAuthenticationExceptionWhenHandledThenTheResponseIs401Unauthorized(): void
    {
        // Arrange
        $exceptionEvent = $this->createExceptionEvent(new AuthenticationException());

        // Act
        $this->createSubscriber(false)->onKernelException($exceptionEvent);

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exceptionEvent->getResponse()?->getStatusCode());
    }

    public function testGivenAContextMissingExceptionOnTheDelegatedFirewallWhenHandledThenItIsAnswered401InvalidToken(): void
    {
        // Arrange
        $exceptionEvent = $this->createExceptionEvent(new AgenticCommerceContextMissingException());

        // Act
        $this->createSubscriber(true)->onKernelException($exceptionEvent);

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $exceptionEvent->getResponse()?->getStatusCode());
        $this->assertStringContainsString('error="invalid_token"', (string)$exceptionEvent->getResponse()->headers->get('WWW-Authenticate'));
        $this->assertSame('invalid_token', json_decode((string)$exceptionEvent->getResponse()->getContent(), true)['error']);
    }

    public function testGivenAnExceptionThatIsNeitherAnAccessDenialNorAnAuthenticationFailureWhenHandledThenItIsLeftToTheErrorHandling(): void
    {
        // Arrange
        $exceptionEvent = $this->createExceptionEvent(new RuntimeException());

        // Act
        $this->createSubscriber(true)->onKernelException($exceptionEvent);

        // Assert
        $this->assertNull($exceptionEvent->getResponse());
    }

    public function testGivenAnAccessDenialOnAnotherFirewallWhenHandledThenItIsLeftToTheStorefrontErrorHandling(): void
    {
        // Arrange
        $exceptionEvent = $this->createExceptionEvent(new AccessDeniedException());

        // Act
        $this->createSubscriber(false, static::STOREFRONT_FIREWALL_NAME)->onKernelException($exceptionEvent);

        // Assert
        $this->assertNull($exceptionEvent->getResponse());
    }

    public function testGivenTheGlueApiExceptionSubscriberWhenBothAreSubscribedThenThisSubscriberHandlesTheExceptionBeforeItTurnsTheDenialIntoAJsonApiError(): void
    {
        // Arrange
        $glueApiExceptionPriorities = array_column(GlueApiExceptionSubscriber::getSubscribedEvents()[KernelEvents::EXCEPTION], 1);

        // Act
        $priority = AgenticCommerceSecurityExceptionSubscriber::getSubscribedEvents()[KernelEvents::EXCEPTION][1];

        // Assert
        $this->assertGreaterThan(max($glueApiExceptionPriorities), $priority);
    }

    protected function createExceptionEvent(Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent($this->createMock(HttpKernelInterface::class), Request::create(static::PROTECTED_PATH), HttpKernelInterface::MAIN_REQUEST, $throwable);
    }

    protected function createSubscriber(bool $isAuthenticated, ?string $firewallName = null): AgenticCommerceSecurityExceptionSubscriber
    {
        $agenticCommerceConfig = new AgenticCommerceConfig();
        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getToken')->willReturn($isAuthenticated ? new NullToken() : null);
        $securityMock->method('getFirewallConfig')->willReturn(
            new FirewallConfig($firewallName ?? $agenticCommerceConfig->getFirewallName(), static::USER_CHECKER),
        );
        $agenticCommerceService = $this->tester->createAgenticCommerceService([[
            'name' => 'principal',
            'pathPrefix' => '/agentic-commerce',
            'resourceUri' => 'https://glue-storefront.shop.test/agentic-commerce',
            'scopes' => ['agentic-commerce:principal-read'],
        ]]);
        $agenticCommerceAuthenticationChallengeBuilder = new AgenticCommerceAuthenticationChallengeBuilder($agenticCommerceConfig, $agenticCommerceService);

        return new AgenticCommerceSecurityExceptionSubscriber(
            $securityMock,
            new AgenticCommerceAuthenticationEntryPoint($agenticCommerceAuthenticationChallengeBuilder),
            new AgenticCommerceAccessDeniedHandler($agenticCommerceAuthenticationChallengeBuilder, $agenticCommerceService, $securityMock),
            $agenticCommerceAuthenticationChallengeBuilder,
            $agenticCommerceConfig,
        );
    }
}
