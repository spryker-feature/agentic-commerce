<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Plugin\GlueStorefrontApiApplication;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use Spryker\Service\Container\Container;
use Spryker\Service\Container\ContainerInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Glue\AgenticCommerce\Plugin\GlueStorefrontApiApplication\AgenticCommerceStoreApplicationPlugin;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Plugin
 * @group GlueStorefrontApiApplication
 * @group AgenticCommerceStoreApplicationPluginTest
 * Add your own group annotations below this line
 */
class AgenticCommerceStoreApplicationPluginTest extends Unit
{
    protected const string SERVICE_STORE = 'store';

    protected AgenticCommerceGlueTester $tester;

    protected const string ACCESS_TOKEN = 'delegated-access-token';

    protected const string TOKEN_STORE_NAME = 'AT';

    protected const string DEFAULT_STORE_NAME = 'DE';

    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected const string STOREFRONT_PATH = '/carts';

    /**
     * @var array<string, mixed>
     */
    protected array $originalServer = [];

    /**
     * @var array<string, mixed>
     */
    protected array $originalQuery = [];

    protected function _before(): void
    {
        $this->originalServer = $_SERVER;
        $this->originalQuery = $_GET;
        $_GET = [];
    }

    protected function _after(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalQuery;
    }

    public function testGivenAValidDelegatedAccessTokenWithoutAStoreHeaderOnADelegatedAccessPathWhenProvidingThenTheStoreOfTheTokenBecomesTheApplicationStore(): void
    {
        // Arrange
        $this->setGlobalRequest(static::PROTECTED_PATH);

        // Act
        $container = $this->createPlugin()->provide($this->createContainer());

        // Assert
        $this->assertSame(static::TOKEN_STORE_NAME, $container->get(static::SERVICE_STORE));
    }

    public function testGivenAValidDelegatedAccessTokenAndAStoreHeaderWhenProvidingThenTheApplicationStoreIsKept(): void
    {
        // Arrange
        $this->setGlobalRequest(static::PROTECTED_PATH);
        $_SERVER['HTTP_STORE'] = static::DEFAULT_STORE_NAME;

        // Act
        $container = $this->createPlugin()->provide($this->createContainer());

        // Assert
        $this->assertSame(static::DEFAULT_STORE_NAME, $container->get(static::SERVICE_STORE));
    }

    public function testGivenAValidDelegatedAccessTokenOnAStorefrontPathWhenProvidingThenTheApplicationStoreIsKept(): void
    {
        // Arrange
        $this->setGlobalRequest(static::STOREFRONT_PATH);

        // Act
        $container = $this->createPlugin()->provide($this->createContainer());

        // Assert
        $this->assertSame(static::DEFAULT_STORE_NAME, $container->get(static::SERVICE_STORE));
    }

    protected function setGlobalRequest(string $path): void
    {
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . static::ACCESS_TOKEN;
        unset($_SERVER['HTTP_STORE'], $_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);
    }

    protected function createContainer(): ContainerInterface
    {
        $container = new Container();
        $container->set(static::SERVICE_STORE, static fn (): string => static::DEFAULT_STORE_NAME);

        return $container;
    }

    protected function createPlugin(): AgenticCommerceStoreApplicationPlugin
    {
        $agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $agenticCommerceClientMock->method('validateAgenticCommerceAccessToken')
            ->with(static::ACCESS_TOKEN)
            ->willReturn(
                (new AgenticCommerceAccessTokenValidationResponseTransfer())
                    ->setIsValid(true)
                    ->setAgenticCommerceAccessToken((new AgenticCommerceAccessTokenTransfer())->setStoreName(static::TOKEN_STORE_NAME)),
            );

        $agenticCommerceFactoryMock = $this->getMockBuilder(AgenticCommerceFactory::class)
            ->onlyMethods(['getClient', 'getAgenticCommerceService'])
            ->getMock();
        $agenticCommerceFactoryMock->method('getClient')->willReturn($agenticCommerceClientMock);
        $agenticCommerceFactoryMock->method('getAgenticCommerceService')->willReturn($this->tester->createAgenticCommerceService([
            ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => 'https://glue.example/agentic-commerce', 'scopes' => []],
        ]));

        $agenticCommerceStoreApplicationPlugin = new AgenticCommerceStoreApplicationPlugin();
        $agenticCommerceStoreApplicationPlugin->setFactory($agenticCommerceFactoryMock);

        return $agenticCommerceStoreApplicationPlugin;
    }
}
