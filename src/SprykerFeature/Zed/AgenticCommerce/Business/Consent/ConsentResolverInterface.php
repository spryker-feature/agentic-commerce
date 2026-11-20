<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;

interface ConsentResolverInterface
{
    /**
     * Resolves the customer, store, principal and user identifier onto the consent when it has no error.
     *
     * @return list<string>
     */
    public function resolveConsent(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): array;
}
