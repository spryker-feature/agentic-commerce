<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceAuthenticationChallengeBuilderTest
 * Add your own group annotations below this line
 */
class AgenticCommerceAuthenticationChallengeBuilderTest extends Unit
{
    protected const string ISSUER = 'https://glue-storefront.shop.test';

    protected const string PROTECTED_RESOURCE_METADATA_URL = self::ISSUER . '/.well-known/oauth-protected-resource/agentic-commerce';

    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected const string PRINCIPAL_RESOURCE_URI = 'https://glue-storefront.shop.test/agentic-commerce';

    protected const string SCOPE = 'agentic-commerce:principal-read';

    protected AgenticCommerceGlueTester $tester;

    protected function _before(): void
    {
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
    }

    public function testGivenNoErrorWhenBuildingTheChallengeThenItPointsToTheMetadataAndScopesOfTheResourceOfThePath(): void
    {
        // Act
        $challenge = $this->createBuilder()->buildUnauthorizedHeader(Request::create(static::PROTECTED_PATH));

        // Assert
        $this->assertSame(sprintf('Bearer resource_metadata="%s", scope="%s"', static::PROTECTED_RESOURCE_METADATA_URL, static::SCOPE), $challenge);
    }

    public function testGivenTheInvalidTokenErrorCodeWhenBuildingTheChallengeThenItCarriesTheInvalidTokenError(): void
    {
        // Act
        $challenge = $this->createBuilder()->buildUnauthorizedHeader(
            Request::create(static::PROTECTED_PATH),
            AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN,
        );

        // Assert
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", error="invalid_token", scope="%s"', static::PROTECTED_RESOURCE_METADATA_URL, static::SCOPE),
            $challenge,
        );
    }

    public function testGivenAnInsufficientScopeWithTheRequiredScopeWhenBuildingTheChallengeThenItNamesTheScope(): void
    {
        // Act
        $challenge = $this->createBuilder()->buildInsufficientScopeHeader(Request::create(static::PROTECTED_PATH), 'cart:write');

        // Assert
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", error="insufficient_scope", scope="cart:write"', static::PROTECTED_RESOURCE_METADATA_URL),
            $challenge,
        );
    }

    public function testGivenAValueWithAQuoteWhenBuildingTheChallengeThenTheQuoteIsEscaped(): void
    {
        // Act
        $challenge = $this->createBuilder()->buildInsufficientScopeHeader(Request::create(static::PROTECTED_PATH), 'a"b');

        // Assert
        $this->assertStringEndsWith('scope="a\"b"', $challenge);
    }

    public function testGivenAResourceWithoutScopesWhenBuildingTheChallengeThenItCarriesNoScope(): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->createAgenticCommerceService([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
        ]);

        // Act
        $challenge = $this->createBuilder($agenticCommerceService)->buildUnauthorizedHeader(Request::create('/mcp'));

        // Assert
        $this->assertSame('Bearer resource_metadata="https://glue.example/.well-known/oauth-protected-resource/mcp"', $challenge);
    }

    public function testGivenAPathUnderNoDeclaredResourceWhenBuildingTheChallengeThenItPointsToTheFirstDeclaredResource(): void
    {
        // Act
        $challenge = $this->createBuilder()->buildUnauthorizedHeader(Request::create('/somewhere-else'));

        // Assert
        $this->assertSame(sprintf('Bearer resource_metadata="%s", scope="%s"', static::PROTECTED_RESOURCE_METADATA_URL, static::SCOPE), $challenge);
    }

    public function testGivenNoDeclaredResourceWhenBuildingTheChallengeThenItPointsToTheConfiguredMetadataUrlWithoutScope(): void
    {
        // Act
        $challenge = $this->createBuilder($this->tester->createAgenticCommerceService([]))->buildUnauthorizedHeader(Request::create(static::PROTECTED_PATH));

        // Assert
        $this->assertSame(sprintf('Bearer resource_metadata="%s"', static::PROTECTED_RESOURCE_METADATA_URL), $challenge);
    }

    public function testGivenAnInvalidResourceDeclarationWhenBuildingTheChallengeThenItPointsToTheConfiguredMetadataUrlWithoutScope(): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->createAgenticCommerceService(
            [['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => 'http://glue.example/agentic-commerce', 'scopes' => [static::SCOPE]]],
            $this->createMock(LoggerInterface::class),
        );

        // Act
        $challenge = $this->createBuilder($agenticCommerceService)->buildUnauthorizedHeader(Request::create('/somewhere-else'));

        // Assert
        $this->assertSame(sprintf('Bearer resource_metadata="%s"', static::PROTECTED_RESOURCE_METADATA_URL), $challenge);
    }

    protected function createBuilder(?AgenticCommerceServiceInterface $agenticCommerceService = null): AgenticCommerceAuthenticationChallengeBuilder
    {
        return new AgenticCommerceAuthenticationChallengeBuilder(
            new AgenticCommerceConfig(),
            $agenticCommerceService ?? $this->tester->createAgenticCommerceService([
                ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => static::PRINCIPAL_RESOURCE_URI, 'scopes' => [static::SCOPE]],
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => ['catalog:read', 'cart:read']],
            ]),
        );
    }
}
