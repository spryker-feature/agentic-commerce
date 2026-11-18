<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\ScopeFilter\CompanyRolePermission;

use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Spryker\Zed\CompanyRole\Business\CompanyRoleFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class CompanyRolePermissionScopeFilter implements CompanyRolePermissionScopeFilterInterface
{
    public function __construct(
        protected CompanyUserFacadeInterface $companyUserFacade,
        protected CompanyRoleFacadeInterface $companyRoleFacade,
        protected AgenticCommerceConfig $config
    ) {
    }

    /**
     * @param list<string> $scopes
     *
     * @return list<string>
     */
    public function filterScopes(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer, array $scopes): array
    {
        $scopePermissionKeys = $this->config->getScopePermissionKeys();

        if (
            $agenticCommercePrincipalTransfer->getPrincipalType() !== SharedAgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER
            || array_intersect($scopes, array_keys($scopePermissionKeys)) === []
        ) {
            return $scopes;
        }

        $idCompanyUser = $this->findIdCompanyUser($agenticCommercePrincipalTransfer);
        $heldPermissionKeys = $idCompanyUser === null ? [] : $this->getHeldPermissionKeys($idCompanyUser);
        $filteredScopes = [];

        foreach ($scopes as $scope) {
            if (!isset($scopePermissionKeys[$scope]) || isset($heldPermissionKeys[$scopePermissionKeys[$scope]])) {
                $filteredScopes[] = $scope;
            }
        }

        return $filteredScopes;
    }

    /**
     * @return array<string, true>
     */
    protected function getHeldPermissionKeys(int $idCompanyUser): array
    {
        $heldPermissionKeys = [];

        foreach ($this->companyRoleFacade->findPermissionsByIdCompanyUser($idCompanyUser)->getPermissions() as $permissionTransfer) {
            $heldPermissionKeys[$permissionTransfer->getKeyOrFail()] = true;
        }

        return $heldPermissionKeys;
    }

    protected function findIdCompanyUser(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): ?int
    {
        $principalReference = $agenticCommercePrincipalTransfer->getPrincipalReference();

        if ($principalReference === null) {
            return null;
        }

        $companyUserTransfer = $this->companyUserFacade->findActiveCompanyUserByUuid((new CompanyUserTransfer())->setUuid($principalReference));

        if ($companyUserTransfer?->getCustomer()?->getCustomerReference() !== $agenticCommercePrincipalTransfer->getCustomerReference()) {
            return null;
        }

        return $companyUserTransfer?->getIdCompanyUser();
    }
}
