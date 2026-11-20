<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommercePrincipalResolverPluginInterface;

class PrincipalResolver implements PrincipalResolverInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommercePrincipalResolverPluginInterface> $agenticCommercePrincipalResolverPlugins
     */
    public function __construct(protected array $agenticCommercePrincipalResolverPlugins)
    {
    }

    public function getCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer
    {
        foreach ($this->agenticCommercePrincipalResolverPlugins as $agenticCommercePrincipalResolverPlugin) {
            $agenticCommercePrincipalCollectionTransfer = $agenticCommercePrincipalResolverPlugin->getPrincipalCandidates($customerTransfer);

            if ($agenticCommercePrincipalCollectionTransfer->getPrincipals()->count() > 0) {
                return $agenticCommercePrincipalCollectionTransfer;
            }
        }

        return new AgenticCommercePrincipalCollectionTransfer();
    }

    public function findCandidate(
        AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer,
        CustomerTransfer $customerTransfer
    ): ?AgenticCommercePrincipalTransfer {
        foreach ($this->getCandidates($customerTransfer)->getPrincipals() as $candidateAgenticCommercePrincipalTransfer) {
            if (
                $candidateAgenticCommercePrincipalTransfer->getPrincipalType() === $agenticCommercePrincipalTransfer->getPrincipalType()
                && $candidateAgenticCommercePrincipalTransfer->getPrincipalReference() === $agenticCommercePrincipalTransfer->getPrincipalReference()
            ) {
                return $candidateAgenticCommercePrincipalTransfer;
            }
        }

        return null;
    }

    public function buildUserIdentifier(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): ?string
    {
        return $this->findPrincipalResolverPlugin($agenticCommercePrincipalTransfer)?->buildUserIdentifier($agenticCommercePrincipalTransfer);
    }

    public function hasPrincipalResolver(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): bool
    {
        return $this->findPrincipalResolverPlugin($agenticCommercePrincipalTransfer) !== null;
    }

    public function isPrincipalActive(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): bool
    {
        return $this->findPrincipalResolverPlugin($agenticCommercePrincipalTransfer)?->isPrincipalActive($agenticCommercePrincipalTransfer) ?? false;
    }

    protected function findPrincipalResolverPlugin(
        AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer
    ): ?AgenticCommercePrincipalResolverPluginInterface {
        foreach ($this->agenticCommercePrincipalResolverPlugins as $agenticCommercePrincipalResolverPlugin) {
            if ($agenticCommercePrincipalResolverPlugin->getPrincipalType() === $agenticCommercePrincipalTransfer->getPrincipalType()) {
                return $agenticCommercePrincipalResolverPlugin;
            }
        }

        return null;
    }
}
