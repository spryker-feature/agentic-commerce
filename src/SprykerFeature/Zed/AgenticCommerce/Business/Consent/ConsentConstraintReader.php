<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentConstraintReader implements ConsentConstraintReaderInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $agenticCommerceConsentConstraintPlugins
     */
    public function __construct(
        protected array $agenticCommerceConsentConstraintPlugins,
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository
    ) {
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getConsentConstraints(string $consentUuid): array
    {
        return $this->filterDeclaredConstraints($this->agenticCommerceRepository->getAgenticCommerceConsentConstraintsByConsentUuid($consentUuid));
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getConsentConstraintsByIdConsent(int $idAgenticCommerceConsent): array
    {
        return $this->filterDeclaredConstraints($this->agenticCommerceRepository->getAgenticCommerceConsentConstraintsByIdConsent($idAgenticCommerceConsent));
    }

    public function expandConsentCollectionWithConstraints(
        AgenticCommerceConsentCollectionTransfer $agenticCommerceConsentCollectionTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        $idAgenticCommerceConsents = [];

        foreach ($agenticCommerceConsentCollectionTransfer->getAgenticCommerceConsents() as $agenticCommerceConsentTransfer) {
            $idAgenticCommerceConsents[] = $agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail();
        }

        $agenticCommerceConsentConstraintTransfersByIdConsent = $this->agenticCommerceRepository->getAgenticCommerceConsentConstraintsGroupedByIdConsent($idAgenticCommerceConsents);
        $constraintFieldTransfersByKey = [];

        foreach ($agenticCommerceConsentCollectionTransfer->getAgenticCommerceConsents() as $agenticCommerceConsentTransfer) {
            $agenticCommerceConsentTransfer->setConstraints(new ArrayObject($this->filterDeclaredConstraints(
                $agenticCommerceConsentConstraintTransfersByIdConsent[$agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail()] ?? [],
            )));
            $this->expandConstraintsWithValueLabels($agenticCommerceConsentTransfer, $constraintFieldTransfersByKey);
        }

        return $agenticCommerceConsentCollectionTransfer;
    }

    public function getConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer {
        $agenticCommercePrincipalTransfer = $agenticCommerceConstraintFieldRequestTransfer->getPrincipalOrFail();
        $scopes = array_values($agenticCommerceConstraintFieldRequestTransfer->getScopes());
        $agenticCommerceConstraintFieldCollectionTransfer = new AgenticCommerceConstraintFieldCollectionTransfer();

        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if (!$agenticCommerceConsentConstraintPlugin->isApplicable($agenticCommercePrincipalTransfer, $scopes)) {
                continue;
            }

            $agenticCommerceConstraintFieldCollectionTransfer->addField(
                $agenticCommerceConsentConstraintPlugin->getFieldDefinition($agenticCommerceConstraintFieldRequestTransfer)
                    ->setType($agenticCommerceConsentConstraintPlugin->getType())
                    ->setIsRequired($agenticCommerceConsentConstraintPlugin->isRequired($agenticCommercePrincipalTransfer, $scopes)),
            );
        }

        return $agenticCommerceConstraintFieldCollectionTransfer;
    }

    /**
     * The name shown for a selected value comes from the options of the constraint's own field; a value without an option is shown as it is.
     * A field definition can load its options, so it is built once per constraint type, store, principal and granted scopes within one call.
     *
     * @param array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer> $constraintFieldTransfersByKey
     */
    protected function expandConstraintsWithValueLabels(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        array &$constraintFieldTransfersByKey
    ): void {
        foreach ($agenticCommerceConsentTransfer->getConstraints() as $agenticCommerceConsentConstraintTransfer) {
            $agenticCommerceConsentConstraintPlugin = $this->findConstraintPlugin($agenticCommerceConsentConstraintTransfer->getTypeOrFail());

            if ($agenticCommerceConsentConstraintPlugin === null || !$this->hasListValue($agenticCommerceConsentConstraintTransfer)) {
                continue;
            }

            $constraintFieldKey = $this->buildConstraintFieldKey($agenticCommerceConsentConstraintPlugin->getType(), $agenticCommerceConsentTransfer);
            $constraintFieldTransfer = $constraintFieldTransfersByKey[$constraintFieldKey] ??= $agenticCommerceConsentConstraintPlugin->getFieldDefinition(
                (new AgenticCommerceConstraintFieldRequestTransfer())
                    ->setPrincipal(
                        (new AgenticCommercePrincipalTransfer())
                            ->setPrincipalType($agenticCommerceConsentTransfer->getPrincipalType())
                            ->setPrincipalReference($agenticCommerceConsentTransfer->getPrincipalReference()),
                    )
                    ->setScopes($agenticCommerceConsentTransfer->getGrantedScopes())
                    ->setStoreName($agenticCommerceConsentTransfer->getStoreName()),
            );
            $valueKey = $constraintFieldTransfer->getValueKey();

            if ($valueKey === null) {
                continue;
            }

            $valueLabels = [];

            foreach ($agenticCommerceConsentConstraintTransfer->getValue()[$valueKey] ?? [] as $selectedValue) {
                $valueLabels[(string)$selectedValue] = (string)($constraintFieldTransfer->getOptions()[$selectedValue] ?? $selectedValue);
            }

            $agenticCommerceConsentConstraintTransfer->setValueLabels($valueLabels);
        }
    }

    protected function buildConstraintFieldKey(string $constraintType, AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): string
    {
        $grantedScopes = $agenticCommerceConsentTransfer->getGrantedScopes();
        sort($grantedScopes);

        return json_encode([
            $constraintType,
            $agenticCommerceConsentTransfer->getStoreName(),
            $agenticCommerceConsentTransfer->getPrincipalType(),
            $agenticCommerceConsentTransfer->getPrincipalReference(),
            $grantedScopes,
        ], JSON_THROW_ON_ERROR);
    }

    protected function hasListValue(AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer): bool
    {
        return array_filter($agenticCommerceConsentConstraintTransfer->getValue(), 'is_array') !== [];
    }

    protected function findConstraintPlugin(string $constraintType): ?AgenticCommerceConsentConstraintPluginInterface
    {
        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if ($agenticCommerceConsentConstraintPlugin->getType() === $constraintType) {
                return $agenticCommerceConsentConstraintPlugin;
            }
        }

        return null;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $agenticCommerceConsentConstraintTransfers
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    protected function filterDeclaredConstraints(array $agenticCommerceConsentConstraintTransfers): array
    {
        $declaredTypes = array_map(
            static fn (AgenticCommerceConsentConstraintPluginInterface $agenticCommerceConsentConstraintPlugin): string => $agenticCommerceConsentConstraintPlugin->getType(),
            $this->agenticCommerceConsentConstraintPlugins,
        );

        return array_values(array_filter(
            $agenticCommerceConsentConstraintTransfers,
            static fn (AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer): bool => in_array($agenticCommerceConsentConstraintTransfer->getType(), $declaredTypes, true),
        ));
    }
}
