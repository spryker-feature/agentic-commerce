<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsParserInterface;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface;

class ConsentConstraintValidator implements ConsentConstraintValidatorInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $agenticCommerceConsentConstraintPlugins
     */
    public function __construct(
        protected array $agenticCommerceConsentConstraintPlugins,
        protected StoreFacadeInterface $storeFacade,
        protected AuthorizationDetailsParserInterface $authorizationDetailsParser
    ) {
    }

    /**
     * A submitted constraint whose plugin is not applicable, or whose type no plugin declares, is dropped without an error.
     * A constraint the client requested through `authorization_details` must be submitted, and no wider than requested, when its plugin is applicable.
     *
     * @return list<\Generated\Shared\Transfer\ErrorTransfer>
     */
    public function validateConsentConstraints(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        ?string $authorizationDetails = null
    ): array {
        $agenticCommercePrincipalTransfer = $agenticCommerceConsentTransfer->getPrincipalOrFail();
        $scopes = array_values($agenticCommerceConsentTransfer->getGrantedScopes());
        $constraintFieldRequestTransfer = $this->createConstraintFieldRequest($agenticCommerceConsentTransfer);
        $parseResponseTransfer = $this->authorizationDetailsParser->parse($authorizationDetails, $constraintFieldRequestTransfer);

        if (!$parseResponseTransfer->getIsValid()) {
            return [(new ErrorTransfer())->setMessage(AgenticCommerceConfig::ERROR_INVALID_AUTHORIZATION_DETAILS)];
        }

        $requestedConstraintTransfers = $this->indexConstraintsByType($parseResponseTransfer->getRequestedConstraints());
        $submittedConstraintTransfers = $this->indexConstraintsByType($agenticCommerceConsentTransfer->getConstraints());
        $acceptedConstraintTransfers = [];
        $errorTransfers = [];

        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if (!$agenticCommerceConsentConstraintPlugin->isApplicable($agenticCommercePrincipalTransfer, $scopes)) {
                continue;
            }

            $submittedConstraintTransfer = $submittedConstraintTransfers[$agenticCommerceConsentConstraintPlugin->getType()] ?? null;
            $requestedConstraintTransfer = $requestedConstraintTransfers[$agenticCommerceConsentConstraintPlugin->getType()] ?? null;

            if ($submittedConstraintTransfer === null) {
                array_push($errorTransfers, ...$this->getMissingConstraintErrors($agenticCommerceConsentConstraintPlugin, $requestedConstraintTransfer, $constraintFieldRequestTransfer));

                continue;
            }

            $submittedConstraintErrorTransfers = $this->validateSubmittedConstraint(
                $agenticCommerceConsentConstraintPlugin,
                $submittedConstraintTransfer,
                $requestedConstraintTransfer,
                $constraintFieldRequestTransfer,
            );

            if ($submittedConstraintErrorTransfers !== []) {
                array_push($errorTransfers, ...$submittedConstraintErrorTransfers);

                continue;
            }

            $acceptedConstraintTransfers[] = $submittedConstraintTransfer;
        }

        $agenticCommerceConsentTransfer->setConstraints(new ArrayObject($acceptedConstraintTransfers));

        return $errorTransfers;
    }

    /**
     * @return list<\Generated\Shared\Transfer\ErrorTransfer>
     */
    protected function getMissingConstraintErrors(
        AgenticCommerceConsentConstraintPluginInterface $agenticCommerceConsentConstraintPlugin,
        ?AgenticCommerceConsentConstraintTransfer $requestedConstraintTransfer,
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): array {
        if ($requestedConstraintTransfer !== null) {
            return [$this->createConstraintError(AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUESTED_REMOVED, $agenticCommerceConsentConstraintPlugin->getType())];
        }

        if ($agenticCommerceConsentConstraintPlugin->isRequired($constraintFieldRequestTransfer->getPrincipalOrFail(), array_values($constraintFieldRequestTransfer->getScopes()))) {
            return [(new ErrorTransfer())->setMessage(AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUIRED)];
        }

        return [];
    }

    /**
     * @return list<\Generated\Shared\Transfer\ErrorTransfer>
     */
    protected function validateSubmittedConstraint(
        AgenticCommerceConsentConstraintPluginInterface $agenticCommerceConsentConstraintPlugin,
        AgenticCommerceConsentConstraintTransfer $submittedConstraintTransfer,
        ?AgenticCommerceConsentConstraintTransfer $requestedConstraintTransfer,
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): array {
        $pluginErrorTransfers = $agenticCommerceConsentConstraintPlugin->validate($submittedConstraintTransfer->getValue(), $constraintFieldRequestTransfer);

        if ($pluginErrorTransfers !== []) {
            return $pluginErrorTransfers;
        }

        if ($requestedConstraintTransfer !== null && !$this->isNarrowerOrEqualToRequested($agenticCommerceConsentConstraintPlugin, $requestedConstraintTransfer, $submittedConstraintTransfer)) {
            return [$this->createConstraintError(AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_WIDER_THAN_REQUESTED, $agenticCommerceConsentConstraintPlugin->getType())];
        }

        return [];
    }

    protected function isNarrowerOrEqualToRequested(
        AgenticCommerceConsentConstraintPluginInterface $agenticCommerceConsentConstraintPlugin,
        AgenticCommerceConsentConstraintTransfer $requestedConstraintTransfer,
        AgenticCommerceConsentConstraintTransfer $submittedConstraintTransfer
    ): bool {
        if (!$agenticCommerceConsentConstraintPlugin instanceof AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface) {
            return true;
        }

        return $agenticCommerceConsentConstraintPlugin->isNarrowerOrEqual($requestedConstraintTransfer->getValue(), $submittedConstraintTransfer->getValue());
    }

    protected function createConstraintError(string $message, string $constraintType): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage($message)
            ->setEntityIdentifier($constraintType);
    }

    protected function createConstraintFieldRequest(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): AgenticCommerceConstraintFieldRequestTransfer {
        $storeName = $agenticCommerceConsentTransfer->getStoreNameOrFail();

        return (new AgenticCommerceConstraintFieldRequestTransfer())
            ->setPrincipal($agenticCommerceConsentTransfer->getPrincipalOrFail())
            ->setScopes(array_values($agenticCommerceConsentTransfer->getGrantedScopes()))
            ->setStoreName($storeName)
            ->setCurrencyCode($this->storeFacade->findStoreByName($storeName)?->getDefaultCurrencyIsoCode());
    }

    /**
     * The first submitted constraint of a type counts.
     *
     * @param \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $agenticCommerceConsentConstraintTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    protected function indexConstraintsByType(ArrayObject $agenticCommerceConsentConstraintTransfers): array
    {
        $indexedConstraintTransfers = [];

        foreach ($agenticCommerceConsentConstraintTransfers as $agenticCommerceConsentConstraintTransfer) {
            $indexedConstraintTransfers[(string)$agenticCommerceConsentConstraintTransfer->getType()] ??= $agenticCommerceConsentConstraintTransfer;
        }

        return $indexedConstraintTransfers;
    }
}
