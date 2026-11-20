<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class AgenticCommerceRequestMatcher implements RequestMatcherInterface
{
    protected const string ATTRIBUTE_IS_DELEGATED_RESOURCE_REQUEST = '_agentic_commerce_delegated_resource_request';

    public function __construct(
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory
    ) {
    }

    public function matches(Request $request): bool
    {
        return (new PathRequestMatcher(SharedAgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN))->matches($request)
            || $this->isDelegatedResourceRequest($request);
    }

    public function isDelegatedResourceRequest(Request $request): bool
    {
        $isDelegatedResourceRequest = $request->attributes->get(static::ATTRIBUTE_IS_DELEGATED_RESOURCE_REQUEST);

        if (is_bool($isDelegatedResourceRequest)) {
            return $isDelegatedResourceRequest;
        }

        $isDelegatedResourceRequest = $this->agenticCommerceService->findProtectedResourceByPath(rawurldecode($request->getPathInfo())) !== null
            || $this->isMarkedOperation($request);
        $request->attributes->set(static::ATTRIBUTE_IS_DELEGATED_RESOURCE_REQUEST, $isDelegatedResourceRequest);

        return $isDelegatedResourceRequest;
    }

    protected function isMarkedOperation(Request $request): bool
    {
        $extraProperties = $this->findOperation($request)?->getExtraProperties() ?? [];

        return ($extraProperties[SharedAgenticCommerceConfig::EXTRA_PROPERTY_AGENTIC_COMMERCE] ?? false) === true;
    }

    protected function findOperation(Request $request): ?Operation
    {
        $operation = $request->attributes->get(RequestAttribute::API_OPERATION);

        if ($operation instanceof Operation) {
            return $operation;
        }

        $resourceClass = $request->attributes->get(RequestAttribute::API_RESOURCE_CLASS);
        $operationName = $request->attributes->get(RequestAttribute::API_OPERATION_NAME);

        if (!is_string($resourceClass) || !is_string($operationName)) {
            return null;
        }

        try {
            return $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        } catch (OperationNotFoundException | ResourceClassNotFoundException) {
            return null;
        }
    }
}
