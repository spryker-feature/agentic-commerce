<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\CustomerTransfer;

interface ConsentCacheInvalidatorInterface
{
    /**
     * Specification:
     * - Tombstones the consent cache entry of every consent in its store.
     * - Runs after the change is committed; logs every failure at error level and never throws.
     *
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     */
    public function invalidateConsents(array $agenticCommerceConsentTransfers): void;

    /**
     * Specification:
     * - Tombstones every cached consent of the customer, in every store of the region.
     * - Logs every failure at error level and never throws.
     */
    public function invalidateByCustomerReference(string $customerReference): void;

    /**
     * Specification:
     * - Does nothing when the customer has no customer reference.
     * - Otherwise behaves like {@link invalidateByCustomerReference()}.
     */
    public function invalidateByCustomer(CustomerTransfer $customerTransfer): void;
}
