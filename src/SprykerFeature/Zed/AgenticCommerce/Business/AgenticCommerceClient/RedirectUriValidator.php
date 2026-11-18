<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class RedirectUriValidator implements RedirectUriValidatorInterface
{
    protected const int URI_MAX_LENGTH = 2048;

    protected const string SCHEME_HTTPS = 'https';

    protected const string SCHEME_HTTP = 'http';

    protected const string PATTERN_WHITESPACE = '/\s/';

    protected const string WILDCARD = '*';

    public function __construct(
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function isRedirectUriAllowed(string $redirectUri): bool
    {
        if (str_contains($redirectUri, static::WILDCARD)) {
            return false;
        }

        $parsedUri = $this->parseUri($redirectUri);

        if ($parsedUri === null || !isset($parsedUri['scheme'], $parsedUri['host']) || isset($parsedUri['fragment'])) {
            return false;
        }

        if ($this->agenticCommerceService->findNormalizedHost((string)$parsedUri['host']) === null) {
            return false;
        }

        if ($parsedUri['scheme'] === static::SCHEME_HTTPS) {
            return true;
        }

        // RFC 8252 §7.3: plain http is acceptable only to a loopback host, for native applications running on the customer's own device.
        return $parsedUri['scheme'] === static::SCHEME_HTTP
            && in_array($parsedUri['host'], $this->agenticCommerceConfig->getLoopbackRedirectHosts(), true);
    }

    public function isOptionalHttpsUri(?string $uri): bool
    {
        if ($uri === null) {
            return true;
        }

        if (strlen($uri) > static::URI_MAX_LENGTH) {
            return false;
        }

        $parsedUri = $this->parseUri($uri);

        return $parsedUri !== null
            && ($parsedUri['scheme'] ?? null) === static::SCHEME_HTTPS
            && isset($parsedUri['host'])
            && !isset($parsedUri['fragment']);
    }

    public function findHost(string $uri): ?string
    {
        $host = (string)($this->parseUri($uri)['host'] ?? '');

        return $host !== '' ? $this->agenticCommerceService->findNormalizedHost($host) : null;
    }

    /**
     * Refuses whitespace and user information (`user:password@`), which would let a URI display one host while pointing to another.
     *
     * @return array<string, int|string>|null
     */
    protected function parseUri(string $uri): ?array
    {
        if (preg_match(static::PATTERN_WHITESPACE, $uri) === 1) {
            return null;
        }

        $parsedUri = parse_url($uri);

        if ($parsedUri === false || isset($parsedUri['user']) || isset($parsedUri['pass'])) {
            return null;
        }

        return $parsedUri;
    }
}
