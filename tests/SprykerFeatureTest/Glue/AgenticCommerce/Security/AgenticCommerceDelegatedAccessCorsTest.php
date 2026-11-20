<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use Nelmio\CorsBundle\DependencyInjection\NelmioCorsExtension;
use Nelmio\CorsBundle\EventListener\CorsListener;
use Nelmio\CorsBundle\Options\ConfigProvider;
use Nelmio\CorsBundle\Options\Resolver;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceDelegatedAccessCorsTest
 * Add your own group annotations below this line
 */
class AgenticCommerceDelegatedAccessCorsTest extends Unit
{
    protected const string BROWSER_ORIGIN = 'https://agent.example';

    protected const string HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

    protected const int MAX_AGE_IN_SECONDS = 3600;

    protected AgenticCommerceGlueTester $tester;

    public function testGivenACrossOriginCallToAResourceDeclaredUnderAnotherPrefixWhenTheDelegatedFirewallAnswers401ThenTheOriginIsAllowedAndWwwAuthenticateExposed(): void
    {
        // Arrange
        $request = $this->createCrossOriginRequest('/mcp');

        // Act
        $response = $this->answerThroughTheCorsListener($request, $this->createDelegatedChallengeResponse());

        // Assert
        $this->assertSame(static::BROWSER_ORIGIN, $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsStringIgnoringCase(static::HEADER_WWW_AUTHENTICATE, (string)$response->headers->get('Access-Control-Expose-Headers'));
    }

    public function testGivenACrossOriginCallToAStorefrontPathWhenAnsweredThenNoCorsHeaderIsAdded(): void
    {
        // Arrange
        $request = $this->createCrossOriginRequest('/carts');

        // Act
        $response = $this->answerThroughTheCorsListener($request, new Response('', Response::HTTP_FORBIDDEN));

        // Assert
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testGivenABrowserPreflightForTheRegistrationEndpointWhenAnsweredThenTheOriginJsonPostIsAllowed(): void
    {
        // Arrange
        $request = $this->createCrossOriginRequest('/oauth/register', Request::METHOD_OPTIONS);
        $request->headers->set('Access-Control-Request-Method', Request::METHOD_POST);
        $request->headers->set('Access-Control-Request-Headers', 'content-type');

        // Act
        $requestEvent = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $this->createCorsListener()->onKernelRequest($requestEvent);

        // Assert
        $response = $requestEvent->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(static::BROWSER_ORIGIN, $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsString(Request::METHOD_POST, (string)$response->headers->get('Access-Control-Allow-Methods'));
        $this->assertStringContainsStringIgnoringCase('content-type', (string)$response->headers->get('Access-Control-Allow-Headers'));
    }

    public function testGivenACrossOriginRegistrationWhenAnsweredThenTheOriginIsAllowed(): void
    {
        // Arrange
        $request = $this->createCrossOriginRequest('/oauth/register', Request::METHOD_POST);

        // Act
        $response = $this->answerThroughTheCorsListener($request, new Response('{}', Response::HTTP_CREATED));

        // Assert
        $this->assertSame(static::BROWSER_ORIGIN, $response->headers->get('Access-Control-Allow-Origin'));
    }

    protected function createCrossOriginRequest(string $path, string $method = Request::METHOD_GET): Request
    {
        $request = Request::create($path, $method);
        $request->headers->set('Origin', static::BROWSER_ORIGIN);

        return $request;
    }

    protected function createDelegatedChallengeResponse(): Response
    {
        return new Response('', Response::HTTP_UNAUTHORIZED, [static::HEADER_WWW_AUTHENTICATE => 'Bearer resource_metadata="https://glue.example/.well-known/oauth-protected-resource/mcp"']);
    }

    protected function answerThroughTheCorsListener(Request $request, Response $response): Response
    {
        $corsListener = $this->createCorsListener();
        $httpKernel = $this->createStub(HttpKernelInterface::class);

        $corsListener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $corsListener->onKernelResponse(new ResponseEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        return $response;
    }

    protected function createCorsListener(): CorsListener
    {
        $pathPattern = $this->tester->createAgenticCommerceService([
            ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => 'https://glue.example/agentic-commerce', 'scopes' => []],
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
        ])->getDelegatedAccessPathPattern();

        return new CorsListener(new Resolver([new ConfigProvider([$pathPattern => $this->createProjectNelmioCorsOptionsNormalizedByTheExtension()], NelmioCorsExtension::DEFAULTS)]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function createProjectNelmioCorsOptionsNormalizedByTheExtension(): array
    {
        return [
            'allow_origin' => [static::BROWSER_ORIGIN],
            'allow_methods' => ['GET', 'POST', 'OPTIONS'],
            'allow_headers' => ['authorization', 'content-type'],
            'expose_headers' => [static::HEADER_WWW_AUTHENTICATE],
            'max_age' => static::MAX_AGE_IN_SECONDS,
        ];
    }
}
