<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Psr\Log\LoggerInterface;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceProtectedResourceResolver;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceProtectedResourceResolverTest
 * Add your own group annotations below this line
 */
class AgenticCommerceProtectedResourceResolverTest extends Unit
{
    protected const string OPERATION_NAME = '_api_/agentic-commerce/principal_get';

    public function testGivenAPathUnderADeclaredResourceWhenResolvingThenTheResourceOfTheDecodedPathIsReturned(): void
    {
        // Arrange
        $agenticCommerceProtectedResourceTransfer = (new AgenticCommerceProtectedResourceTransfer())->setName('mcp');
        $agenticCommerceServiceMock = $this->createMock(AgenticCommerceServiceInterface::class);
        $agenticCommerceServiceMock->expects($this->once())
            ->method('findProtectedResourceByPath')
            ->with('/mcp/tools list')
            ->willReturn($agenticCommerceProtectedResourceTransfer);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->never())->method('error');
        $agenticCommerceProtectedResourceResolver = $this->createResolver($agenticCommerceServiceMock, $loggerMock, true);

        // Act
        $resolvedAgenticCommerceProtectedResourceTransfer = $agenticCommerceProtectedResourceResolver->getProtectedResource(Request::create('/mcp/tools%20list'));

        // Assert
        $this->assertSame($agenticCommerceProtectedResourceTransfer, $resolvedAgenticCommerceProtectedResourceTransfer);
    }

    public function testGivenAMarkedOperationUnderNoDeclaredResourceWhenResolvingThenTheOperationIsLoggedAndTheAuthenticationFails(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error')->with($this->stringContains(static::OPERATION_NAME));
        $agenticCommerceProtectedResourceResolver = $this->createResolver($this->createMock(AgenticCommerceServiceInterface::class), $loggerMock, true);
        $request = Request::create('/agentic-commerce/principal');
        $request->attributes->set(RequestAttribute::API_OPERATION_NAME, static::OPERATION_NAME);

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $agenticCommerceProtectedResourceResolver->getProtectedResource($request);
    }

    public function testGivenARequestWithoutAnOperationNameUnderNoDeclaredResourceWhenResolvingThenThePathIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error')->with($this->stringContains('/agentic-commerce/principal'));
        $agenticCommerceProtectedResourceResolver = $this->createResolver($this->createMock(AgenticCommerceServiceInterface::class), $loggerMock, true);

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $agenticCommerceProtectedResourceResolver->getProtectedResource(Request::create('/agentic-commerce/principal'));
    }

    public function testGivenDelegatedAccessIsNotConfiguredWhenResolvingAPathUnderNoResourceThenTheAuthenticationFailsWithoutALogLine(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->never())->method('error');
        $agenticCommerceProtectedResourceResolver = $this->createResolver($this->createMock(AgenticCommerceServiceInterface::class), $loggerMock, false);

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $agenticCommerceProtectedResourceResolver->getProtectedResource(Request::create('/agentic-commerce/principal'));
    }

    protected function createResolver(
        AgenticCommerceServiceInterface $agenticCommerceService,
        LoggerInterface $logger,
        bool $isConfigured
    ): AgenticCommerceProtectedResourceResolver {
        $agenticCommerceConfigMock = $this->createMock(AgenticCommerceConfig::class);
        $agenticCommerceConfigMock->method('isConfigured')->willReturn($isConfigured);

        return new AgenticCommerceProtectedResourceResolver($agenticCommerceService, $logger, $agenticCommerceConfigMock);
    }
}
