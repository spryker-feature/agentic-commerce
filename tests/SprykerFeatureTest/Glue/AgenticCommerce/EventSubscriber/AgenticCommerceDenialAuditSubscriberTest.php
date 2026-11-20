<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\EventSubscriber;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Psr\Log\LoggerInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceDenialAuditSubscriber;
use SprykerFeature\Glue\AgenticCommerce\EventSubscriber\AgenticCommerceMcpToolScopeSubscriber;
use SprykerFeature\Glue\AgenticCommerce\Logger\AgenticCommerceDenialAuditLogger;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAccessDeniedHandler;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group EventSubscriber
 * @group AgenticCommerceDenialAuditSubscriberTest
 * Add your own group annotations below this line
 */
class AgenticCommerceDenialAuditSubscriberTest extends Unit
{
    protected const string MCP_PATH = '/mcp';

    protected const string CLIENT_ID = 'procurement-assistant';

    protected const string CONSENT_UUID = 'consent-1';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const string MCP_TOOL_CART_ADD = 'cart_add';

    protected const string SCOPE_CART_WRITE = 'cart:write';

    protected const string ROLE_SCOPE_CART_WRITE = 'ROLE_SCOPE_CART_WRITE';

    protected const string STOREFRONT_FIREWALL_NAME = 'main';

    protected const string USER_CHECKER = 'security.user_checker';

    protected const string ACTION_ACCESS_DENIED = 'Agentic Commerce Access Denied';

    protected AgenticCommerceGlueTester $tester;

    public function testGivenAnInsufficientScopeDenialOfAnAgentWhenTheResponseIsSentThenTheClientConsentAndMissingScopeAreLogged(): void
    {
        // Arrange
        $request = Request::create(static::MCP_PATH);
        $agenticCommerceApiUser = $this->createAgenticCommerceApiUser();
        $accessDeniedException = new AccessDeniedException();
        $accessDeniedException->setAttributes([static::ROLE_SCOPE_CART_WRITE]);
        $response = $this->createAccessDeniedHandler($agenticCommerceApiUser)->handle($request, $accessDeniedException);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Assert
        $loggerMock->expects($this->once())->method('info')->with(static::ACTION_ACCESS_DENIED, $this->callback(
            fn (array $context): bool => $context['status'] === Response::HTTP_FORBIDDEN
                && $context['error'] === 'insufficient_scope'
                && $context['missing_scope'] === static::SCOPE_CART_WRITE
                && $context['client_id'] === static::CLIENT_ID
                && $context['consent_uuid'] === static::CONSENT_UUID
                && $context['customer_reference'] === static::CUSTOMER_REFERENCE
                && $context['protected_resource'] === SharedAgenticCommerceConfig::RESOURCE_NAME_MCP,
        ));

        // Act
        $this->createSubscriber($loggerMock, $agenticCommerceApiUser)->onKernelResponse($this->createResponseEvent($request, $response));
    }

    public function testGivenAnInvalidTokenDenialWithoutAuthenticatedUserWhenTheResponseIsSentThenThePathIsLoggedWithoutAClient(): void
    {
        // Arrange
        $response = new JsonResponse(
            [AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR => AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN],
            Response::HTTP_UNAUTHORIZED,
        );
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Assert
        $loggerMock->expects($this->once())->method('info')->with(static::ACTION_ACCESS_DENIED, $this->callback(
            fn (array $context): bool => $context['status'] === Response::HTTP_UNAUTHORIZED
                && $context['error'] === AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN
                && $context['path'] === static::MCP_PATH
                && !array_key_exists('client_id', $context),
        ));

        // Act
        $this->createSubscriber($loggerMock, null)->onKernelResponse($this->createResponseEvent(Request::create(static::MCP_PATH), $response));
    }

    public function testGivenAnMcpToolCallRefusedForItsScopeWhenTheResponseIsSentThenTheRefusedToolIsLogged(): void
    {
        // Arrange
        $request = Request::create(
            static::MCP_PATH,
            Request::METHOD_POST,
            content: (string)json_encode(['method' => 'tools/call', 'params' => ['name' => static::MCP_TOOL_CART_ADD]]),
        );
        $agenticCommerceApiUser = $this->createAgenticCommerceApiUser();

        $this->refuseMcpToolCall($request);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Assert
        $loggerMock->expects($this->once())->method('info')->with(static::ACTION_ACCESS_DENIED, $this->callback(
            fn (array $context): bool => $context['mcp_tool'] === static::MCP_TOOL_CART_ADD,
        ));

        // Act
        $this->createSubscriber($loggerMock, $agenticCommerceApiUser)->onKernelResponse(
            $this->createResponseEvent($request, new JsonResponse([], Response::HTTP_FORBIDDEN)),
        );
    }

    public function testGivenASuccessfulResponseWhenTheResponseIsSentThenNothingIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Assert
        $loggerMock->expects($this->never())->method('info');

        // Act
        $this->createSubscriber($loggerMock, $this->createAgenticCommerceApiUser())->onKernelResponse(
            $this->createResponseEvent(Request::create(static::MCP_PATH), new JsonResponse([], Response::HTTP_OK)),
        );
    }

    public function testGivenADenialOnAnotherFirewallWhenTheResponseIsSentThenNothingIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Assert
        $loggerMock->expects($this->never())->method('info');

        // Act
        $this->createSubscriber($loggerMock, null, static::STOREFRONT_FIREWALL_NAME)->onKernelResponse(
            $this->createResponseEvent(Request::create(static::MCP_PATH), new JsonResponse([], Response::HTTP_FORBIDDEN)),
        );
    }

    protected function createSubscriber(LoggerInterface $logger, ?UserInterface $user, ?string $firewallName = null): AgenticCommerceDenialAuditSubscriber
    {
        $agenticCommerceConfig = new AgenticCommerceConfig();
        $securityStub = $this->createStub(Security::class);
        $securityStub->method('getUser')->willReturn($user);
        $securityStub->method('getFirewallConfig')->willReturn(
            new FirewallConfig($firewallName ?? $agenticCommerceConfig->getFirewallName(), static::USER_CHECKER),
        );
        $agenticCommerceDenialAuditLoggerMock = $this->getMockBuilder(AgenticCommerceDenialAuditLogger::class)
            ->onlyMethods(['getAuditLogger'])
            ->getMock();
        $agenticCommerceDenialAuditLoggerMock->method('getAuditLogger')->willReturn($logger);

        return new AgenticCommerceDenialAuditSubscriber($securityStub, $agenticCommerceDenialAuditLoggerMock, $agenticCommerceConfig);
    }

    protected function createAccessDeniedHandler(UserInterface $user): AgenticCommerceAccessDeniedHandler
    {
        $agenticCommerceService = $this->tester->createAgenticCommerceService([[
            'name' => SharedAgenticCommerceConfig::RESOURCE_NAME_MCP,
            'pathPrefix' => static::MCP_PATH,
            'resourceUri' => 'https://glue.example/mcp',
            'scopes' => [static::SCOPE_CART_WRITE],
        ]]);
        $securityStub = $this->createStub(Security::class);
        $securityStub->method('getUser')->willReturn($user);

        return new AgenticCommerceAccessDeniedHandler(
            new AgenticCommerceAuthenticationChallengeBuilder(new AgenticCommerceConfig(), $agenticCommerceService),
            $agenticCommerceService,
            $securityStub,
        );
    }

    protected function createMcpToolScopeSubscriber(): AgenticCommerceMcpToolScopeSubscriber
    {
        $agenticCommerceServiceStub = $this->createStub(AgenticCommerceServiceInterface::class);
        $agenticCommerceServiceStub->method('findProtectedResourceByPath')->willReturn(
            (new AgenticCommerceProtectedResourceTransfer())->setName(SharedAgenticCommerceConfig::RESOURCE_NAME_MCP),
        );
        $agenticCommerceServiceStub->method('getMcpToolScopes')->willReturn([static::MCP_TOOL_CART_ADD => static::SCOPE_CART_WRITE]);
        $agenticCommerceServiceStub->method('mapScopeToRole')->willReturn(static::ROLE_SCOPE_CART_WRITE);
        $securityStub = $this->createStub(Security::class);
        $securityStub->method('isGranted')->willReturn(false);

        return new AgenticCommerceMcpToolScopeSubscriber($agenticCommerceServiceStub, $securityStub);
    }

    protected function refuseMcpToolCall(Request $request): ?AccessDeniedException
    {
        try {
            $this->createMcpToolScopeSubscriber()->onKernelRequest(
                new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST),
            );
        } catch (AccessDeniedException $accessDeniedException) {
            return $accessDeniedException;
        }

        return null;
    }

    protected function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }

    protected function createAgenticCommerceApiUser(): AgenticCommerceApiUser
    {
        $agenticCommerceAccessTokenTransfer = (new AgenticCommerceAccessTokenTransfer())
            ->setClientId(static::CLIENT_ID)
            ->setSubject('{"customer_reference":"DE--1"}')
            ->setTokenIdentifier('token-identifier')
            ->setConsentUuid(static::CONSENT_UUID)
            ->setScopes(['catalog:read']);
        $agenticCommerceConsentTransfer = (new AgenticCommerceConsentTransfer())->setUuid(static::CONSENT_UUID);

        return new AgenticCommerceApiUser(
            $agenticCommerceAccessTokenTransfer,
            $agenticCommerceConsentTransfer,
            ['ROLE_AGENTIC_COMMERCE'],
            SharedAgenticCommerceConfig::RESOURCE_NAME_MCP,
            static::CUSTOMER_REFERENCE,
        );
    }
}
