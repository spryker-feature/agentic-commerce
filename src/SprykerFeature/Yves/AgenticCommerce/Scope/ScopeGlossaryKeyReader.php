<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Scope;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceScopeConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;

class ScopeGlossaryKeyReader implements ScopeGlossaryKeyReaderInterface
{
    public function __construct(protected AgenticCommerceClientInterface $agenticCommerceClient)
    {
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return array<string, string> Scope to the glossary key of its label.
     */
    public function getScopeGlossaryKeys(ArrayObject $agenticCommerceConsentTransfers): array
    {
        $grantedScopes = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $grantedScopes = [...$grantedScopes, ...$agenticCommerceConsentTransfer->getGrantedScopes()];
        }

        if ($grantedScopes === []) {
            return [];
        }

        $agenticCommerceScopeCollectionTransfer = $this->agenticCommerceClient->getAgenticCommerceScopeCollection(
            (new AgenticCommerceScopeCriteriaTransfer())->setAgenticCommerceScopeConditions(
                (new AgenticCommerceScopeConditionsTransfer())->setNames(array_values(array_unique($grantedScopes))),
            ),
        );

        $scopeGlossaryKeys = [];

        foreach ($agenticCommerceScopeCollectionTransfer->getScopes() as $agenticCommerceScopeTransfer) {
            $scopeGlossaryKeys[$agenticCommerceScopeTransfer->getNameOrFail()] = $agenticCommerceScopeTransfer->getGlossaryKeyOrFail();
        }

        return $scopeGlossaryKeys;
    }
}
