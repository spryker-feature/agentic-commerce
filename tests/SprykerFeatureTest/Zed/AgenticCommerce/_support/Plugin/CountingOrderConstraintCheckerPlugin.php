<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Plugin;

use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceOrderConstraintCheckerPluginInterface;

class CountingOrderConstraintCheckerPlugin implements AgenticCommerceOrderConstraintCheckerPluginInterface
{
    protected int $checkCount = 0;

    public function __construct(
        protected string $type,
        protected ?string $refusalReason
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function check(
        AgenticCommerceConsentConstraintTransfer $consentConstraintTransfer,
        QuoteTransfer $quoteTransfer,
        AgenticCommerceConsentTransfer $consentTransfer
    ): ?AgenticCommerceOrderPlacementCheckTransfer {
        $this->checkCount++;

        if ($this->refusalReason === null) {
            return null;
        }

        return (new AgenticCommerceOrderPlacementCheckTransfer())
            ->setResult(AgenticCommerceConfig::RESULT_REFUSED)
            ->setReason($this->refusalReason);
    }

    public function countChecks(): int
    {
        return $this->checkCount;
    }
}
