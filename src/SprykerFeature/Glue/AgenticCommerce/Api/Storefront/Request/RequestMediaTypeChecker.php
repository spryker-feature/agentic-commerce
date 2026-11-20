<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request;

use Symfony\Component\HttpFoundation\Request;

class RequestMediaTypeChecker implements RequestMediaTypeCheckerInterface
{
    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    public function isMediaType(Request $request, string $mediaType): bool
    {
        $requestMediaType = explode(';', (string)$request->headers->get(static::HEADER_CONTENT_TYPE))[0];

        return strtolower(trim($requestMediaType)) === strtolower($mediaType);
    }
}
