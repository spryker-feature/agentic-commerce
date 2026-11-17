<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

/**
 * Use this plugin to declare the delegated-access scopes that the module shipping it checks in its code.
 */
interface AgenticCommerceScopeProviderPluginInterface
{
    /**
     * Specification:
     * - Returns the delegated-access scopes the module that ships this plugin checks in its code.
     * - Each scope name has the shape `<resource>:<action>` with lower-case words joined by `-`, for example `cart:write`.
     * - The role checked in code is `ROLE_SCOPE_` + the upper-case name with `:` and `-` replaced by `_`.
     * - `glossaryKey` names the consent-page label; by convention `agentic_commerce.scope.<resource>.<action>`, the name with `:` replaced by `.`.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getScopes(): array;
}
