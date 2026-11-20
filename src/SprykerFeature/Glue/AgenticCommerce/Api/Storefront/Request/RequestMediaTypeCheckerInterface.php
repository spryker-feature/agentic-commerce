<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request;

use Symfony\Component\HttpFoundation\Request;

interface RequestMediaTypeCheckerInterface
{
    public function isMediaType(Request $request, string $mediaType): bool;
}
