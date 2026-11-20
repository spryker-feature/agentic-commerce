<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Processor;

use Generated\Shared\Transfer\GlueAuthenticationRequestContextTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Psr\Http\Client\NetworkExceptionInterface;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Client\Oauth\OauthClientInterface;
use Spryker\Shared\ZedRequest\Client\Exception\InvalidZedResponseException;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request\RequestMediaTypeCheckerInterface;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilderInterface;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OauthTokenStorefrontProcessor extends AbstractStorefrontProcessor
{
    protected const string MEDIA_TYPE_FORM = 'application/x-www-form-urlencoded';

    protected const string PARAMETER_GRANT_TYPE = 'grant_type';

    protected const string PARAMETER_CLIENT_ID = 'client_id';

    protected const string PARAMETER_CODE = 'code';

    protected const string PARAMETER_CODE_VERIFIER = 'code_verifier';

    protected const string PARAMETER_REDIRECT_URI = 'redirect_uri';

    protected const string PARAMETER_REFRESH_TOKEN = 'refresh_token';

    protected const string PARAMETER_SCOPE = 'scope';

    protected const string PARAMETER_RESOURCE = 'resource';

    protected const string PARAMETER_AUTHORIZATION_DETAILS = 'authorization_details';

    protected const string ERROR_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';

    protected const string ERROR_DESCRIPTION_FORM_BODY_ONLY = 'Parameters are accepted only in an application/x-www-form-urlencoded request body.';

    protected const string ERROR_DESCRIPTION_PARAMETER_REPEATED = 'A request parameter must not be included more than once.';

    protected const string ERROR_DESCRIPTION_AUTHORIZATION_DETAILS_NOT_SUPPORTED = 'authorization_details is not supported at the token endpoint';

    protected const int PARAMETER_OCCURRENCES_ALLOWED = 1;

    protected const string ERROR_DESCRIPTION_GRANT_TYPE_MISSING = 'The grant_type parameter is missing.';

    protected const string ERROR_DESCRIPTION_GRANT_TYPE_UNSUPPORTED = 'Only the authorization_code and refresh_token grant types are supported.';

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_GRANT_TYPES = [
        SharedAgenticCommerceConfig::GRANT_TYPE_AUTHORIZATION_CODE,
        SharedAgenticCommerceConfig::GRANT_TYPE_REFRESH_TOKEN,
    ];

    public function __construct(
        protected OauthClientInterface $oauthClient,
        protected OauthProtocolResponseBuilderInterface $oauthProtocolResponseBuilder,
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected ZedCallFailureLogger $zedCallFailureLogger,
        protected RequestMediaTypeCheckerInterface $requestMediaTypeChecker
    ) {
    }

    protected function processPost(mixed $data): Response
    {
        if (!$this->agenticCommerceConfig->isConfigured()) {
            return $this->oauthProtocolResponseBuilder->createNotFoundResponse();
        }

        $request = $this->getRequest();
        $errorResponse = $this->findRequestErrorResponse($request);

        if ($errorResponse !== null) {
            return $errorResponse;
        }

        try {
            $oauthResponseTransfer = $this->oauthClient->processAccessTokenRequest(
                $this->createOauthRequest($request),
            );
        } catch (RequestException) {
            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        } catch (InvalidZedResponseException | NetworkExceptionInterface $exception) {
            $this->zedCallFailureLogger->log($exception);

            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        }

        if ($oauthResponseTransfer->getIsValid()) {
            return $this->oauthProtocolResponseBuilder->createTokenResponse($oauthResponseTransfer);
        }

        return $this->oauthProtocolResponseBuilder->createErrorResponse(
            $oauthResponseTransfer->getError()?->getErrorType() ?? SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST,
            $oauthResponseTransfer->getError()?->getMessage(),
        );
    }

    protected function findRequestErrorResponse(Request $request): ?Response
    {
        if ($request->query->count() > 0 || !$this->requestMediaTypeChecker->isMediaType($request, static::MEDIA_TYPE_FORM)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_FORM_BODY_ONLY);
        }

        if ($this->hasRepeatedParameter($request)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_PARAMETER_REPEATED);
        }

        if ($request->request->has(static::PARAMETER_AUTHORIZATION_DETAILS)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_AUTHORIZATION_DETAILS, static::ERROR_DESCRIPTION_AUTHORIZATION_DETAILS_NOT_SUPPORTED);
        }

        $grantType = $this->findParameter($request, static::PARAMETER_GRANT_TYPE);

        if ($grantType === null) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_GRANT_TYPE_MISSING);
        }

        if (!in_array($grantType, static::SUPPORTED_GRANT_TYPES, true)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(static::ERROR_UNSUPPORTED_GRANT_TYPE, static::ERROR_DESCRIPTION_GRANT_TYPE_UNSUPPORTED);
        }

        return null;
    }

    protected function hasRepeatedParameter(Request $request): bool
    {
        $parameterNames = [];

        foreach (explode('&', (string)$request->getContent()) as $parameterPair) {
            parse_str($parameterPair, $parsedParameter);
            $parameterName = array_key_first($parsedParameter);

            if ($parameterName !== null) {
                $parameterNames[] = (string)$parameterName;
            }
        }

        return array_filter(array_count_values($parameterNames), static fn (int $occurrences): bool => $occurrences > static::PARAMETER_OCCURRENCES_ALLOWED) !== [];
    }

    protected function createOauthRequest(Request $request): OauthRequestTransfer
    {
        return (new OauthRequestTransfer())
            ->setGrantType($this->findParameter($request, static::PARAMETER_GRANT_TYPE))
            ->setClientId($this->findParameter($request, static::PARAMETER_CLIENT_ID))
            ->setCode($this->findParameter($request, static::PARAMETER_CODE))
            ->setCodeVerifier($this->findParameter($request, static::PARAMETER_CODE_VERIFIER))
            ->setRedirectUri($this->findParameter($request, static::PARAMETER_REDIRECT_URI))
            ->setRefreshToken($this->findParameter($request, static::PARAMETER_REFRESH_TOKEN))
            ->setScope($this->findParameter($request, static::PARAMETER_SCOPE))
            ->setResource($this->findParameter($request, static::PARAMETER_RESOURCE))
            ->setGlueAuthenticationRequestContext(
                (new GlueAuthenticationRequestContextTransfer())->setRequestApplication(SharedAgenticCommerceConfig::REQUEST_APPLICATION_AGENTIC_COMMERCE),
            );
    }

    protected function findParameter(Request $request, string $name): ?string
    {
        $value = $request->request->all()[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
