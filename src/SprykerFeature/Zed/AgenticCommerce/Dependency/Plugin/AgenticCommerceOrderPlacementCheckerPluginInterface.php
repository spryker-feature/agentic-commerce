<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;

/**
 * Decides, for one kind of principal, whether the principal's own authority covers an order placed through a delegated consent.
 */
interface AgenticCommerceOrderPlacementCheckerPluginInterface
{
    /**
     * Specification:
     * - Returns true when this checker decides for the given principal, for example company_user.
     *
     * @api
     */
    public function isApplicable(AgenticCommercePrincipalTransfer $principalTransfer): bool;

    /**
     * Specification:
     * - Checks the principal's own authority for the quote, after the generic consent rules passed.
     * - Returns result `allowed`, or `requires_approval` with approval status and approver candidates, or `refused` with a reason.
     *
     * @api
     */
    public function check(AgenticCommerceOrderPlacementCheckRequestTransfer $checkRequestTransfer): AgenticCommerceOrderPlacementCheckTransfer;

    /**
     * Specification:
     * - Returns the principal's own order total limit in cents for the store and currency, or null when it has none.
     * - Used for the hint on the consent page.
     *
     * @api
     */
    public function findOrderTotalLimit(AgenticCommercePrincipalTransfer $principalTransfer, string $storeName, string $currencyCode): ?int;
}
