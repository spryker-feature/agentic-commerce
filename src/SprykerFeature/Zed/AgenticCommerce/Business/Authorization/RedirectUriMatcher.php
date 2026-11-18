<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class RedirectUriMatcher implements RedirectUriMatcherInterface
{
    protected const string LOOPBACK_SCHEME_PREFIX = 'http://';

    /**
     * What may follow a loopback host: an optional port, then nothing or a path or query without a fragment.
     */
    protected const string LOOPBACK_REMAINDER_PATTERN = '#^(?::(?<port>[0-9]{1,5}))?(?<pathAndQuery>(?:[/?][^\#]*)?)$#D';

    protected const int PORT_MINIMUM = 1;

    protected const int PORT_MAXIMUM = 65535;

    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    /**
     * Exact string comparison, except that the port is ignored for a registered loopback redirect URI
     * (RFC 8252, OAuth 2.0 for Native Apps, section 7.3); scheme, host, path and query still match exactly.
     *
     * @param list<string> $registeredRedirectUris
     */
    public function isMatching(string $requestedRedirectUri, array $registeredRedirectUris): bool
    {
        foreach ($registeredRedirectUris as $registeredRedirectUri) {
            if ($requestedRedirectUri === $registeredRedirectUri || $this->isMatchingLoopbackRedirectUri($requestedRedirectUri, $registeredRedirectUri)) {
                return true;
            }
        }

        return false;
    }

    public function isLoopbackRedirectUri(string $redirectUri): bool
    {
        return $this->stripLoopbackPort($redirectUri) !== null;
    }

    protected function isMatchingLoopbackRedirectUri(string $requestedRedirectUri, string $registeredRedirectUri): bool
    {
        $registeredRedirectUriWithoutPort = $this->stripLoopbackPort($registeredRedirectUri);

        return $registeredRedirectUriWithoutPort !== null
            && $registeredRedirectUriWithoutPort === $this->stripLoopbackPort($requestedRedirectUri);
    }

    /**
     * Returns the URI without its port when it is an `http` URI to a loopback host, `null` otherwise.
     */
    protected function stripLoopbackPort(string $uri): ?string
    {
        foreach ($this->agenticCommerceConfig->getLoopbackRedirectHosts() as $loopbackHost) {
            $origin = static::LOOPBACK_SCHEME_PREFIX . $loopbackHost;

            if (!str_starts_with($uri, $origin)) {
                continue;
            }

            if (preg_match(static::LOOPBACK_REMAINDER_PATTERN, substr($uri, strlen($origin)), $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
                continue;
            }

            if ($matches['port'] !== null && !$this->isPortInRange((int)$matches['port'])) {
                continue;
            }

            return $origin . ($matches['pathAndQuery'] ?? '');
        }

        return null;
    }

    protected function isPortInRange(int $port): bool
    {
        return $port >= static::PORT_MINIMUM && $port <= static::PORT_MAXIMUM;
    }
}
