<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentConstraintWriter implements ConsentConstraintWriterInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $agenticCommerceConsentConstraintPlugins
     */
    public function __construct(
        protected array $agenticCommerceConsentConstraintPlugins,
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $agenticCommerceConsentConstraintTransfers
     */
    public function replaceConsentConstraints(int $idAgenticCommerceConsent, array $agenticCommerceConsentConstraintTransfers): void
    {
        $this->agenticCommerceEntityManager->deleteAgenticCommerceConsentConstraints($idAgenticCommerceConsent);

        foreach ($agenticCommerceConsentConstraintTransfers as $agenticCommerceConsentConstraintTransfer) {
            $this->agenticCommerceEntityManager->createAgenticCommerceConsentConstraint($idAgenticCommerceConsent, $agenticCommerceConsentConstraintTransfer);
        }
    }

    /**
     * @param list<string> $scopes
     */
    public function deleteInapplicableConsentConstraints(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, array $scopes): void
    {
        $idAgenticCommerceConsent = $agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail();
        $storedTypes = array_map(
            static fn (AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer): string => $agenticCommerceConsentConstraintTransfer->getTypeOrFail(),
            $this->agenticCommerceRepository->getAgenticCommerceConsentConstraintsByIdConsent($idAgenticCommerceConsent),
        );
        $inapplicableTypes = [];

        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if (
                in_array($agenticCommerceConsentConstraintPlugin->getType(), $storedTypes, true)
                && !$agenticCommerceConsentConstraintPlugin->isApplicable($agenticCommerceConsentTransfer->getPrincipalOrFail(), $scopes)
            ) {
                $inapplicableTypes[] = $agenticCommerceConsentConstraintPlugin->getType();
            }
        }

        if ($inapplicableTypes !== []) {
            $this->agenticCommerceEntityManager->deleteAgenticCommerceConsentConstraintsByTypes($idAgenticCommerceConsent, $inapplicableTypes);
        }
    }
}
