<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class ConsentResolver implements ConsentResolverInterface
{
    protected const string ERROR_SCOPE_REQUIRED = 'agentic_commerce.consent.validation.scope_required';

    protected const string ERROR_SCOPE_NOT_GRANTABLE = 'agentic_commerce.consent.validation.scope_not_grantable';

    protected const string ERROR_DURATION_UNSUPPORTED = 'agentic_commerce.consent.validation.duration_unsupported';

    protected const string ERROR_CUSTOMER_NOT_FOUND = 'agentic_commerce.consent.validation.customer_not_found';

    protected const string ERROR_STORE_NOT_FOUND = 'agentic_commerce.consent.validation.store_not_found';

    protected const string ERROR_PRINCIPAL_NOT_FOUND = 'agentic_commerce.consent.validation.principal_not_found';

    public function __construct(
        protected PrincipalResolverInterface $principalResolver,
        protected ScopeNarrowerInterface $scopeNarrower,
        protected CustomerFacadeInterface $customerFacade,
        protected StoreFacadeInterface $storeFacade,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
     * @return list<string>
     */
    public function resolveConsent(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): array {
        $agenticCommerceConsentTransfer->setGrantedScopes(array_values(array_unique($agenticCommerceConsentTransfer->getGrantedScopes())));
        $errorMessages = $this->getSubmittedValueErrorMessages($agenticCommerceConsentTransfer);
        $storeTransfer = $this->findConsentStore((string)$agenticCommerceConsentTransfer->getStoreName());

        if ($storeTransfer === null) {
            $errorMessages[] = static::ERROR_STORE_NOT_FOUND;
        }

        $customerTransfer = $this->findCustomer((string)$agenticCommerceConsentTransfer->getCustomerReference());

        if ($customerTransfer === null) {
            $errorMessages[] = static::ERROR_CUSTOMER_NOT_FOUND;

            return $errorMessages;
        }

        $agenticCommercePrincipalTransfer = $this->findCandidatePrincipal($agenticCommerceConsentTransfer, $customerTransfer);
        $userIdentifier = $agenticCommercePrincipalTransfer !== null ? $this->principalResolver->buildUserIdentifier($agenticCommercePrincipalTransfer) : null;

        if ($agenticCommercePrincipalTransfer === null || $userIdentifier === null) {
            $errorMessages[] = static::ERROR_PRINCIPAL_NOT_FOUND;

            return $errorMessages;
        }

        if (!$this->isEveryGrantedScopeGrantable($agenticCommerceConsentTransfer, $agenticCommercePrincipalTransfer, $agenticCommerceAuthorizationResponseTransfer)) {
            $errorMessages[] = static::ERROR_SCOPE_NOT_GRANTABLE;
        }

        if ($storeTransfer === null || $errorMessages !== []) {
            return $errorMessages;
        }

        $agenticCommerceConsentTransfer
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setPrincipal($agenticCommercePrincipalTransfer)
            ->setPrincipalType($agenticCommercePrincipalTransfer->getPrincipalTypeOrFail())
            ->setPrincipalReference($agenticCommercePrincipalTransfer->getPrincipalReferenceOrFail())
            ->setIdStore($storeTransfer->getIdStoreOrFail())
            ->setStoreName($storeTransfer->getNameOrFail())
            ->setUserIdentifier($userIdentifier);

        return [];
    }

    /**
     * The consent belongs to the current store, the one the authorization request was validated against; a submitted
     * store name only has to name it.
     */
    protected function findConsentStore(string $storeName): ?StoreTransfer
    {
        $currentStoreTransfer = $this->storeFacade->getCurrentStore(true);

        if ($storeName !== '' && $storeName !== $currentStoreTransfer->getName()) {
            return null;
        }

        return $currentStoreTransfer;
    }

    /**
     * @return list<string>
     */
    protected function getSubmittedValueErrorMessages(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): array
    {
        $errorMessages = [];

        if ($agenticCommerceConsentTransfer->getGrantedScopes() === []) {
            $errorMessages[] = static::ERROR_SCOPE_REQUIRED;
        }

        if (!in_array($agenticCommerceConsentTransfer->getDurationInDays(), $this->agenticCommerceConfig->getConsentDurationsInDays(), true)) {
            $errorMessages[] = static::ERROR_DURATION_UNSUPPORTED;
        }

        return $errorMessages;
    }

    /**
     * The customer may untick offered scopes, never add one the client did not request or the principal may not hold.
     */
    protected function isEveryGrantedScopeGrantable(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): bool {
        $grantableScopes = $this->scopeNarrower->narrow(
            $agenticCommercePrincipalTransfer,
            array_values($agenticCommerceAuthorizationResponseTransfer->getGrantableScopes()),
        );

        return array_diff($agenticCommerceConsentTransfer->getGrantedScopes(), $grantableScopes) === [];
    }

    /**
     * Returns the matching server-built candidate, so resolvers and filters never see fields of the submitted principal.
     */
    protected function findCandidatePrincipal(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        CustomerTransfer $customerTransfer
    ): ?AgenticCommercePrincipalTransfer {
        $submittedAgenticCommercePrincipalTransfer = $agenticCommerceConsentTransfer->getPrincipal();

        if ($submittedAgenticCommercePrincipalTransfer === null) {
            return null;
        }

        return $this->principalResolver->findCandidate($submittedAgenticCommercePrincipalTransfer, $customerTransfer);
    }

    protected function findCustomer(string $customerReference): ?CustomerTransfer
    {
        if ($customerReference === '') {
            return null;
        }

        $customerResponseTransfer = $this->customerFacade->findCustomerByReference($customerReference);

        if (!$customerResponseTransfer->getIsSuccess()) {
            return null;
        }

        return $customerResponseTransfer->getCustomerTransfer();
    }
}
