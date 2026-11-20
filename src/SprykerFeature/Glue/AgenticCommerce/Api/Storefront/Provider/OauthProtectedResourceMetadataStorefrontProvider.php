<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Provider;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceMetadataTransfer;
use Spryker\ApiPlatform\State\Provider\AbstractStorefrontProvider;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilderInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Component\HttpFoundation\Response;

class OauthProtectedResourceMetadataStorefrontProvider extends AbstractStorefrontProvider
{
    protected const string URI_VARIABLE_RESOURCE_PATH = 'resourcePath';

    protected const string BEARER_METHOD_HEADER = 'header';

    public function __construct(
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected OauthProtocolResponseBuilderInterface $oauthProtocolResponseBuilder,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    protected function provideItem(): Response
    {
        if (!$this->agenticCommerceConfig->isConfigured()) {
            return $this->oauthProtocolResponseBuilder->createNotFoundResponse();
        }

        $agenticCommerceProtectedResourceTransfer = $this->agenticCommerceService->findProtectedResourceByMetadataPath(
            (string)($this->findUriVariable(static::URI_VARIABLE_RESOURCE_PATH) ?? ''),
        );

        if ($agenticCommerceProtectedResourceTransfer === null) {
            return $this->oauthProtocolResponseBuilder->createNotFoundResponse();
        }

        return $this->oauthProtocolResponseBuilder->createJsonDocumentResponse(
            (new AgenticCommerceProtectedResourceMetadataTransfer())
                ->setResource($agenticCommerceProtectedResourceTransfer->getResourceUriOrFail())
                ->setAuthorizationServers([$this->agenticCommerceConfig->getIssuer()])
                ->setScopesSupported(array_values($agenticCommerceProtectedResourceTransfer->getScopes()))
                ->setBearerMethodsSupported([static::BEARER_METHOD_HEADER])
                ->toArray(true, false),
        );
    }
}
