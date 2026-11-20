<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Context;

use Generated\Shared\Transfer\AgenticCommerceContextTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCustomerRequestTransfer;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AgenticCommerceContextReader implements AgenticCommerceContextReaderInterface
{
    protected ?AgenticCommerceApiUser $memoizedAgenticCommerceApiUser = null;

    protected ?AgenticCommerceContextTransfer $memoizedAgenticCommerceContextTransfer = null;

    public function __construct(
        protected TokenStorageInterface $tokenStorage,
        protected AgenticCommerceClientInterface $agenticCommerceClient
    ) {
    }

    public function findContext(): ?AgenticCommerceContextTransfer
    {
        $agenticCommerceApiUser = $this->tokenStorage->getToken()?->getUser();

        if (!$agenticCommerceApiUser instanceof AgenticCommerceApiUser) {
            return null;
        }

        if ($this->memoizedAgenticCommerceApiUser !== $agenticCommerceApiUser) {
            $this->memoizedAgenticCommerceContextTransfer = $this->readContext($agenticCommerceApiUser);
            $this->memoizedAgenticCommerceApiUser = $agenticCommerceApiUser;
        }

        return $this->memoizedAgenticCommerceContextTransfer;
    }

    protected function readContext(AgenticCommerceApiUser $agenticCommerceApiUser): ?AgenticCommerceContextTransfer
    {
        $agenticCommerceAccessTokenTransfer = $agenticCommerceApiUser->getAgenticCommerceAccessToken();
        $agenticCommerceConsentTransfer = $agenticCommerceApiUser->getAgenticCommerceConsent();
        $principalType = $agenticCommerceConsentTransfer->getPrincipalTypeOrFail();
        $principalReference = $agenticCommerceConsentTransfer->getPrincipalReferenceOrFail();

        $agenticCommercePrincipalCustomerResponseTransfer = $this->agenticCommerceClient->getAgenticCommercePrincipalCustomer(
            (new AgenticCommercePrincipalCustomerRequestTransfer())
                ->setPrincipalType($principalType)
                ->setPrincipalReference($principalReference)
                ->setCustomerReference($agenticCommerceApiUser->getCustomerReference()),
        );

        $customerTransfer = $agenticCommercePrincipalCustomerResponseTransfer->getCustomer();

        if (!$agenticCommercePrincipalCustomerResponseTransfer->getIsActive() || $customerTransfer === null) {
            return null;
        }

        $companyUserTransfer = $customerTransfer->getCompanyUserTransfer();

        return (new AgenticCommerceContextTransfer())
            ->setClientIdentifier($agenticCommerceAccessTokenTransfer->getClientIdOrFail())
            ->setConsentUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->setPrincipalType($principalType)
            ->setPrincipalReference($principalReference)
            ->setCustomer($customerTransfer)
            ->setCompanyUserUuid($companyUserTransfer?->getUuid())
            ->setCompanyBusinessUnitUuid($companyUserTransfer?->getCompanyBusinessUnit()?->getUuid())
            ->setScopes(array_values(array_intersect($agenticCommerceAccessTokenTransfer->getScopes(), $agenticCommerceConsentTransfer->getGrantedScopes())))
            ->setStoreName($agenticCommerceAccessTokenTransfer->getStoreNameOrFail())
            ->setResourceName($agenticCommerceApiUser->getResourceName())
            ->setAuthorizationDetails($agenticCommerceAccessTokenTransfer->getAuthorizationDetails());
    }
}
