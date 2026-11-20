<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Context;

use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\ApiPlatform\EventSubscriber\IdentityRequestSubscriber;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Exception\AgenticCommerceContextMissingException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AgenticCommerceRequestAttributeSeeder implements AgenticCommerceRequestAttributeSeederInterface
{
    protected const string ERROR_CONTEXT_MISSING = 'The request carries no delegated access context, so no principal can be seeded.';

    protected const array MAIN_REQUEST_ATTRIBUTES = [
        RequestAttribute::STORE_TRANSFER,
        RequestAttribute::LOCALE,
        RequestAttribute::LOCALE_TRANSFER,
    ];

    public function __construct(
        protected AgenticCommerceContextReaderInterface $agenticCommerceContextReader,
        protected TokenStorageInterface $tokenStorage,
        protected RequestStack $requestStack
    ) {
    }

    public function seedPrincipalAttributes(Request $subRequest): void
    {
        $agenticCommerceContextTransfer = $this->agenticCommerceContextReader->findContext();

        if ($agenticCommerceContextTransfer === null) {
            throw new AgenticCommerceContextMissingException(static::ERROR_CONTEXT_MISSING);
        }

        $customerTransfer = (new CustomerTransfer())->fromArray($agenticCommerceContextTransfer->getCustomerOrFail()->toArray(), true);

        $subRequest->attributes->set(RequestAttribute::CUSTOMER_TRANSFER, $customerTransfer);
        $subRequest->attributes->set(AgenticCommerceConfig::ATTRIBUTE_CUSTOMER_REFERENCE, $customerTransfer->getCustomerReference());
        $subRequest->attributes->set(AgenticCommerceConfig::ATTRIBUTE_ID_CUSTOMER, $customerTransfer->getIdCustomer());
        $subRequest->attributes->set(IdentityRequestSubscriber::ATTRIBUTE_OAUTH_IDENTITY_CLAIMS, $this->readIdentityClaims());

        $this->copyMainRequestAttributes($subRequest);
    }

    /**
     * @return array<string, mixed>
     */
    protected function readIdentityClaims(): array
    {
        $userIdentifier = $this->tokenStorage->getToken()?->getUserIdentifier();
        $identityClaims = $userIdentifier === null ? null : json_decode($userIdentifier, true);

        return is_array($identityClaims) ? $identityClaims : [];
    }

    protected function copyMainRequestAttributes(Request $subRequest): void
    {
        $mainRequest = $this->requestStack->getMainRequest();

        if ($mainRequest === null) {
            return;
        }

        foreach (static::MAIN_REQUEST_ATTRIBUTES as $attributeName) {
            if ($mainRequest->attributes->has($attributeName)) {
                $subRequest->attributes->set($attributeName, $mainRequest->attributes->get($attributeName));
            }
        }
    }
}
