<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * Use this plugin to refuse an order placed through a delegated-access consent when the quote breaks one type of per-consent constraint, for example an order budget.
 */
interface AgenticCommerceOrderConstraintCheckerPluginInterface
{
    /**
     * Specification:
     * - Returns the consent constraint type this checker enforces.
     *
     * @api
     */
    public function getType(): string;

    /**
     * Specification:
     * - Checks the quote against one stored constraint of the consent.
     * - Returns null when the quote passes, otherwise a refused check with a reason.
     *
     * @api
     */
    public function check(
        AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer,
        QuoteTransfer $quoteTransfer,
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): ?AgenticCommerceOrderPlacementCheckTransfer;
}
