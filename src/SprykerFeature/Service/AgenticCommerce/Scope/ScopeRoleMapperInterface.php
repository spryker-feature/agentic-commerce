<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Scope;

interface ScopeRoleMapperInterface
{
    public function mapScopeToRole(string $scope): string;

    public function isDelegatedAccessScope(string $scope): bool;
}
