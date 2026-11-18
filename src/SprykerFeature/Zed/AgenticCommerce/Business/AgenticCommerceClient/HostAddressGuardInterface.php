<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

/**
 * Keeps the fetch of a Client ID Metadata Document (CIMD) away from private networks, a defence against Server-Side Request Forgery (SSRF).
 */
interface HostAddressGuardInterface
{
    /**
     * Resolves the host once and returns the address to connect to, without brackets.
     * Returns `null` when the host does not resolve or when any of its addresses is not allowed.
     */
    public function resolveAllowedAddress(string $host): ?string;

    /**
     * Returns `true` for an IP address, with or without brackets, outside every private, loopback, link-local, reserved, documentation, multicast and protocol-assignment range.
     */
    public function isAllowedAddress(string $address): bool;

    /**
     * Compares the binary form of two IP addresses, with or without brackets; `false` when either is not an IP address.
     */
    public function isSameAddress(string $firstAddress, string $secondAddress): bool;
}
