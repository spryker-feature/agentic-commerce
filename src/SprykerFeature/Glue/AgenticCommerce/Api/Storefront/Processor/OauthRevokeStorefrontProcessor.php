<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Processor;

use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationRequestTransfer;
use Psr\Http\Client\NetworkExceptionInterface;
use Spryker\ApiPlatform\State\Processor\AbstractStorefrontProcessor;
use Spryker\Shared\ZedRequest\Client\Exception\InvalidZedResponseException;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request\RequestMediaTypeCheckerInterface;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilderInterface;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OauthRevokeStorefrontProcessor extends AbstractStorefrontProcessor
{
    protected const string MEDIA_TYPE_FORM = 'application/x-www-form-urlencoded';

    protected const string ERROR_DESCRIPTION_FORM_BODY_ONLY = 'Parameters are accepted only in an application/x-www-form-urlencoded request body.';

    protected const string ERROR_DESCRIPTION_PARAMETER_MISSING = 'The token and client_id parameters are required.';

    protected const string ERROR_UNSUPPORTED_TOKEN_TYPE = 'unsupported_token_type';

    protected const string ERROR_DESCRIPTION_UNSUPPORTED_TOKEN_TYPE = 'Only refresh tokens can be revoked.';

    protected const string PARAMETER_TOKEN = 'token';

    protected const string PARAMETER_CLIENT_ID = 'client_id';

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected OauthProtocolResponseBuilderInterface $oauthProtocolResponseBuilder,
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected ZedCallFailureLogger $zedCallFailureLogger,
        protected RequestMediaTypeCheckerInterface $requestMediaTypeChecker,
        protected RequestClientDataMapperInterface $requestClientDataMapper
    ) {
    }

    protected function processPost(mixed $data): Response
    {
        if (!$this->agenticCommerceConfig->isConfigured()) {
            return $this->oauthProtocolResponseBuilder->createNotFoundResponse();
        }

        $request = $this->getRequest();

        if (!$this->requestMediaTypeChecker->isMediaType($request, static::MEDIA_TYPE_FORM)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_FORM_BODY_ONLY);
        }

        $token = $this->findParameter($request, static::PARAMETER_TOKEN);
        $clientIdentifier = $this->findParameter($request, static::PARAMETER_CLIENT_ID);

        if ($token === null || $clientIdentifier === null) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REQUEST, static::ERROR_DESCRIPTION_PARAMETER_MISSING);
        }

        if ($this->agenticCommerceClient->validateAgenticCommerceAccessToken($token)->getIsValid()) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(static::ERROR_UNSUPPORTED_TOKEN_TYPE, static::ERROR_DESCRIPTION_UNSUPPORTED_TOKEN_TYPE);
        }

        try {
            $this->agenticCommerceClient->revokeAgenticCommerceRefreshToken(
                (new AgenticCommerceRefreshTokenRevocationRequestTransfer())
                    ->setToken($token)
                    ->setClientIdentifier($clientIdentifier)
                    ->setRequestClientData($this->requestClientDataMapper->mapRequestToRequestClientData($request)),
            );
        } catch (RequestException) {
            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        } catch (InvalidZedResponseException | NetworkExceptionInterface $exception) {
            $this->zedCallFailureLogger->log($exception);

            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        }

        return $this->oauthProtocolResponseBuilder->createRevocationResponse();
    }

    protected function findParameter(Request $request, string $name): ?string
    {
        $value = $request->request->all()[$name] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
