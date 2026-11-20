<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;

interface ConsentConstraintWriterInterface
{
    /**
     * Deletes the stored constraints of the consent, then stores the given ones; runs inside the caller's transaction.
     *
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $agenticCommerceConsentConstraintTransfers
     */
    public function replaceConsentConstraints(int $idAgenticCommerceConsent, array $agenticCommerceConsentConstraintTransfers): void;

    /**
     * Deletes the stored constraints whose plugin is not applicable for the consent's principal and the given scopes.
     * A stored type no plugin declares stays untouched.
     *
     * @param list<string> $scopes
     */
    public function deleteInapplicableConsentConstraints(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, array $scopes): void;
}
