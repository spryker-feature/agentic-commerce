<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Store;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceBearerTokenExtractor;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommercePathMatcher;
use SprykerFeature\Glue\AgenticCommerce\Store\AgenticCommerceStoreResolver;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Store
 * @group AgenticCommerceStoreResolverTest
 * Add your own group annotations below this line
 */
class AgenticCommerceStoreResolverTest extends Unit
{
    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected const string ACCESS_TOKEN = 'delegated-access-token';

    protected const string TOKEN_STORE_NAME = 'DE';

    protected const string REQUESTED_STORE_NAME = 'AT';

    protected AgenticCommerceGlueTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
    }

    public function testGivenAValidTokenAndNoRequestedStoreWhenResolvingThenTheStoreOfTheTokenIsReturned(): void
    {
        // Arrange
        $this->mockTokenValidation(true);

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($this->createRequest(static::PROTECTED_PATH));

        // Assert
        $this->assertSame(static::TOKEN_STORE_NAME, $storeName);
    }

    public function testGivenAValidTokenOnAPercentEncodedDelegatedAccessPathWhenResolvingThenTheStoreOfTheTokenIsReturned(): void
    {
        // Arrange
        $this->mockTokenValidation(true);

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($this->createRequest('/agentic%2Dcommerce/principal'));

        // Assert
        $this->assertSame(static::TOKEN_STORE_NAME, $storeName);
    }

    /**
     * @dataProvider storefrontPathDataProvider
     */
    public function testGivenAValidTokenOnAPathOutsideTheDelegatedAccessFirewallWhenResolvingThenNoStoreIsReturnedWithoutAValidation(string $path): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceAccessToken');

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($this->createRequest($path));

        // Assert
        $this->assertNull($storeName);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function storefrontPathDataProvider(): array
    {
        return [
            'carts' => ['/carts'],
            'resource sharing the oauth prefix' => ['/oauth-clients'],
            'delegated access path below a store prefix' => ['/DE/agentic-commerce/principal'],
        ];
    }

    public function testGivenAValidTokenOnThePathOfAResourceDeclaredUnderAnotherPrefixWhenResolvingThenTheStoreOfTheTokenIsReturned(): void
    {
        // Arrange
        $this->mockTokenValidation(true);

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($this->createRequest('/mcp/tools'));

        // Assert
        $this->assertSame(static::TOKEN_STORE_NAME, $storeName);
    }

    public function testGivenAValidTokenAndAStoreHeaderWhenResolvingThenNoStoreIsReturned(): void
    {
        // Arrange
        $this->mockTokenValidation(true);
        $request = $this->createRequest(static::PROTECTED_PATH);
        $request->headers->set('Store', static::REQUESTED_STORE_NAME);

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($request);

        // Assert
        $this->assertNull($storeName);
    }

    public function testGivenAValidTokenAndAStoreQueryParameterWhenResolvingThenNoStoreIsReturned(): void
    {
        // Arrange
        $this->mockTokenValidation(true);

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($this->createRequest(static::PROTECTED_PATH . '?_store=' . static::REQUESTED_STORE_NAME));

        // Assert
        $this->assertNull($storeName);
    }

    public function testGivenATokenTheAccessTokenValidationRejectsWhenResolvingThenNoStoreIsReturned(): void
    {
        // Arrange
        $this->mockTokenValidation(false);

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore($this->createRequest(static::PROTECTED_PATH));

        // Assert
        $this->assertNull($storeName);
    }

    public function testGivenNoBearerTokenWhenResolvingThenNoStoreIsReturnedWithoutAValidation(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceAccessToken');

        // Act
        $storeName = $this->createResolver()->findTokenStoreNameWhenRequestNamesNoStore(Request::create(static::PROTECTED_PATH));

        // Assert
        $this->assertNull($storeName);
    }

    protected function mockTokenValidation(bool $isValid): void
    {
        $agenticCommerceAccessTokenValidationResponseTransfer = (new AgenticCommerceAccessTokenValidationResponseTransfer())->setIsValid($isValid);

        if ($isValid) {
            $agenticCommerceAccessTokenValidationResponseTransfer->setAgenticCommerceAccessToken(
                (new AgenticCommerceAccessTokenTransfer())->setStoreName(static::TOKEN_STORE_NAME),
            );
        }

        $this->agenticCommerceClientMock->method('validateAgenticCommerceAccessToken')
            ->with(static::ACCESS_TOKEN)
            ->willReturn($agenticCommerceAccessTokenValidationResponseTransfer);
    }

    protected function createRequest(string $uri): Request
    {
        $request = Request::create($uri);
        $request->headers->set('Authorization', 'Bearer ' . static::ACCESS_TOKEN);

        return $request;
    }

    protected function createResolver(): AgenticCommerceStoreResolver
    {
        return new AgenticCommerceStoreResolver(
            $this->agenticCommerceClientMock,
            new AgenticCommerceBearerTokenExtractor(),
            new AgenticCommercePathMatcher($this->tester->createAgenticCommerceService([
                ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => 'https://glue.example/agentic-commerce', 'scopes' => []],
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
            ])),
        );
    }
}
