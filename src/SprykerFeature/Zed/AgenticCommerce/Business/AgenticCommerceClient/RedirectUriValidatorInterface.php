<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

interface RedirectUriValidatorInterface
{
    /**
     * Accepts an `https` URI with a host and no fragment, or an `http` URI to a loopback host with any or no port.
     * The loopback host must be spelled exactly as configured (`127.0.0.1`, `localhost`, `[::1]`), because the matcher recognizes a loopback redirect URI only in that spelling.
     * Refuses whitespace, user information and a host that is not a valid host once percent-decoded.
     */
    public function isRedirectUriAllowed(string $redirectUri): bool;

    /**
     * Accepts `null`, or an `https` URI with a host, without a fragment, user information or whitespace and at most 2048 characters long.
     */
    public function isOptionalHttpsUri(?string $uri): bool;

    /**
     * Returns the normalized host (see `HostNormalizerInterface::findNormalizedHost()`), or `null` when the URI cannot be parsed safely or has no valid host.
     */
    public function findHost(string $uri): ?string;
}
