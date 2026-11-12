<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Scope;

use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;

class McpToolScopeReader implements McpToolScopeReaderInterface
{
    /**
     * @var array<string, string>|null
     */
    protected ?array $toolScopes = null;

    /**
     * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceMcpToolScopeProviderPluginInterface> $mcpToolScopeProviderPlugins
     */
    public function __construct(protected array $mcpToolScopeProviderPlugins)
    {
    }

    public function getMcpToolScopes(): array
    {
        if ($this->toolScopes !== null) {
            return $this->toolScopes;
        }

        $toolScopes = [];

        foreach ($this->mcpToolScopeProviderPlugins as $mcpToolScopeProviderPlugin) {
            foreach ($mcpToolScopeProviderPlugin->getToolScopes() as $toolName => $scope) {
                if (isset($toolScopes[$toolName]) && $toolScopes[$toolName] !== $scope) {
                    throw new AgenticCommerceConfigurationException(sprintf('The MCP tool "%s" is declared with the scopes "%s" and "%s".', $toolName, $toolScopes[$toolName], $scope));
                }

                $toolScopes[$toolName] = $scope;
            }
        }

        $this->toolScopes = $toolScopes;

        return $this->toolScopes;
    }
}
