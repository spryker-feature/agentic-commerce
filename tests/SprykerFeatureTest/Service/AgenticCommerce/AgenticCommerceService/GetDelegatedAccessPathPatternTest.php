<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group GetDelegatedAccessPathPatternTest
 * Add your own group annotations below this line
 */
class GetDelegatedAccessPathPatternTest extends Unit
{
    protected AgenticCommerceServiceTester $tester;

    /**
     * @dataProvider providePaths
     */
    public function testGivenDeclaredResourcesWhenMatchingAPathAgainstThePatternLikeSymfonyAndNelmioDoThenOnlyProtocolAndResourcePathsMatch(
        string $path,
        bool $expectedIsMatching
    ): void {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => 'https://glue.example/agentic-commerce', 'scopes' => []],
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
        ])]);

        // Act
        $pathPattern = $this->tester->getService()->getDelegatedAccessPathPattern();

        // Assert
        $this->assertSame($expectedIsMatching, preg_match('{' . $pathPattern . '}i', $path) === 1);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function providePaths(): array
    {
        return [
            'token endpoint' => ['/oauth/token', true],
            'revocation endpoint' => ['/oauth/revoke', true],
            'protected resource metadata' => ['/.well-known/oauth-protected-resource/mcp', true],
            'declared resource root' => ['/mcp', true],
            'below a declared resource' => ['/mcp/tools', true],
            'principal resource' => ['/agentic-commerce/principal', true],
            'name sharing a declared prefix' => ['/mcpx', false],
            'declared prefix below a store prefix' => ['/DE/mcp', false],
            'storefront resource' => ['/carts', false],
            'resource sharing the oauth prefix' => ['/oauth-clients', false],
        ];
    }

    public function testGivenDeclaredResourcesWhenReadingThePatternThenItIsAnchoredAtTheStartOfThePath(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => []],
        ])]);

        // Act
        $pathPattern = $this->tester->getService()->getDelegatedAccessPathPattern();

        // Assert
        $this->assertStringStartsWith('^', $pathPattern);
    }

    public function testGivenNoDeclaredResourceWhenMatchingAPathAgainstThePatternThenOnlyTheProtocolPathsMatch(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([]);

        // Act
        $pathPattern = $this->tester->getService()->getDelegatedAccessPathPattern();

        // Assert
        $this->assertSame(1, preg_match('{' . $pathPattern . '}i', '/oauth/token'));
        $this->assertSame(0, preg_match('{' . $pathPattern . '}i', '/agentic-commerce/principal'));
    }
}
