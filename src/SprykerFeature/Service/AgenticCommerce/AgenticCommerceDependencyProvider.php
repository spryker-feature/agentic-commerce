<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce;

use Spryker\Service\Kernel\AbstractBundleDependencyProvider;
use Spryker\Service\Kernel\Container;

class AgenticCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string PLUGINS_AGENTIC_COMMERCE_PROTECTED_RESOURCE_PROVIDER = 'PLUGINS_AGENTIC_COMMERCE_PROTECTED_RESOURCE_PROVIDER';

    public const string PLUGINS_AGENTIC_COMMERCE_MCP_TOOL_SCOPE_PROVIDER = 'PLUGINS_AGENTIC_COMMERCE_MCP_TOOL_SCOPE_PROVIDER';

    public function provideServiceDependencies(Container $container): Container
    {
        $container = parent::provideServiceDependencies($container);
        $container = $this->addAgenticCommerceProtectedResourceProviderPlugins($container);
        $container = $this->addAgenticCommerceMcpToolScopeProviderPlugins($container);

        return $container;
    }

    protected function addAgenticCommerceProtectedResourceProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_PROTECTED_RESOURCE_PROVIDER, function () {
            return $this->getAgenticCommerceProtectedResourceProviderPlugins();
        });

        return $container;
    }

    /**
     * @return list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface>
     */
    protected function getAgenticCommerceProtectedResourceProviderPlugins(): array
    {
        return [];
    }

    protected function addAgenticCommerceMcpToolScopeProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_MCP_TOOL_SCOPE_PROVIDER, function () {
            return $this->getAgenticCommerceMcpToolScopeProviderPlugins();
        });

        return $container;
    }

    /**
     * @return list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceMcpToolScopeProviderPluginInterface>
     */
    protected function getAgenticCommerceMcpToolScopeProviderPlugins(): array
    {
        return [];
    }
}
