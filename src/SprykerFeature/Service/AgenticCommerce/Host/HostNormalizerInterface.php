<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Host;

/**
 * Brings a host into the one form every host comparison of the feature uses, so that a browser and the comparison see the same host.
 */
interface HostNormalizerInterface
{
    /**
     * Percent-decodes the host, lower-cases it, converts an internationalized host to its ASCII (punycode) form and then removes one trailing dot.
     * Writes a bracketed IPv6 address in its compressed form. Returns `null` when the result still contains a percent sign or a trailing dot, is an IPv4 address in another spelling than dotted decimal, or is neither a valid host name, an IPv4 address nor a bracketed IPv6 address.
     */
    public function findNormalizedHost(string $host): ?string;

    /**
     * Normalizes every host and leaves out the ones that are not valid.
     *
     * @param array<string> $hosts
     *
     * @return list<string>
     */
    public function normalizeHosts(array $hosts): array;
}
