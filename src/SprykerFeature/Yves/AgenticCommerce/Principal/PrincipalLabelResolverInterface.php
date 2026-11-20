<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Principal;

use ArrayObject;
use Generated\Shared\Transfer\CustomerTransfer;

interface PrincipalLabelResolverInterface
{
    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return array<string, string>
     */
    public function resolvePrincipalLabels(ArrayObject $agenticCommerceConsentTransfers, CustomerTransfer $customerTransfer): array;
}
