<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceRequestMatcher;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceRequestMatcherTest
 * Add your own group annotations below this line
 */
class AgenticCommerceRequestMatcherTest extends Unit
{
    /**
     * @var list<string>
     */
    protected const array OPERATION_NAMES = ['somewhere_else_get', 'carts_get_collection'];

    /**
     * @var list<array<string, mixed>>
     */
    protected const array INVALID_RESOURCES = [
        ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'http://glue.example/mcp', 'scopes' => []],
    ];

    protected AgenticCommerceGlueTester $tester;

    public function testGivenAnInvalidResourceDeclarationWhenMatchingAStorefrontRouteThenItIsNotTakenAndTheErrorIsLogged(): void
    {
        // Arrange
        $loggerMock = $this->createMock(LoggerInterface::class);
        $matcher = new AgenticCommerceRequestMatcher(
            $this->tester->createAgenticCommerceService(static::INVALID_RESOURCES, $loggerMock),
            $this->createResourceMetadataCollectionFactoryStub([]),
        );

        // Assert
        $loggerMock->expects($this->once())->method('error');

        // Act
        $isCartsMatched = $matcher->matches(Request::create('/carts'));
        $isMisdeclaredResourceMatched = $matcher->matches(Request::create('/mcp'));
        $isTokenEndpointMatched = $matcher->matches(Request::create('/oauth/token', Request::METHOD_POST));

        // Assert
        $this->assertFalse($isCartsMatched);
        $this->assertFalse($isMisdeclaredResourceMatched);
        $this->assertTrue($isTokenEndpointMatched);
    }

    public function testGivenAPathUnderNoPrefixAndNoMarkerWhenMatchingThenTheDelegatedFirewallDoesNotTakeIt(): void
    {
        // Arrange
        $request = Request::create('/carts');

        // Act
        $isMatched = $this->createMatcher()->matches($request);

        // Assert
        $this->assertFalse($isMatched);
    }

    public function testGivenTheTokenEndpointWhenMatchingThenTheDelegatedFirewallTakesIt(): void
    {
        // Arrange
        $request = Request::create('/oauth/token', Request::METHOD_POST);

        // Act
        $isMatched = $this->createMatcher()->matches($request);

        // Assert
        $this->assertTrue($isMatched);
    }

    public function testGivenTheRegistrationEndpointWhenMatchingThenTheDelegatedFirewallTakesIt(): void
    {
        // Arrange
        $request = Request::create('/oauth/register', Request::METHOD_POST);

        // Act
        $isMatched = $this->createMatcher()->matches($request);

        // Assert
        $this->assertTrue($isMatched);
    }

    public function testGivenAPathBelowTheRegistrationEndpointWhenMatchingThenTheDelegatedFirewallDoesNotTakeIt(): void
    {
        // Arrange
        $request = Request::create('/oauth/register/x', Request::METHOD_POST);

        // Act
        $isMatched = $this->createMatcher()->matches($request);

        // Assert
        $this->assertFalse($isMatched);
    }

    public function testGivenAPathUnderADeclaredPrefixWithoutRouteAttributesWhenMatchingThenTheDelegatedFirewallTakesIt(): void
    {
        // Arrange
        $request = Request::create('/mcp', Request::METHOD_POST);

        // Act
        $isMatched = $this->createMatcher()->matches($request);

        // Assert
        $this->assertTrue($isMatched);
    }

    public function testGivenAMarkedOperationOutsideEveryPrefixWhenMatchingThenTheDelegatedFirewallTakesItSoItFailsClosed(): void
    {
        // Arrange
        $request = Request::create('/somewhere-else');
        $request->attributes->set(RequestAttribute::API_RESOURCE_CLASS, 'Generated\Api\Storefront\SomewhereElseStorefrontResource');
        $request->attributes->set(RequestAttribute::API_OPERATION_NAME, 'somewhere_else_get');
        $matcher = $this->createMatcher(operationExtraProperties: ['agenticCommerce' => true]);

        // Act
        $isMatched = $matcher->matches($request);

        // Assert
        $this->assertTrue($isMatched);
    }

    public function testGivenAnUnmarkedApiPlatformOperationOutsideEveryPrefixWhenMatchingThenTheDelegatedFirewallDoesNotTakeIt(): void
    {
        // Arrange
        $request = Request::create('/carts');
        $request->attributes->set(RequestAttribute::API_RESOURCE_CLASS, 'Generated\Api\Storefront\CartsStorefrontResource');
        $request->attributes->set(RequestAttribute::API_OPERATION_NAME, 'carts_get_collection');
        $matcher = $this->createMatcher(operationExtraProperties: ['entityTag' => 'write']);

        // Act
        $isMatched = $matcher->matches($request);

        // Assert
        $this->assertFalse($isMatched);
    }

    public function testGivenAPathThatOnlySharesTheBeginningOfADeclaredPrefixWhenMatchingThenTheDelegatedFirewallDoesNotTakeIt(): void
    {
        // Arrange
        $request = Request::create('/mcpx');

        // Act
        $isMatched = $this->createMatcher()->matches($request);

        // Assert
        $this->assertFalse($isMatched);
    }

    public function testGivenAnAlreadyResolvedMarkedOperationWhenMatchingThenTheDelegatedFirewallTakesItWithoutAMetadataLookup(): void
    {
        // Arrange
        $request = Request::create('/somewhere-else');
        $request->attributes->set(RequestAttribute::API_OPERATION, new Get(name: 'somewhere_else_get', extraProperties: ['agenticCommerce' => true]));
        $resourceMetadataCollectionFactoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);

        // Assert
        $resourceMetadataCollectionFactoryMock->expects($this->never())->method('create');

        // Act
        $isMatched = $this->createMatcher(resourceMetadataCollectionFactory: $resourceMetadataCollectionFactoryMock)->matches($request);

        // Assert
        $this->assertTrue($isMatched);
    }

    public function testGivenAResourceClassTheMetadataFactoryDoesNotKnowWhenMatchingThenTheDelegatedFirewallDoesNotTakeIt(): void
    {
        // Arrange
        $request = Request::create('/somewhere-else');
        $request->attributes->set(RequestAttribute::API_RESOURCE_CLASS, 'Generated\Api\Storefront\UnknownStorefrontResource');
        $request->attributes->set(RequestAttribute::API_OPERATION_NAME, 'unknown_get');
        $resourceMetadataCollectionFactoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryMock->method('create')->willThrowException(new ResourceClassNotFoundException());

        // Act
        $isMatched = $this->createMatcher(resourceMetadataCollectionFactory: $resourceMetadataCollectionFactoryMock)->matches($request);

        // Assert
        $this->assertFalse($isMatched);
    }

    public function testGivenAMatchedRequestWhenMatchedTwiceThenTheMetadataFactoryIsAskedOnce(): void
    {
        // Arrange
        $request = Request::create('/somewhere-else');
        $request->attributes->set(RequestAttribute::API_RESOURCE_CLASS, 'Generated\Api\Storefront\SomewhereElseStorefrontResource');
        $request->attributes->set(RequestAttribute::API_OPERATION_NAME, 'somewhere_else_get');
        $resourceMetadataCollectionFactoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn(new ResourceMetadataCollection('Generated\Api\Storefront\SomewhereElseStorefrontResource', [
                new ApiResource(operations: ['somewhere_else_get' => new Get(name: 'somewhere_else_get', extraProperties: ['agenticCommerce' => true])]),
            ]));
        $matcher = $this->createMatcher(resourceMetadataCollectionFactory: $resourceMetadataCollectionFactoryMock);

        // Act
        $isMatchedFirst = $matcher->matches($request);
        $isMatchedSecond = $matcher->matches($request);

        // Assert
        $this->assertTrue($isMatchedFirst);
        $this->assertTrue($isMatchedSecond);
    }

    /**
     * @param array<string, mixed> $operationExtraProperties
     */
    protected function createMatcher(
        array $operationExtraProperties = [],
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null
    ): AgenticCommerceRequestMatcher {
        return new AgenticCommerceRequestMatcher(
            $this->tester->createAgenticCommerceService([
                ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => ['catalog:read', 'cart:read']],
            ]),
            $resourceMetadataCollectionFactory ?? $this->createResourceMetadataCollectionFactoryStub($operationExtraProperties),
        );
    }

    /**
     * @param array<string, mixed> $operationExtraProperties
     */
    protected function createResourceMetadataCollectionFactoryStub(array $operationExtraProperties): ResourceMetadataCollectionFactoryInterface
    {
        $operations = [];

        foreach (static::OPERATION_NAMES as $operationName) {
            $operations[$operationName] = new Get(name: $operationName, extraProperties: $operationExtraProperties);
        }

        $resourceMetadataCollectionFactoryStub = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryStub->method('create')->willReturnCallback(
            static fn (string $resourceClass): ResourceMetadataCollection => new ResourceMetadataCollection(
                $resourceClass,
                [new ApiResource(operations: $operations)],
            ),
        );

        return $resourceMetadataCollectionFactoryStub;
    }
}
