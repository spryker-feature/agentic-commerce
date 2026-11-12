<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentRevoker implements ConsentRevokerInterface
{
    use TransactionTrait;

    protected const string ERROR_REVOKED_BY_UNSUPPORTED = 'agentic_commerce.consent.validation.revoked_by_unsupported';

    protected const string ERROR_EXACTLY_ONE_SELECTOR_REQUIRED = 'agentic_commerce.consent.validation.exactly_one_selector_required';

    protected const string ERROR_CONSENT_NOT_FOUND = 'agentic_commerce.consent.validation.not_found';

    protected const string ERROR_CONSENT_NOT_ACTIVE = 'agentic_commerce.consent.validation.not_active';

    protected const string ERROR_STATUS_TRANSITION_UNSUPPORTED = 'agentic_commerce.consent.validation.status_transition_unsupported';

    protected const int REQUIRED_SELECTOR_COUNT = 1;

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_REVOKED_BY_VALUES = [
        AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_COMPANY_ADMIN,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_REUSE_DETECTED,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CODE_REPLAY,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED,
    ];

    /**
     * @var array<string, string>
     */
    protected const array EVENTS_BY_REVOKED_BY = [
        AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_REUSE_DETECTED => AgenticCommerceConfig::CONSENT_EVENT_REUSE_DETECTED,
        AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CODE_REPLAY => AgenticCommerceConfig::CONSENT_EVENT_CODE_REPLAYED,
    ];

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface> $agenticCommerceConsentPostRevokePlugins
     */
    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected ConsentEventWriterInterface $consentEventWriter,
        protected array $agenticCommerceConsentPostRevokePlugins
    ) {
    }

    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        $agenticCommerceConsentCollectionResponseTransfer = new AgenticCommerceConsentCollectionResponseTransfer();
        $revocableAgenticCommerceConsentTransfers = [];

        foreach ($agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents() as $index => $agenticCommerceConsentTransfer) {
            $storedAgenticCommerceConsentTransfer = $this->findStoredConsent($agenticCommerceConsentTransfer);
            $errorMessage = $this->validateCustomerRevocation($agenticCommerceConsentTransfer, $storedAgenticCommerceConsentTransfer);

            if ($errorMessage !== null || $storedAgenticCommerceConsentTransfer === null) {
                $agenticCommerceConsentCollectionResponseTransfer->addError($this->createError($errorMessage ?? static::ERROR_CONSENT_NOT_FOUND, (string)$index));

                continue;
            }

            $revocableAgenticCommerceConsentTransfers[$index] = $storedAgenticCommerceConsentTransfer;
        }

        if (
            $agenticCommerceConsentCollectionRequestTransfer->getIsTransactional() !== false
            && $agenticCommerceConsentCollectionResponseTransfer->getErrors()->count() > 0
        ) {
            return $agenticCommerceConsentCollectionResponseTransfer->setAgenticCommerceConsents(
                $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents(),
            );
        }

        $revokedAgenticCommerceConsentTransfers = $this->revokeInTransaction(
            array_values($revocableAgenticCommerceConsentTransfers),
            AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER,
        );

        return $this->addCustomerRevocationResults(
            $agenticCommerceConsentCollectionResponseTransfer,
            $agenticCommerceConsentCollectionRequestTransfer,
            $revocableAgenticCommerceConsentTransfers,
            $revokedAgenticCommerceConsentTransfers,
        );
    }

    public function revokeAgenticCommerceConsentsBySystem(
        AgenticCommerceConsentRevocationRequestTransfer $agenticCommerceConsentRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        $agenticCommerceConsentCollectionResponseTransfer = new AgenticCommerceConsentCollectionResponseTransfer();
        $errorMessage = $this->validateRevocationRequest($agenticCommerceConsentRevocationRequestTransfer);

        if ($errorMessage !== null) {
            return $agenticCommerceConsentCollectionResponseTransfer->addError((new ErrorTransfer())->setMessage($errorMessage));
        }

        $agenticCommerceConsentTransfers = $this->getActiveConsents(
            $this->resolveConsentUuids($agenticCommerceConsentRevocationRequestTransfer),
        );
        $revokedAgenticCommerceConsentTransfers = $this->revokeInTransaction(
            $agenticCommerceConsentTransfers,
            $agenticCommerceConsentRevocationRequestTransfer->getRevokedByOrFail(),
        );

        foreach ($revokedAgenticCommerceConsentTransfers as $revokedAgenticCommerceConsentTransfer) {
            $agenticCommerceConsentCollectionResponseTransfer->addAgenticCommerceConsent($revokedAgenticCommerceConsentTransfer);
        }

        return $agenticCommerceConsentCollectionResponseTransfer;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer>
     */
    protected function revokeInTransaction(array $agenticCommerceConsentTransfers, string $revokedBy): array
    {
        if ($agenticCommerceConsentTransfers === []) {
            return [];
        }

        /** @var list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $revokedAgenticCommerceConsentTransfers */
        $revokedAgenticCommerceConsentTransfers = $this->getTransactionHandler()->handleTransaction(function () use ($agenticCommerceConsentTransfers, $revokedBy): array {
            return $this->executeRevokeTransaction($agenticCommerceConsentTransfers, $revokedBy);
        });

        $this->executeAgenticCommerceConsentPostRevokePlugins($revokedAgenticCommerceConsentTransfers);

        return $revokedAgenticCommerceConsentTransfers;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer>
     */
    protected function executeRevokeTransaction(array $agenticCommerceConsentTransfers, string $revokedBy): array
    {
        $revokedAgenticCommerceConsentTransfers = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $idAgenticCommerceConsent = $agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail();

            // The revoke is conditional on the stored status, so a concurrent revocation of the same consent loses here and writes no second event.
            if (!$this->agenticCommerceEntityManager->revokeAgenticCommerceConsent($idAgenticCommerceConsent, $revokedBy)) {
                continue;
            }

            $this->consentEventWriter->write(
                $idAgenticCommerceConsent,
                static::EVENTS_BY_REVOKED_BY[$revokedBy] ?? AgenticCommerceConfig::CONSENT_EVENT_REVOKED,
                $revokedBy,
                array_values($agenticCommerceConsentTransfer->getGrantedScopes()),
            );

            $revokedAgenticCommerceConsentTransfers[] = $this->agenticCommerceRepository->findAgenticCommerceConsentByUuid($agenticCommerceConsentTransfer->getUuidOrFail())
                ?? $agenticCommerceConsentTransfer;
        }

        return $revokedAgenticCommerceConsentTransfers;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $revokedAgenticCommerceConsentTransfers
     */
    protected function executeAgenticCommerceConsentPostRevokePlugins(array $revokedAgenticCommerceConsentTransfers): void
    {
        if ($revokedAgenticCommerceConsentTransfers === []) {
            return;
        }

        $agenticCommerceConsentCollectionTransfer = new AgenticCommerceConsentCollectionTransfer();

        foreach ($revokedAgenticCommerceConsentTransfers as $revokedAgenticCommerceConsentTransfer) {
            $agenticCommerceConsentCollectionTransfer->addAgenticCommerceConsent($revokedAgenticCommerceConsentTransfer);
        }

        foreach ($this->agenticCommerceConsentPostRevokePlugins as $agenticCommerceConsentPostRevokePlugin) {
            $agenticCommerceConsentPostRevokePlugin->postRevoke($agenticCommerceConsentCollectionTransfer);
        }
    }

    protected function validateRevocationRequest(
        AgenticCommerceConsentRevocationRequestTransfer $agenticCommerceConsentRevocationRequestTransfer
    ): ?string {
        if (!in_array($agenticCommerceConsentRevocationRequestTransfer->getRevokedBy(), static::SUPPORTED_REVOKED_BY_VALUES, true)) {
            return static::ERROR_REVOKED_BY_UNSUPPORTED;
        }

        $isPrincipalTypeGiven = (string)$agenticCommerceConsentRevocationRequestTransfer->getPrincipalType() !== '';
        $isPrincipalReferenceGiven = (string)$agenticCommerceConsentRevocationRequestTransfer->getPrincipalReference() !== '';

        if ($isPrincipalTypeGiven !== $isPrincipalReferenceGiven) {
            return static::ERROR_EXACTLY_ONE_SELECTOR_REQUIRED;
        }

        $givenSelectors = array_filter([
            $agenticCommerceConsentRevocationRequestTransfer->getConsentUuids() !== [],
            $isPrincipalTypeGiven,
            (string)$agenticCommerceConsentRevocationRequestTransfer->getCustomerReference() !== '',
            (string)$agenticCommerceConsentRevocationRequestTransfer->getClientIdentifier() !== '',
        ]);

        if (count($givenSelectors) !== static::REQUIRED_SELECTOR_COUNT) {
            return static::ERROR_EXACTLY_ONE_SELECTOR_REQUIRED;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function resolveConsentUuids(
        AgenticCommerceConsentRevocationRequestTransfer $agenticCommerceConsentRevocationRequestTransfer
    ): array {
        if ($agenticCommerceConsentRevocationRequestTransfer->getConsentUuids() !== []) {
            return array_values($agenticCommerceConsentRevocationRequestTransfer->getConsentUuids());
        }

        if ((string)$agenticCommerceConsentRevocationRequestTransfer->getPrincipalType() !== '') {
            return $this->agenticCommerceRepository->getActiveAgenticCommerceConsentUuidsByPrincipal(
                $agenticCommerceConsentRevocationRequestTransfer->getPrincipalTypeOrFail(),
                $agenticCommerceConsentRevocationRequestTransfer->getPrincipalReferenceOrFail(),
            );
        }

        if ((string)$agenticCommerceConsentRevocationRequestTransfer->getCustomerReference() !== '') {
            return $this->agenticCommerceRepository->getActiveAgenticCommerceConsentUuidsByCustomerReference(
                $agenticCommerceConsentRevocationRequestTransfer->getCustomerReferenceOrFail(),
            );
        }

        return $this->agenticCommerceRepository->getActiveAgenticCommerceConsentUuidsByClient(
            $agenticCommerceConsentRevocationRequestTransfer->getClientIdentifierOrFail(),
        );
    }

    /**
     * @param list<string> $consentUuids
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer>
     */
    protected function getActiveConsents(array $consentUuids): array
    {
        // An empty uuid condition would not filter at all, so no uuid must mean no consent.
        if ($consentUuids === []) {
            return [];
        }

        $agenticCommerceConsentCollectionTransfer = $this->agenticCommerceRepository->getAgenticCommerceConsentCollection(
            (new AgenticCommerceConsentCriteriaTransfer())->setAgenticCommerceConsentConditions(
                (new AgenticCommerceConsentConditionsTransfer())
                    ->setUuids($consentUuids)
                    ->addStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE),
            ),
        );

        return array_values($agenticCommerceConsentCollectionTransfer->getAgenticCommerceConsents()->getArrayCopy());
    }

    protected function findStoredConsent(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): ?AgenticCommerceConsentTransfer
    {
        $consentUuid = (string)$agenticCommerceConsentTransfer->getUuid();

        if ($consentUuid === '') {
            return null;
        }

        return $this->agenticCommerceRepository->findAgenticCommerceConsentByUuid($consentUuid);
    }

    protected function validateCustomerRevocation(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        ?AgenticCommerceConsentTransfer $storedAgenticCommerceConsentTransfer
    ): ?string {
        if ($agenticCommerceConsentTransfer->getStatus() !== AgenticCommerceConfig::CONSENT_STATUS_REVOKED) {
            return static::ERROR_STATUS_TRANSITION_UNSUPPORTED;
        }

        // Another customer's consent is reported exactly like a missing one, so the response never confirms that a uuid exists.
        if (
            $storedAgenticCommerceConsentTransfer === null
            || (string)$agenticCommerceConsentTransfer->getCustomerReference() === ''
            || $storedAgenticCommerceConsentTransfer->getCustomerReference() !== $agenticCommerceConsentTransfer->getCustomerReference()
        ) {
            return static::ERROR_CONSENT_NOT_FOUND;
        }

        if ($storedAgenticCommerceConsentTransfer->getStatus() !== AgenticCommerceConfig::CONSENT_STATUS_ACTIVE) {
            return static::ERROR_CONSENT_NOT_ACTIVE;
        }

        return null;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $revocableAgenticCommerceConsentTransfers
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $revokedAgenticCommerceConsentTransfers
     */
    protected function addCustomerRevocationResults(
        AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer,
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer,
        array $revocableAgenticCommerceConsentTransfers,
        array $revokedAgenticCommerceConsentTransfers
    ): AgenticCommerceConsentCollectionResponseTransfer {
        $revokedAgenticCommerceConsentTransfersByUuid = [];

        foreach ($revokedAgenticCommerceConsentTransfers as $revokedAgenticCommerceConsentTransfer) {
            $revokedAgenticCommerceConsentTransfersByUuid[$revokedAgenticCommerceConsentTransfer->getUuidOrFail()] = $revokedAgenticCommerceConsentTransfer;
        }

        foreach ($agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents() as $index => $agenticCommerceConsentTransfer) {
            if (!isset($revocableAgenticCommerceConsentTransfers[$index])) {
                $agenticCommerceConsentCollectionResponseTransfer->addAgenticCommerceConsent($agenticCommerceConsentTransfer);

                continue;
            }

            $consentUuid = $revocableAgenticCommerceConsentTransfers[$index]->getUuidOrFail();

            // A consent revoked concurrently, or listed twice in the request, is revoked once; every other item reports it as no longer active.
            if (!isset($revokedAgenticCommerceConsentTransfersByUuid[$consentUuid])) {
                $agenticCommerceConsentCollectionResponseTransfer
                    ->addError($this->createError(static::ERROR_CONSENT_NOT_ACTIVE, (string)$index))
                    ->addAgenticCommerceConsent($agenticCommerceConsentTransfer);

                continue;
            }

            $agenticCommerceConsentCollectionResponseTransfer->addAgenticCommerceConsent($revokedAgenticCommerceConsentTransfersByUuid[$consentUuid]);
            unset($revokedAgenticCommerceConsentTransfersByUuid[$consentUuid]);
        }

        return $agenticCommerceConsentCollectionResponseTransfer;
    }

    protected function createError(string $message, string $entityIdentifier): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage($message)
            ->setEntityIdentifier($entityIdentifier);
    }
}
