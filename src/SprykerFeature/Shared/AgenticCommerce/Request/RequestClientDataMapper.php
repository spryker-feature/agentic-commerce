<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Shared\AgenticCommerce\Request;

use Generated\Shared\Transfer\AgenticCommerceRequestClientDataTransfer;
use Symfony\Component\HttpFoundation\Request;

class RequestClientDataMapper implements RequestClientDataMapperInterface
{
    protected const string HEADER_USER_AGENT = 'User-Agent';

    protected const int USER_AGENT_MAX_LENGTH = 512;

    protected const int USER_AGENT_OFFSET = 0;

    public function mapRequestToRequestClientData(Request $request): AgenticCommerceRequestClientDataTransfer
    {
        $userAgent = $request->headers->get(static::HEADER_USER_AGENT);

        return (new AgenticCommerceRequestClientDataTransfer())
            ->setIpAddress($request->getClientIp())
            ->setUserAgent($userAgent === null ? null : mb_substr($userAgent, static::USER_AGENT_OFFSET, static::USER_AGENT_MAX_LENGTH));
    }
}
