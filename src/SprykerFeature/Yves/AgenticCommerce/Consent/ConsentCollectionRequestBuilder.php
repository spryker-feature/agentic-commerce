<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapperInterface;
use SprykerFeature\Yves\AgenticCommerce\Form\ConsentForm;
use SprykerFeature\Yves\AgenticCommerce\Form\DataProvider\ConsentFormDataProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ConsentCollectionRequestBuilder implements ConsentCollectionRequestBuilderInterface
{
    public function __construct(
        protected ConsentFormDataProviderInterface $consentFormDataProvider,
        protected RequestClientDataMapperInterface $requestClientDataMapper,
        protected StoreClientInterface $storeClient
    ) {
    }

    /**
     * @param array<string, mixed> $consentData
     * @param array<string, mixed> $formOptions
     */
    public function build(
        Request $request,
        array $consentData,
        array $formOptions,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        CustomerTransfer $customerTransfer
    ): AgenticCommerceConsentCollectionRequestTransfer {
        $principal = (string)$consentData[ConsentForm::FIELD_PRINCIPAL];

        return (new AgenticCommerceConsentCollectionRequestTransfer())
            ->setIsTransactional(true)
            ->setAuthorizationRequest($agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail())
            ->setRequestClientData($this->requestClientDataMapper->mapRequestToRequestClientData($request))
            ->addAgenticCommerceConsent(
                (new AgenticCommerceConsentTransfer())
                    ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                    ->setPrincipal($this->consentFormDataProvider->mapPrincipalValueToPrincipal($principal))
                    ->setStoreName($this->storeClient->getCurrentStore()->getNameOrFail())
                    ->setGrantedScopes(array_values($consentData[ConsentForm::FIELD_SCOPES]))
                    ->setDurationInDays((int)$consentData[ConsentForm::FIELD_DURATION_IN_DAYS])
                    ->setConstraints($this->consentFormDataProvider->mapConstraintValuesToConsentConstraints(
                        $consentData[ConsentForm::FIELD_CONSTRAINTS] ?? [],
                        $formOptions[ConsentForm::OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL][$principal] ?? [],
                    )),
            );
    }
}
