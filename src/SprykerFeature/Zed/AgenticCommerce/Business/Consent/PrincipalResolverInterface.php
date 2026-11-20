<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;

interface PrincipalResolverInterface
{
    public function getCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer;

    public function findCandidate(
        AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer,
        CustomerTransfer $customerTransfer
    ): ?AgenticCommercePrincipalTransfer;

    public function buildUserIdentifier(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): ?string;

    public function hasPrincipalResolver(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): bool;

    public function isPrincipalActive(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): bool;
}
