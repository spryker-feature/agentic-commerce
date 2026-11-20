<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Symfony\Component\HttpFoundation\Request;

class AgenticCommerceBearerTokenExtractor
{
    protected const string HEADER_AUTHORIZATION = 'Authorization';

    protected const string BEARER_TOKEN_PATTERN = '/^Bearer +(\S+)$/i';

    protected const int MATCH_INDEX_TOKEN = 1;

    public function findBearerToken(Request $request): ?string
    {
        if (preg_match(static::BEARER_TOKEN_PATTERN, (string)$request->headers->get(static::HEADER_AUTHORIZATION), $matches) !== 1) {
            return null;
        }

        return $matches[static::MATCH_INDEX_TOKEN];
    }
}
