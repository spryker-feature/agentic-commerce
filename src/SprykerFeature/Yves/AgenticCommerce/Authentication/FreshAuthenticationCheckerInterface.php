<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Authentication;

use Generated\Shared\Transfer\CustomerTransfer;

interface FreshAuthenticationCheckerInterface
{
    public function recordAuthentication(?CustomerTransfer $customerTransfer): void;

    public function isAuthenticationFresh(CustomerTransfer $customerTransfer): bool;
}
