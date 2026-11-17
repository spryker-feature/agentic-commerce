<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;

/**
 * Use this plugin to narrow the scopes a principal may be granted, for example by the permissions of a company role.
 */
interface AgenticCommerceScopeFilterPluginInterface
{
    /**
     * Specification:
     * - Returns a subset of `$scopes` that the principal may hold; never adds a scope.
     * - Returns `$scopes` unchanged for principal types the plugin does not handle.
     * - Is executed in stack order, each plugin receiving the scopes the previous one returned; a scope not in the original `$scopes` is dropped from the result.
     * - Is executed when the consent page lists the grantable scopes, when a consent is created, and again on every refresh token grant, so a changed permission narrows the next refreshed token.
     *
     * @api
     *
     * @param list<string> $scopes
     *
     * @return list<string>
     */
    public function filterScopes(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer, array $scopes): array;
}
