<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use ApiPlatform\Metadata\Get;
use Codeception\Test\Unit;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAccessDeniedHandler;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceAccessDeniedHandlerTest
 * Add your own group annotations below this line
 */
class AgenticCommerceAccessDeniedHandlerTest extends Unit
{
    protected const string METADATA_URL = 'https://glue.example/.well-known/oauth-protected-resource/mcp';

    protected AgenticCommerceGlueTester $tester;

    public function testGivenADenialOfAScopeRoleWhenHandlingThenThe403ChallengeNamesTheScopeBehindTheRole(): void
    {
        // Arrange
        $accessDeniedException = new AccessDeniedException();
        $accessDeniedException->setAttributes(['ROLE_SCOPE_CART_READ']);

        // Act
        $response = $this->createHandler()->handle(Request::create('/mcp'), $accessDeniedException);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", error="insufficient_scope", scope="cart:read"', static::METADATA_URL),
            $response->headers->get('WWW-Authenticate'),
        );
        $this->assertSame('insufficient_scope', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenAnOperationWhoseSecurityExpressionChecksAScopeRoleTheUserLacksWhenHandlingThenThe403ChallengeNamesThatScope(): void
    {
        // Arrange
        $request = Request::create('/mcp');
        $request->attributes->set(
            RequestAttribute::API_OPERATION,
            new Get(security: "is_granted('ROLE_SCOPE_CATALOG_READ') and is_granted('ROLE_SCOPE_CART_READ')"),
        );

        // Act
        $response = $this->createHandler(['ROLE_SCOPE_CATALOG_READ'])->handle($request, new AccessDeniedException());

        // Assert
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", error="insufficient_scope", scope="cart:read"', static::METADATA_URL),
            $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testGivenADenialOfARoleNoDeclaredScopeMapsToWhenHandlingThenAPlain403WithoutInsufficientScopeIsAnswered(): void
    {
        // Arrange
        $accessDeniedException = new AccessDeniedException();
        $accessDeniedException->setAttributes(['ROLE_CUSTOMER']);

        // Act
        $response = $this->createHandler()->handle(Request::create('/mcp'), $accessDeniedException);

        // Assert
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertArrayNotHasKey('error', json_decode((string)$response->getContent(), true));
        $this->assertFalse($response->headers->has('WWW-Authenticate'));
    }

    /**
     * @param list<string> $userRoles
     */
    protected function createHandler(array $userRoles = []): AgenticCommerceAccessDeniedHandler
    {
        $agenticCommerceService = $this->tester->createAgenticCommerceService([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => ['catalog:read', 'cart:read']],
        ]);
        $securityStub = $this->createStub(Security::class);
        $securityStub->method('getUser')->willReturn(new InMemoryUser('agent', null, $userRoles));

        return new AgenticCommerceAccessDeniedHandler(
            new AgenticCommerceAuthenticationChallengeBuilder(new AgenticCommerceConfig(), $agenticCommerceService),
            $agenticCommerceService,
            $securityStub,
        );
    }
}
