<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Provider;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer;
use Psr\Http\Client\NetworkExceptionInterface;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use Spryker\Shared\ZedRequest\Client\Exception\InvalidZedResponseException;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilderInterface;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class OauthAuthorizationServerMetadataStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string DOCUMENT_KEY_AUTHORIZATION_DETAILS_TYPES_SUPPORTED = 'authorization_details_types_supported';

    public function __construct(
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected OauthProtocolResponseBuilderInterface $oauthProtocolResponseBuilder,
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected ZedCallFailureLogger $zedCallFailureLogger
    ) {
    }

    protected function provideItem(): Response
    {
        if (!$this->agenticCommerceConfig->isConfigured()) {
            return $this->oauthProtocolResponseBuilder->createNotFoundResponse();
        }

        $document = array_filter(
            $this->agenticCommerceService->buildAuthorizationServerMetadata()->toArray(true, false),
            static fn (mixed $value): bool => $value !== null,
        );

        try {
            $authorizationDetailsTypes = $this->getAuthorizationDetailsTypes();
        } catch (RequestException) {
            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        } catch (InvalidZedResponseException | NetworkExceptionInterface $exception) {
            $this->zedCallFailureLogger->log($exception);

            return $this->oauthProtocolResponseBuilder->createServerErrorResponse();
        }

        if ($authorizationDetailsTypes !== []) {
            $document[static::DOCUMENT_KEY_AUTHORIZATION_DETAILS_TYPES_SUPPORTED] = $authorizationDetailsTypes;
        }

        return $this->oauthProtocolResponseBuilder->createJsonDocumentResponse($document);
    }

    /**
     * @return list<string>
     */
    protected function getAuthorizationDetailsTypes(): array
    {
        return array_values(
            $this->agenticCommerceClient
                ->getAgenticCommerceAuthorizationDetailsTypeCollection(new AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer())
                ->getAuthorizationDetailsTypes(),
        );
    }
}
