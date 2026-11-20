<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Plugin;

use Codeception\Test\Unit;
use Spryker\Service\Container\Container;
use Spryker\Shared\EventDispatcher\EventDispatcher;
use SprykerFeature\Yves\AgenticCommerce\Plugin\EventDispatcher\AgenticCommerceFramingProtectionEventDispatcherPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Plugin
 * @group AgenticCommerceFramingProtectionEventDispatcherPluginTest
 * Add your own group annotations below this line
 */
class AgenticCommerceFramingProtectionEventDispatcherPluginTest extends Unit
{
    /**
     * @uses \Spryker\Yves\Application\ApplicationConfig::HEADER_X_FRAME_OPTIONS_VALUE
     */
    protected const string SHOP_X_FRAME_OPTIONS = 'SAMEORIGIN';

    /**
     * @uses \Spryker\Yves\Application\ApplicationConfig::HEADER_CONTENT_SECURITY_POLICY_VALUE
     */
    protected const string SHOP_CONTENT_SECURITY_POLICY = 'frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\'';

    protected const string PROJECT_SCRIPT_CONTENT_SECURITY_POLICY = "script-src 'self' 'nonce-abc'";

    public function testGivenTheConsentRouteWhenTheResponseIsDispatchedThenFramingIsBlocked(): void
    {
        // Act
        $response = $this->dispatchResponse('agentic-commerce/consent');

        // Assert
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame("frame-ancestors 'none'; base-uri 'self'; form-action 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function testGivenTheReloginRouteWhenTheResponseIsDispatchedThenFramingIsBlocked(): void
    {
        // Act
        $response = $this->dispatchResponse('agentic-commerce/relogin');

        // Assert
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'none'", (string)$response->headers->get('Content-Security-Policy'));
    }

    public function testGivenTheAuthorizeRouteWhenTheResponseIsDispatchedThenFramingIsBlocked(): void
    {
        // Act
        $response = $this->dispatchResponse('agentic-commerce/authorize');

        // Assert
        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString("frame-ancestors 'none'", (string)$response->headers->get('Content-Security-Policy'));
    }

    /**
     * @dataProvider authorizationRouteDataProvider
     */
    public function testGivenAnAuthorizationRouteWhenTheResponseIsDispatchedThenTheResponseIsNotStored(string $routeName): void
    {
        // Act
        $response = $this->dispatchResponse($routeName);

        // Assert
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizationRouteDataProvider(): array
    {
        return [
            'authorize, which renders the interstitial page with an error' => ['agentic-commerce/authorize'],
            'consent, which renders the interstitial page with the authorization code' => ['agentic-commerce/consent'],
            'relogin' => ['agentic-commerce/relogin'],
        ];
    }

    public function testGivenTheConsentRouteAndNoContentSecurityPolicyWhenTheResponseIsDispatchedThenFramingIsBlockedByANewPolicy(): void
    {
        // Act
        $response = $this->dispatchResponse('agentic-commerce/consent', false);

        // Assert
        $this->assertSame("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function testGivenARouteOutsideTheAuthorizationFlowWhenTheResponseIsDispatchedThenTheShopFramingHeadersStayAndTheResponseIsNotMarkedNoStore(): void
    {
        // Act
        $response = $this->dispatchResponse('home');

        // Assert
        $this->assertSame(static::SHOP_X_FRAME_OPTIONS, $response->headers->get('X-Frame-Options'));
        $this->assertSame(static::SHOP_CONTENT_SECURITY_POLICY, $response->headers->get('Content-Security-Policy'));
        $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
    }

    public function testGivenTheConsentRouteAndAnUpperCaseFrameAncestorsDirectiveWhenTheResponseIsDispatchedThenTheDirectiveIsReplaced(): void
    {
        // Act
        $response = $this->dispatchResponse('agentic-commerce/consent', true, "Frame-Ancestors 'self'; base-uri 'self'");

        // Assert
        $this->assertSame("frame-ancestors 'none'; base-uri 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function testGivenTheConsentRouteAndTwoContentSecurityPolicyHeadersWhenTheResponseIsDispatchedThenBothAreKeptEachWithFramingBlocked(): void
    {
        // Act
        $response = $this->dispatchResponse('agentic-commerce/consent', true, static::SHOP_CONTENT_SECURITY_POLICY, [static::PROJECT_SCRIPT_CONTENT_SECURITY_POLICY]);

        // Assert
        $this->assertSame(
            [
                "frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
                "frame-ancestors 'none'; " . static::PROJECT_SCRIPT_CONTENT_SECURITY_POLICY,
            ],
            $response->headers->all('Content-Security-Policy'),
        );
    }

    /**
     * @param list<string> $additionalContentSecurityPolicies
     */
    protected function dispatchResponse(
        string $routeName,
        bool $hasShopContentSecurityPolicy = true,
        string $shopContentSecurityPolicy = self::SHOP_CONTENT_SECURITY_POLICY,
        array $additionalContentSecurityPolicies = []
    ): Response {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(KernelEvents::RESPONSE, static function (ResponseEvent $responseEvent) use ($hasShopContentSecurityPolicy, $shopContentSecurityPolicy, $additionalContentSecurityPolicies): void {
            $responseEvent->getResponse()->headers->set('X-Frame-Options', static::SHOP_X_FRAME_OPTIONS);

            if ($hasShopContentSecurityPolicy) {
                $responseEvent->getResponse()->headers->set('Content-Security-Policy', $shopContentSecurityPolicy);
            }

            foreach ($additionalContentSecurityPolicies as $additionalContentSecurityPolicy) {
                $responseEvent->getResponse()->headers->set('Content-Security-Policy', $additionalContentSecurityPolicy, false);
            }
        });
        $eventDispatcher = (new AgenticCommerceFramingProtectionEventDispatcherPlugin())->extend($eventDispatcher, new Container());

        $request = new Request();
        $request->attributes->set('_route', $routeName);
        $responseEvent = new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response());
        $eventDispatcher->dispatch($responseEvent, KernelEvents::RESPONSE);

        return $responseEvent->getResponse();
    }
}
