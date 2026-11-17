<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Plugin;

use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceOrderPlacementCheckerPluginInterface;

class CountingOrderPlacementCheckerPlugin implements AgenticCommerceOrderPlacementCheckerPluginInterface
{
    protected int $checkCount = 0;

    protected ?AgenticCommerceOrderPlacementCheckRequestTransfer $lastCheckRequestTransfer = null;

    public function __construct(
        protected string $principalType,
        protected ?int $orderTotalLimit,
        protected string $result,
        protected ?string $approvalStatus
    ) {
    }

    public function isApplicable(AgenticCommercePrincipalTransfer $principalTransfer): bool
    {
        return $principalTransfer->getPrincipalType() === $this->principalType;
    }

    public function check(AgenticCommerceOrderPlacementCheckRequestTransfer $checkRequestTransfer): AgenticCommerceOrderPlacementCheckTransfer
    {
        $this->checkCount++;
        $this->lastCheckRequestTransfer = $checkRequestTransfer;

        return (new AgenticCommerceOrderPlacementCheckTransfer())
            ->setResult($this->result)
            ->setApprovalStatus($this->approvalStatus);
    }

    public function findOrderTotalLimit(AgenticCommercePrincipalTransfer $principalTransfer, string $storeName, string $currencyCode): ?int
    {
        return $this->orderTotalLimit;
    }

    public function countChecks(): int
    {
        return $this->checkCount;
    }

    public function findLastCheckRequest(): ?AgenticCommerceOrderPlacementCheckRequestTransfer
    {
        return $this->lastCheckRequestTransfer;
    }
}
