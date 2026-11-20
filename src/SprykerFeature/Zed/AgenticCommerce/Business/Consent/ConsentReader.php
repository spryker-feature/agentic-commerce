<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentReader implements ConsentReaderInterface
{
    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected ConsentConstraintReaderInterface $consentConstraintReader
    ) {
    }

    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        return $this->consentConstraintReader->expandConsentCollectionWithConstraints(
            $this->agenticCommerceRepository->getAgenticCommerceConsentCollection($agenticCommerceConsentCriteriaTransfer),
        );
    }
}
