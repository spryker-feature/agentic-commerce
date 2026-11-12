<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Host;

class HostNormalizer implements HostNormalizerInterface
{
    protected const string HOST_ROOT_LABEL_SUFFIX = '.';

    protected const string PERCENT_SIGN = '%';

    protected const string IPV6_LITERAL_START = '[';

    protected const string IPV6_LITERAL_END = ']';

    protected const string FUNCTION_IDN_TO_ASCII = 'idn_to_ascii';

    /**
     * The packed IPv6 prefixes that carry an IPv4 address in their last four bytes: IPv4-mapped (`::ffff:a.b.c.d`) and IPv4-compatible (`::a.b.c.d`).
     */
    protected const string IPV4_MAPPED_ADDRESS_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    protected const string IPV4_COMPATIBLE_ADDRESS_PREFIX = "\0\0\0\0\0\0\0\0\0\0\0\0";

    /**
     * `::` and `::1` share the IPv4-compatible prefix but are IPv6 addresses of their own.
     *
     * @var list<string>
     */
    protected const array IPV6_ADDRESSES_WITH_IPV4_COMPATIBLE_PREFIX = [
        "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0",
        "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\x01",
    ];

    /**
     * A last label a URL parser reads as a number: the host is then an IPv4 address, in whatever spelling.
     */
    protected const string PATTERN_NUMERIC_LAST_LABEL = '/(?:^|\.)(?:0x[0-9a-f]*|[0-9]+)$/';

    public function findNormalizedHost(string $host): ?string
    {
        $host = strtolower(rawurldecode($host));

        if ($host === '' || str_contains($host, static::PERCENT_SIGN)) {
            return null;
        }

        if ($this->isIpv6Literal($host)) {
            return $this->isEmbeddedIpv4Address($host) ? null : $this->canonicalizeIpv6Literal($host);
        }

        // The trailing dot is removed after the conversion, which maps a Unicode full stop such as `。` to `.`.
        $host = $this->removeTrailingDot($this->convertToAscii($host));

        if ($host === null || $host === '' || str_ends_with($host, static::HOST_ROOT_LABEL_SUFFIX) || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        // Only the dotted-decimal spelling of an IPv4 address is accepted, so `127.1`, `2130706433` and `0x7f.0.0.1` never pass for another host than `127.0.0.1`.
        if (preg_match(static::PATTERN_NUMERIC_LAST_LABEL, $host) === 1 && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return null;
        }

        return $host;
    }

    /**
     * @param array<string> $hosts
     *
     * @return list<string>
     */
    public function normalizeHosts(array $hosts): array
    {
        return array_values(array_filter(
            array_map($this->findNormalizedHost(...), $hosts),
            static fn (?string $host): bool => $host !== null,
        ));
    }

    protected function removeTrailingDot(?string $host): ?string
    {
        if ($host === null || !str_ends_with($host, static::HOST_ROOT_LABEL_SUFFIX)) {
            return $host;
        }

        return substr($host, 0, -1);
    }

    protected function isIpv6Literal(string $host): bool
    {
        return str_starts_with($host, static::IPV6_LITERAL_START)
            && str_ends_with($host, static::IPV6_LITERAL_END)
            && filter_var(substr($host, 1, -1), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    protected function isEmbeddedIpv4Address(string $host): bool
    {
        $packedAddress = (string)inet_pton(substr($host, 1, -1));

        if (str_starts_with($packedAddress, static::IPV4_MAPPED_ADDRESS_PREFIX)) {
            return true;
        }

        return str_starts_with($packedAddress, static::IPV4_COMPATIBLE_ADDRESS_PREFIX)
            && !in_array($packedAddress, static::IPV6_ADDRESSES_WITH_IPV4_COMPATIBLE_PREFIX, true);
    }

    protected function canonicalizeIpv6Literal(string $host): string
    {
        return static::IPV6_LITERAL_START . inet_ntop((string)inet_pton(substr($host, 1, -1))) . static::IPV6_LITERAL_END;
    }

    /**
     * Without the intl extension a non-ASCII host stays as it is and fails the host name validation.
     */
    protected function convertToAscii(string $host): ?string
    {
        if (!function_exists(static::FUNCTION_IDN_TO_ASCII)) {
            return $host;
        }

        $asciiHost = idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);

        return $asciiHost === false ? null : strtolower($asciiHost);
    }
}
