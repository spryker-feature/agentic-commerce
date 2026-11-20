<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use Symfony\Component\HttpFoundation\Request;

class AgenticCommerceAuthenticationChallengeBuilder
{
    public const string HEADER_WWW_AUTHENTICATE = 'WWW-Authenticate';

    public const string ERROR_INVALID_TOKEN = SharedAgenticCommerceConfig::ERROR_INVALID_TOKEN;

    public const string ERROR_INSUFFICIENT_SCOPE = 'insufficient_scope';

    public const string RESPONSE_KEY_ERROR = 'error';

    public const string RESPONSE_KEY_ERROR_DESCRIPTION = 'error_description';

    protected const string PARAMETER_RESOURCE_METADATA = 'resource_metadata';

    protected const string PARAMETER_ERROR = 'error';

    protected const string PARAMETER_SCOPE = 'scope';

    protected const string CHALLENGE_FORMAT = 'Bearer %s';

    protected const string PARAMETER_FORMAT = '%s="%s"';

    protected const string PARAMETER_SEPARATOR = ', ';

    protected const string ESCAPED_CHARACTERS = '"\\';

    protected const string SCOPE_SEPARATOR = ' ';

    public function __construct(
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected AgenticCommerceServiceInterface $agenticCommerceService
    ) {
    }

    public function buildUnauthorizedHeader(Request $request, ?string $error = null): string
    {
        $agenticCommerceProtectedResourceTransfer = $this->findProtectedResource($request);
        $scopes = $agenticCommerceProtectedResourceTransfer?->getScopes() ?? [];

        return $this->buildChallenge($request, $error, $scopes === [] ? null : implode(static::SCOPE_SEPARATOR, $scopes));
    }

    public function buildInsufficientScopeHeader(Request $request, ?string $scope = null): string
    {
        return $this->buildChallenge($request, static::ERROR_INSUFFICIENT_SCOPE, $scope);
    }

    protected function findProtectedResource(Request $request): ?AgenticCommerceProtectedResourceTransfer
    {
        return $this->agenticCommerceService->findProtectedResourceByPath(rawurldecode($request->getPathInfo()))
            ?? $this->agenticCommerceService->getProtectedResourcesFailClosed()[0]
            ?? null;
    }

    protected function buildChallenge(Request $request, ?string $error, ?string $scope): string
    {
        $parameters = [static::PARAMETER_RESOURCE_METADATA => $this->getProtectedResourceMetadataUrl($request)];

        if ($error !== null) {
            $parameters[static::PARAMETER_ERROR] = $error;
        }

        if ($scope !== null) {
            $parameters[static::PARAMETER_SCOPE] = $scope;
        }

        $formattedParameters = [];

        foreach ($parameters as $name => $value) {
            $formattedParameters[] = sprintf(static::PARAMETER_FORMAT, $name, addcslashes($value, static::ESCAPED_CHARACTERS));
        }

        return sprintf(static::CHALLENGE_FORMAT, implode(static::PARAMETER_SEPARATOR, $formattedParameters));
    }

    protected function getProtectedResourceMetadataUrl(Request $request): string
    {
        $agenticCommerceProtectedResourceTransfer = $this->findProtectedResource($request);

        if ($agenticCommerceProtectedResourceTransfer === null) {
            return $this->agenticCommerceConfig->getProtectedResourceMetadataUrl();
        }

        return $this->agenticCommerceService->getProtectedResourceMetadataUrl($agenticCommerceProtectedResourceTransfer);
    }
}
