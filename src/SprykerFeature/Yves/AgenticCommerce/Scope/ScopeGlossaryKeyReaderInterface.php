<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Scope;

use ArrayObject;

interface ScopeGlossaryKeyReaderInterface
{
    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return array<string, string> Scope to the glossary key of its label.
     */
    public function getScopeGlossaryKeys(ArrayObject $agenticCommerceConsentTransfers): array;
}
