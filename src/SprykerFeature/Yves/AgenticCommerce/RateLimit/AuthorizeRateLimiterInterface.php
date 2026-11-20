<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\RateLimit;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

interface AuthorizeRateLimiterInterface
{
    public function handleRequest(RequestEvent $requestEvent): void;

    public function handleResponse(ResponseEvent $responseEvent): void;
}
