<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Dependency\Plugin;

/**
 * Use this plugin to declare the scope that a delegated access token needs to call each MCP tool the module shipping it serves.
 */
interface AgenticCommerceMcpToolScopeProviderPluginInterface
{
    /**
     * Specification:
     * - Returns the scope every tool needs, by the tool name the MCP server lists; the name is compared exactly, case included.
     * - An empty scope means the tool needs no scope; a tool that is not declared at all is refused.
     * - A scope has the shape `<resource>:<action>` and is one of the scopes of the `mcp` protected resource.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getToolScopes(): array;
}
