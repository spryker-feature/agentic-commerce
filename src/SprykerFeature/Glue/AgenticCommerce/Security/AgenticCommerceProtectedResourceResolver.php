<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Psr\Log\LoggerInterface;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AgenticCommerceProtectedResourceResolver
{
    protected const string ERROR_DESCRIPTION_NO_PROTECTED_RESOURCE = 'The called path belongs to no protected resource.';

    protected const string LOG_MESSAGE_MARKED_OPERATION_WITHOUT_RESOURCE = 'Operation %s is marked for delegated access but its path is under no declared protected resource.';

    public function __construct(
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected LoggerInterface $logger,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function getProtectedResource(Request $request): AgenticCommerceProtectedResourceTransfer
    {
        $agenticCommerceProtectedResourceTransfer = $this->agenticCommerceService->findProtectedResourceByPath(rawurldecode($request->getPathInfo()));

        if ($agenticCommerceProtectedResourceTransfer !== null) {
            return $agenticCommerceProtectedResourceTransfer;
        }

        if ($this->agenticCommerceConfig->isConfigured()) {
            $operationName = $request->attributes->get(RequestAttribute::API_OPERATION_NAME);
            $this->logger->error(sprintf(
                static::LOG_MESSAGE_MARKED_OPERATION_WITHOUT_RESOURCE,
                is_string($operationName) ? $operationName : $request->getPathInfo(),
            ));
        }

        throw new AuthenticationException(static::ERROR_DESCRIPTION_NO_PROTECTED_RESOURCE);
    }
}
