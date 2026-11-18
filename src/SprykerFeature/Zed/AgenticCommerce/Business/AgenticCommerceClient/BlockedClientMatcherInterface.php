<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

interface BlockedClientMatcherInterface
{
    /**
     * Returns `true` when a client is blocked by the family block of the software id (`software_id:<software id>`),
     * or when the software id is in `AgenticCommerceConfig::getClientDenylistSoftwareIds()`;
     * `false` for `null` or an empty software id. A client blocked on its own never counts.
     */
    public function hasBlockedClientWithSoftwareId(?string $softwareId): bool;

    /**
     * Returns `true` when a client other than `$ignoredClientIdentifier` is blocked by the family block of the
     * lower-case host of one of `$redirectUris` (`redirect_host:<host>`),
     * or when such a host is in `AgenticCommerceConfig::getClientDenylistHosts()`. Loopback redirect URIs are left out:
     * every native client shares them. A client blocked on its own never counts.
     *
     * @param array<string> $redirectUris
     */
    public function hasBlockedClientWithRedirectHost(array $redirectUris, ?string $ignoredClientIdentifier = null): bool;

    /**
     * Returns `true` when the software id is in `AgenticCommerceConfig::getClientDenylistSoftwareIds()`. Reads no client.
     */
    public function isDenylistedSoftwareId(?string $softwareId): bool;

    /**
     * Returns `true` when the lower-case host of one of `$redirectUris` is in `AgenticCommerceConfig::getClientDenylistHosts()`.
     * Loopback redirect URIs are left out. Reads no client.
     *
     * @param array<string> $redirectUris
     */
    public function hasDenylistedRedirectHost(array $redirectUris): bool;
}
