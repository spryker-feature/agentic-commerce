<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;

interface ProtectedResourceValidatorInterface
{
    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer> $declaredResourceTransfers
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    public function validate(
        AgenticCommerceProtectedResourceTransfer $protectedResourceTransfer,
        array $declaredResourceTransfers
    ): void;
}
