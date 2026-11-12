<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Scope;

use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class ScopeRoleMapper implements ScopeRoleMapperInterface
{
    protected const string RESOURCE_ACTION_SEPARATOR = ':';

    protected const string WORD_SEPARATOR = '-';

    protected const string ROLE_SEPARATOR = '_';

    protected const string ROLE_WORD_SEPARATOR = '__';

    public function mapScopeToRole(string $scope): string
    {
        $role = strtr($scope, [
            static::WORD_SEPARATOR => static::ROLE_WORD_SEPARATOR,
            static::RESOURCE_ACTION_SEPARATOR => static::ROLE_SEPARATOR,
        ]);

        return SharedAgenticCommerceConfig::ROLE_SCOPE_PREFIX . strtoupper($role);
    }

    public function isDelegatedAccessScope(string $scope): bool
    {
        return preg_match(SharedAgenticCommerceConfig::DELEGATED_SCOPE_PATTERN, $scope) === 1;
    }
}
