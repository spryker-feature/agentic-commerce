<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceMcpToolScopeProviderPluginInterface;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface;
use SprykerTest\Service\Testify\Helper\DependencyProviderHelper;

class AgenticCommerceServiceHelper extends Module
{
    /**
     * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface> $protectedResourceProviderPlugins
     */
    public function setProtectedResourceProviderPlugins(array $protectedResourceProviderPlugins): void
    {
        $this->getDependencyProviderHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_PROTECTED_RESOURCE_PROVIDER, $protectedResourceProviderPlugins);
    }

    /**
     * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceMcpToolScopeProviderPluginInterface> $mcpToolScopeProviderPlugins
     */
    public function setMcpToolScopeProviderPlugins(array $mcpToolScopeProviderPlugins): void
    {
        $this->getDependencyProviderHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_MCP_TOOL_SCOPE_PROVIDER, $mcpToolScopeProviderPlugins);
    }

    /**
     * @param array<string, string> $toolScopes
     */
    public function createMcpToolScopeProviderPlugin(array $toolScopes): AgenticCommerceMcpToolScopeProviderPluginInterface
    {
        return new class ($toolScopes) implements AgenticCommerceMcpToolScopeProviderPluginInterface {
            /**
             * @param array<string, string> $toolScopes
             */
            public function __construct(protected array $toolScopes)
            {
            }

            public function getToolScopes(): array
            {
                return $this->toolScopes;
            }
        };
    }

    /**
     * @param list<array<string, mixed>> $resources
     */
    public function createProtectedResourceProviderPlugin(array $resources): AgenticCommerceProtectedResourceProviderPluginInterface
    {
        return new class ($resources) implements AgenticCommerceProtectedResourceProviderPluginInterface {
            /**
             * @param list<array<string, mixed>> $resources
             */
            public function __construct(protected array $resources)
            {
            }

            public function getProtectedResources(): array
            {
                return array_map(
                    static fn (array $resource): AgenticCommerceProtectedResourceTransfer => (new AgenticCommerceProtectedResourceTransfer())->fromArray($resource, true),
                    $this->resources,
                );
            }
        };
    }

    protected function getDependencyProviderHelper(): DependencyProviderHelper
    {
        /** @var \SprykerTest\Service\Testify\Helper\DependencyProviderHelper $dependencyProviderHelper */
        $dependencyProviderHelper = $this->getModule('\\' . DependencyProviderHelper::class);

        return $dependencyProviderHelper;
    }
}
