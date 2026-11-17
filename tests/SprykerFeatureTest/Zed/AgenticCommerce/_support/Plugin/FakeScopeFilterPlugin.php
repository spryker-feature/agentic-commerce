<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Plugin;

use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeFilterPluginInterface;

class FakeScopeFilterPlugin implements AgenticCommerceScopeFilterPluginInterface
{
    /**
     * @param list<string> $removedScopes
     * @param list<string> $addedScopes
     */
    public function __construct(
        protected array $removedScopes,
        protected array $addedScopes = []
    ) {
    }

    public function filterScopes(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer, array $scopes): array
    {
        return array_values(array_merge(array_diff($scopes, $this->removedScopes), $this->addedScopes));
    }
}
