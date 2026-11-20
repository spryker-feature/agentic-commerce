<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;

interface ConsentConstraintReaderInterface
{
    /**
     * Returns the stored constraints whose type a plugin declares, oldest first.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getConsentConstraints(string $consentUuid): array;

    /**
     * Returns the stored constraints whose type a plugin declares, oldest first.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getConsentConstraintsByIdConsent(int $idAgenticCommerceConsent): array;

    /**
     * Sets on every consent of the collection its stored constraints whose type a plugin declares, oldest first.
     */
    public function expandConsentCollectionWithConstraints(
        AgenticCommerceConsentCollectionTransfer $agenticCommerceConsentCollectionTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    public function getConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer;
}
