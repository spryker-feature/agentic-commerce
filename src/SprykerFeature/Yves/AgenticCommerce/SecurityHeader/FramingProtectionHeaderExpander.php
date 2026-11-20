<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\SecurityHeader;

use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FramingProtectionHeaderExpander implements FramingProtectionHeaderExpanderInterface
{
    protected const string REQUEST_ATTRIBUTE_ROUTE = '_route';

    protected const string HEADER_X_FRAME_OPTIONS = 'X-Frame-Options';

    protected const string HEADER_CONTENT_SECURITY_POLICY = 'Content-Security-Policy';

    protected const string X_FRAME_OPTIONS_DENY = 'DENY';

    protected const string DIRECTIVE_FRAME_ANCESTORS_NONE = "frame-ancestors 'none'";

    protected const string PATTERN_DIRECTIVE_FRAME_ANCESTORS = '/frame-ancestors[^;]*/i';

    protected const string DIRECTIVE_SEPARATOR = '; ';

    protected const string CACHE_CONTROL_DIRECTIVE_NO_STORE = 'no-store';

    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    public function expand(Request $request, Response $response): Response
    {
        if (!in_array($request->attributes->get(static::REQUEST_ATTRIBUTE_ROUTE), $this->agenticCommerceConfig->getFramingProtectedRouteNames(), true)) {
            return $response;
        }

        $response->headers->set(static::HEADER_X_FRAME_OPTIONS, static::X_FRAME_OPTIONS_DENY);
        $contentSecurityPolicies = $response->headers->all(static::HEADER_CONTENT_SECURITY_POLICY);
        $response->headers->set(
            static::HEADER_CONTENT_SECURITY_POLICY,
            $contentSecurityPolicies === [] ? [static::DIRECTIVE_FRAME_ANCESTORS_NONE] : array_map($this->buildContentSecurityPolicy(...), $contentSecurityPolicies),
        );
        $response->headers->addCacheControlDirective(static::CACHE_CONTROL_DIRECTIVE_NO_STORE);

        return $response;
    }

    protected function buildContentSecurityPolicy(?string $contentSecurityPolicy): string
    {
        if ($contentSecurityPolicy === null || $contentSecurityPolicy === '') {
            return static::DIRECTIVE_FRAME_ANCESTORS_NONE;
        }

        if (preg_match(static::PATTERN_DIRECTIVE_FRAME_ANCESTORS, $contentSecurityPolicy) === 1) {
            return (string)preg_replace(static::PATTERN_DIRECTIVE_FRAME_ANCESTORS, static::DIRECTIVE_FRAME_ANCESTORS_NONE, $contentSecurityPolicy);
        }

        return static::DIRECTIVE_FRAME_ANCESTORS_NONE . static::DIRECTIVE_SEPARATOR . $contentSecurityPolicy;
    }
}
