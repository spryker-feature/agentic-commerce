<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeature\Service\AgenticCommerce\Plugin\AgenticCommerce\PrincipalAgenticCommerceProtectedResourceProviderPlugin;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group PrincipalAgenticCommerceProtectedResourceProviderPluginTest
 * Add your own group annotations below this line
 */
class PrincipalAgenticCommerceProtectedResourceProviderPluginTest extends Unit
{
    protected const string ISSUER = 'https://glue.example';

    protected const string RESOURCE_URI = self::ISSUER . '/agentic-commerce';

    protected AgenticCommerceServiceTester $tester;

    public function testGivenAConfiguredIssuerWhenDeclaringResourcesThenThePrincipalResourceIsServedAtItsPath(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);

        // Act
        $protectedResourceTransfers = (new PrincipalAgenticCommerceProtectedResourceProviderPlugin())->getProtectedResources();

        // Assert
        $this->assertCount(1, $protectedResourceTransfers);
        $this->assertSame('principal', $protectedResourceTransfers[0]->getName());
        $this->assertSame('/agentic-commerce', $protectedResourceTransfers[0]->getPathPrefix());
        $this->assertSame(static::RESOURCE_URI, $protectedResourceTransfers[0]->getResourceUri());
        $this->assertSame(['agentic-commerce:principal-read'], $protectedResourceTransfers[0]->getScopes());
    }

    public function testGivenNoIssuerWhenDeclaringResourcesThenNothingIsDeclared(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Act
        $protectedResourceTransfers = (new PrincipalAgenticCommerceProtectedResourceProviderPlugin())->getProtectedResources();

        // Assert
        $this->assertSame([], $protectedResourceTransfers);
    }
}
