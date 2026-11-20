<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;

interface ConsentUsageValidatorInterface
{
    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer;
}
