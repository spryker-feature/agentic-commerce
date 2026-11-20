<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;

interface AgenticCommerceClientStatusUpdaterInterface
{
    public function updateAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer;

    public function updateAgenticCommerceClientStatusByCriteria(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer,
        string $status
    ): AgenticCommerceClientCollectionResponseTransfer;
}
