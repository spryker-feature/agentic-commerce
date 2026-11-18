<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

interface RedirectUriMatcherInterface
{
    /**
     * @param list<string> $registeredRedirectUris
     */
    public function isMatching(string $requestedRedirectUri, array $registeredRedirectUris): bool;

    public function isLoopbackRedirectUri(string $redirectUri): bool;
}
