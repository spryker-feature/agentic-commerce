<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Principal;

use ArrayObject;
use Generated\Shared\Transfer\CustomerTransfer;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;

class PrincipalLabelResolver implements PrincipalLabelResolverInterface
{
    protected const string PRINCIPAL_KEY_SEPARATOR = ':';

    public function __construct(protected AgenticCommerceClientInterface $agenticCommerceClient)
    {
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return array<string, string>
     */
    public function resolvePrincipalLabels(ArrayObject $agenticCommerceConsentTransfers, CustomerTransfer $customerTransfer): array
    {
        if ($agenticCommerceConsentTransfers->count() === 0) {
            return [];
        }

        $displayNamesByPrincipal = [];

        foreach ($this->agenticCommerceClient->getAgenticCommercePrincipalCandidates($customerTransfer)->getPrincipals() as $agenticCommercePrincipalTransfer) {
            $principalKey = $agenticCommercePrincipalTransfer->getPrincipalType() . static::PRINCIPAL_KEY_SEPARATOR . $agenticCommercePrincipalTransfer->getPrincipalReference();
            $displayNamesByPrincipal[$principalKey] = (string)$agenticCommercePrincipalTransfer->getDisplayName();
        }

        $principalLabels = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $principalKey = $agenticCommerceConsentTransfer->getPrincipalType() . static::PRINCIPAL_KEY_SEPARATOR . $agenticCommerceConsentTransfer->getPrincipalReference();
            $principalLabels[$agenticCommerceConsentTransfer->getUuidOrFail()] = ($displayNamesByPrincipal[$principalKey] ?? '') ?: (string)$agenticCommerceConsentTransfer->getPrincipalReference();
        }

        return $principalLabels;
    }
}
