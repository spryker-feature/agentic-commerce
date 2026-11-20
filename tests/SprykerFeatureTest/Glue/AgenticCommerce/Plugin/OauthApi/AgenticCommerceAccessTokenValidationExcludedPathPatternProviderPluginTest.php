<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Plugin\OauthApi;

use Codeception\Test\Unit;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Glue\AgenticCommerce\Plugin\OauthApi\AgenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Plugin
 * @group OauthApi
 * @group AgenticCommerceAccessTokenValidationExcludedPathPatternProviderPluginTest
 * Add your own group annotations below this line
 */
class AgenticCommerceAccessTokenValidationExcludedPathPatternProviderPluginTest extends Unit
{
    protected AgenticCommerceGlueTester $tester;

    public function testGivenAResourceDeclaredUnderAPrefixWhenReadingTheExcludedPathPatternsThenTheyMatchAPathUnderThatPrefix(): void
    {
        // Arrange
        $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin = $this->createPluginWithResourceUnder('/shopping-agent');

        // Act
        $excludedPathPatterns = $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin->getExcludedPathPatterns();

        // Assert
        $this->assertTrue($this->isMatchedByAny($excludedPathPatterns, '/shopping-agent/carts'));
    }

    public function testGivenAResourceDeclaredUnderAPrefixWhenReadingTheExcludedPathPatternsThenTheyMatchTheProtocolEndpoints(): void
    {
        // Arrange
        $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin = $this->createPluginWithResourceUnder('/shopping-agent');

        // Act
        $excludedPathPatterns = $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin->getExcludedPathPatterns();

        // Assert
        $this->assertTrue($this->isMatchedByAny($excludedPathPatterns, '/oauth/token'));
    }

    public function testGivenAResourceDeclaredUnderAPrefixWhenReadingTheExcludedPathPatternsThenTheyDoNotMatchAStorefrontPath(): void
    {
        // Arrange
        $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin = $this->createPluginWithResourceUnder('/shopping-agent');

        // Act
        $excludedPathPatterns = $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin->getExcludedPathPatterns();

        // Assert
        $this->assertFalse($this->isMatchedByAny($excludedPathPatterns, '/carts'));
    }

    protected function createPluginWithResourceUnder(string $pathPrefix): AgenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin
    {
        $agenticCommerceFactoryMock = $this->getMockBuilder(AgenticCommerceFactory::class)
            ->onlyMethods(['getAgenticCommerceService'])
            ->getMock();
        $agenticCommerceFactoryMock->method('getAgenticCommerceService')->willReturn($this->tester->createAgenticCommerceService([
            ['name' => 'principal', 'pathPrefix' => $pathPrefix, 'resourceUri' => 'https://glue.example' . $pathPrefix, 'scopes' => []],
        ]));

        $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin = new AgenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin();
        $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin->setFactory($agenticCommerceFactoryMock);

        return $agenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin;
    }

    /**
     * @param list<string> $pathPatterns
     */
    protected function isMatchedByAny(array $pathPatterns, string $path): bool
    {
        foreach ($pathPatterns as $pathPattern) {
            if (preg_match('{' . $pathPattern . '}s', $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
