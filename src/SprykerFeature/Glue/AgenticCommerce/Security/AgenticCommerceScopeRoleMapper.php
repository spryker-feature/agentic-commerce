<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class AgenticCommerceScopeRoleMapper
{
    /**
     * @var array<string, list<string>>
     */
    protected const array PRINCIPAL_TYPE_ROLES = [
        SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER => [SharedAgenticCommerceConfig::ROLE_CUSTOMER],
        SharedAgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER => [SharedAgenticCommerceConfig::ROLE_CUSTOMER, SharedAgenticCommerceConfig::ROLE_COMPANY_USER],
    ];

    public function __construct(protected AgenticCommerceServiceInterface $agenticCommerceService)
    {
    }

    /**
     * @param list<string> $scopes
     *
     * @return list<string>
     */
    public function mapScopesToRoles(array $scopes, string $principalType): array
    {
        $roles = [SharedAgenticCommerceConfig::ROLE_AGENTIC_COMMERCE];

        foreach ($scopes as $scope) {
            if ($this->agenticCommerceService->isDelegatedAccessScope($scope)) {
                $roles[] = $this->agenticCommerceService->mapScopeToRole($scope);
            }
        }

        return array_values(array_unique(array_merge($roles, static::PRINCIPAL_TYPE_ROLES[$principalType] ?? [])));
    }
}
