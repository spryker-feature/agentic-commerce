<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Context;

use Generated\Shared\Transfer\AgenticCommerceContextTransfer;

interface AgenticCommerceContextReaderInterface
{
    public function findContext(): ?AgenticCommerceContextTransfer;
}
