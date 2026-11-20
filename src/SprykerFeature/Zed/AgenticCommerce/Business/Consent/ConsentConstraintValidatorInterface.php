<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;

interface ConsentConstraintValidatorInterface
{
    /**
     * Requires the resolved principal, the final granted scopes and the store name on the consent.
     * Leaves on the consent only the valid constraints of applicable plugins, in plugin order.
     * Parses `$authorizationDetails` (RFC 9396) with the grantable scopes and the store of the consent: a failing parameter returns one `invalid_authorization_details` error.
     * Returns an error for every applicable constraint the client requested that is not submitted or is wider than requested, with the constraint type as entity identifier.
     *
     * @return list<\Generated\Shared\Transfer\ErrorTransfer>
     */
    public function validateConsentConstraints(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        ?string $authorizationDetails = null
    ): array;
}
