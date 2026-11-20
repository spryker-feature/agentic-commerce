<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;

class AuthorizationResponseUrlBuilder implements AuthorizationResponseUrlBuilderInterface
{
    public const string ERROR_ACCESS_DENIED = SharedAgenticCommerceConfig::ERROR_ACCESS_DENIED;

    public const string ERROR_SERVER_ERROR = SharedAgenticCommerceConfig::ERROR_SERVER_ERROR;

    protected const string PARAMETER_CODE = 'code';

    protected const string PARAMETER_STATE = 'state';

    protected const string PARAMETER_ISSUER = 'iss';

    protected const string PARAMETER_ERROR = 'error';

    protected const string PARAMETER_ERROR_DESCRIPTION = 'error_description';

    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    public function buildCodeResponseUrl(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        string $authorizationCode
    ): string {
        return $this->buildResponseUrl($agenticCommerceAuthorizationRequestTransfer, [static::PARAMETER_CODE => $authorizationCode]);
    }

    public function buildErrorResponseUrl(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        string $errorCode,
        ?string $errorDescription = null
    ): string {
        return $this->buildResponseUrl($agenticCommerceAuthorizationRequestTransfer, [
            static::PARAMETER_ERROR => $errorCode,
            static::PARAMETER_ERROR_DESCRIPTION => $errorDescription,
        ]);
    }

    /**
     * @param array<string, string|null> $responseParameters
     */
    protected function buildResponseUrl(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        array $responseParameters
    ): string {
        $redirectUri = $agenticCommerceAuthorizationRequestTransfer->getRedirectUriOrFail();
        $query = http_build_query(array_filter(
            $responseParameters + [
                static::PARAMETER_STATE => $agenticCommerceAuthorizationRequestTransfer->getState(),
                static::PARAMETER_ISSUER => $this->agenticCommerceConfig->getIssuer(),
            ],
            static fn (?string $value): bool => $value !== null && $value !== '',
        ), '', '&', PHP_QUERY_RFC3986);

        return sprintf('%s%s%s', $redirectUri, str_contains($redirectUri, '?') ? '&' : '?', $query);
    }
}
