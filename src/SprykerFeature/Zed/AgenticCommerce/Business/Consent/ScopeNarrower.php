<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;

class ScopeNarrower implements ScopeNarrowerInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeFilterPluginInterface> $agenticCommerceScopeFilterPlugins
     */
    public function __construct(
        protected array $agenticCommerceScopeFilterPlugins,
        protected SupportedScopeReaderInterface $supportedScopeReader
    ) {
    }

    public function narrow(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer, array $scopes): array
    {
        $filteredScopes = $scopes;

        foreach ($this->agenticCommerceScopeFilterPlugins as $agenticCommerceScopeFilterPlugin) {
            $filteredScopes = $agenticCommerceScopeFilterPlugin->filterScopes($agenticCommercePrincipalTransfer, $filteredScopes);
        }

        // Intersecting with the input keeps a plugin that adds a scope from ever widening the set.
        return array_values(array_unique(array_intersect($scopes, $filteredScopes)));
    }

    public function getGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer {
        $grantableScopes = $this->narrow(
            $agenticCommerceGrantableScopesRequestTransfer->getPrincipalOrFail(),
            array_values($agenticCommerceGrantableScopesRequestTransfer->getRequestedScopes()),
        );

        return (new AgenticCommerceGrantableScopesResponseTransfer())
            ->setGrantableScopes($grantableScopes)
            ->setScopes(new ArrayObject($this->supportedScopeReader->getScopesByNames($grantableScopes)));
    }
}
