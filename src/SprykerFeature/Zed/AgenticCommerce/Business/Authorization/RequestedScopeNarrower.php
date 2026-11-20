<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

class RequestedScopeNarrower implements RequestedScopeNarrowerInterface
{
    /**
     * @param list<string> $requestedScopes
     * @param list<string> $scopeCeiling
     * @param list<string> $supportedScopes
     *
     * @return list<string>
     */
    public function narrow(array $requestedScopes, array $scopeCeiling, array $supportedScopes): array
    {
        if ($requestedScopes === []) {
            return array_values(array_unique(array_intersect($scopeCeiling, $supportedScopes)));
        }

        return array_values(array_unique(array_intersect($requestedScopes, $scopeCeiling, $supportedScopes)));
    }
}
