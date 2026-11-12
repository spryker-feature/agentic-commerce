<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Shared\AgenticCommerce\Request;

use Generated\Shared\Transfer\AgenticCommerceRequestClientDataTransfer;
use Symfony\Component\HttpFoundation\Request;

interface RequestClientDataMapperInterface
{
    /**
     * Maps `Request::getClientIp()` and the `User-Agent` header, truncated to 512 characters.
     */
    public function mapRequestToRequestClientData(Request $request): AgenticCommerceRequestClientDataTransfer;
}
