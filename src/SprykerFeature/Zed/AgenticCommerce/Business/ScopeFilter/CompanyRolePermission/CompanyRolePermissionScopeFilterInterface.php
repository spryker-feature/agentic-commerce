<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\ScopeFilter\CompanyRolePermission;

use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;

interface CompanyRolePermissionScopeFilterInterface
{
    /**
     * @param list<string> $scopes
     *
     * @return list<string>
     */
    public function filterScopes(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer, array $scopes): array;
}
