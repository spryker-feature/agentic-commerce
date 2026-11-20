<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Processor;

use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use JsonException;
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
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class OauthRegisterStorefrontProcessor extends AbstractStorefrontProcessor
{
    protected const string MEDIA_TYPE_JSON = 'application/json';

    protected const string ERROR_DESCRIPTION_JSON_BODY_ONLY = 'Client metadata is accepted only in an application/json request body.';

    protected const string ERROR_DESCRIPTION_BODY_TOO_LARGE = 'The request body must not be larger than %d bytes.';

    protected const string ERROR_DESCRIPTION_NOT_A_JSON_OBJECT = 'The request body must be a JSON object.';

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected OauthProtocolResponseBuilderInterface $oauthProtocolResponseBuilder,
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected ZedCallFailureLogger $zedCallFailureLogger,
        protected RequestMediaTypeCheckerInterface $requestMediaTypeChecker
    ) {
    }

    protected function processPost(mixed $data): Response
    {
        if (!$this->agenticCommerceConfig->isConfigured() || !$this->agenticCommerceConfig->isDynamicClientRegistrationEnabled()) {
            return $this->oauthProtocolResponseBuilder->createNotFoundResponse();
        }

        $request = $this->getRequest();

        if (!$this->requestMediaTypeChecker->isMediaType($request, static::MEDIA_TYPE_JSON)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, static::ERROR_DESCRIPTION_JSON_BODY_ONLY);
        }

        $rawRequest = (string)$request->getContent();
        $maxBytes = $this->agenticCommerceConfig->getDynamicClientRegistrationRequestMaxBytes();

        if (strlen($rawRequest) > $maxBytes) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(
                SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA,
                sprintf(static::ERROR_DESCRIPTION_BODY_TOO_LARGE, $maxBytes),
            );
        }

        if (!$this->isJsonObject($rawRequest)) {
            return $this->oauthProtocolResponseBuilder->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, static::ERROR_DESCRIPTION_NOT_A_JSON_OBJECT);
        }

        try {
            $registrationResponseTransfer = $this->agenticCommerceClient->registerAgenticCommerceClient(
                (new AgenticCommerceClientRegistrationRequestTransfer())->setRawRequest($rawRequest),
            );
        } catch (RequestException) {
            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        } catch (InvalidZedResponseException | NetworkExceptionInterface $exception) {
            $this->zedCallFailureLogger->log($exception);

            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        }

        if ($registrationResponseTransfer->getIsSuccessful()) {
            return $this->oauthProtocolResponseBuilder->createClientInformationResponse($registrationResponseTransfer);
        }

        return $this->oauthProtocolResponseBuilder->createErrorResponse(
            $registrationResponseTransfer->getErrorCode() ?? SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA,
            $registrationResponseTransfer->getErrorDescription(),
        );
    }

    protected function isJsonObject(string $rawRequest): bool
    {
        try {
            return json_decode($rawRequest, false, flags: JSON_THROW_ON_ERROR) instanceof stdClass;
        } catch (JsonException) {
            return false;
        }
    }
}
