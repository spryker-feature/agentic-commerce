<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Scope;

use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;

interface SupportedScopeReaderInterface
{
    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getSupportedScopes(): array;

    /**
     * @param list<string> $scopeNames
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getScopesByNames(array $scopeNames): array;

    public function getScopeCollection(AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer): AgenticCommerceScopeCollectionTransfer;

    /**
     * @return list<string>
     */
    public function getSupportedScopeNames(): array;
}
