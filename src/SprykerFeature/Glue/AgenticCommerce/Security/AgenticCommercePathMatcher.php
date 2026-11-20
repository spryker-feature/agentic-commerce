<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;

class AgenticCommercePathMatcher
{
    public function __construct(protected AgenticCommerceServiceInterface $agenticCommerceService)
    {
    }

    public function isDelegatedAccessPath(Request $request): bool
    {
        return $this->isMatching($this->agenticCommerceService->getDelegatedAccessPathPattern(), $request);
    }

    public function isPublicProtocolPath(Request $request): bool
    {
        return $this->isMatching(SharedAgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN, $request);
    }

    protected function isMatching(string $pathPattern, Request $request): bool
    {
        return (new PathRequestMatcher($pathPattern))->matches($request);
    }
}
