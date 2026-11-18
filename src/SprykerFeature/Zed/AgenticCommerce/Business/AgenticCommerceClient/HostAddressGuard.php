<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Symfony\Component\HttpFoundation\IpUtils;

class HostAddressGuard implements HostAddressGuardInterface
{
    /**
     * Ranges `IpUtils::PRIVATE_SUBNETS` does not cover: multicast and the IETF protocol assignments.
     *
     * @var list<string>
     */
    protected const array ADDITIONAL_REFUSED_SUBNETS = ['224.0.0.0/4', 'ff00::/8', '192.0.0.0/24'];

    protected const string IPV6_BRACKETS = '[]';

    protected const string RECORD_KEY_IPV6 = 'ipv6';

    public function resolveAllowedAddress(string $host): ?string
    {
        $addresses = $this->resolveAddresses(trim($host, static::IPV6_BRACKETS));

        foreach ($addresses as $address) {
            if (!$this->isAllowedAddress($address)) {
                return null;
            }
        }

        return $addresses[0] ?? null;
    }

    public function isAllowedAddress(string $address): bool
    {
        $address = trim($address, static::IPV6_BRACKETS);

        return filter_var($address, FILTER_VALIDATE_IP) !== false
            && !IpUtils::checkIp($address, IpUtils::PRIVATE_SUBNETS)
            && !IpUtils::checkIp($address, static::ADDITIONAL_REFUSED_SUBNETS);
    }

    public function isSameAddress(string $firstAddress, string $secondAddress): bool
    {
        $firstAddress = trim($firstAddress, static::IPV6_BRACKETS);
        $secondAddress = trim($secondAddress, static::IPV6_BRACKETS);

        if (filter_var($firstAddress, FILTER_VALIDATE_IP) === false || filter_var($secondAddress, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return inet_pton($firstAddress) === inet_pton($secondAddress);
    }

    /**
     * @return list<string>
     */
    protected function resolveAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = gethostbynamel($host) ?: [];

        foreach ($this->resolveIpv6Records($host) as $record) {
            $addresses[] = $record[static::RECORD_KEY_IPV6];
        }

        return $addresses;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolveIpv6Records(string $host): array
    {
        set_error_handler(static fn (): bool => true);

        try {
            return dns_get_record($host, DNS_AAAA) ?: [];
        } finally {
            restore_error_handler();
        }
    }
}
