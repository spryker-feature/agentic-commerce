<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Plugin;

use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommercePrincipalResolverPluginInterface;

class FakePrincipalResolverPlugin implements AgenticCommercePrincipalResolverPluginInterface
{
    /**
     * @param array<string, string> $userIdentifiersByPrincipalReference
     */
    public function __construct(
        protected string $principalType,
        protected array $userIdentifiersByPrincipalReference,
        protected bool $isActive = true
    ) {
    }

    public function getPrincipalType(): string
    {
        return $this->principalType;
    }

    public function getPrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer
    {
        $principalCollectionTransfer = new AgenticCommercePrincipalCollectionTransfer();

        foreach (array_keys($this->userIdentifiersByPrincipalReference) as $principalReference) {
            $principalCollectionTransfer->addPrincipal((new AgenticCommercePrincipalTransfer())
                ->setPrincipalType($this->principalType)
                ->setPrincipalReference((string)$principalReference)
                ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->setDisplayName((string)$principalReference));
        }

        return $principalCollectionTransfer;
    }

    public function buildUserIdentifier(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): ?string
    {
        return $this->userIdentifiersByPrincipalReference[$agenticCommercePrincipalTransfer->getPrincipalReferenceOrFail()] ?? null;
    }

    public function isPrincipalActive(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): bool
    {
        return $this->isActive;
    }
}
