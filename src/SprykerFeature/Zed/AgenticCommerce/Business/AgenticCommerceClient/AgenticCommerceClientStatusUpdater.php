<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentRevokerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class AgenticCommerceClientStatusUpdater implements AgenticCommerceClientStatusUpdaterInterface
{
    use TransactionTrait;

    protected const string ERROR_STATUS_UNSUPPORTED = 'agentic_commerce.client.validation.status_unsupported';

    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_NOT_FOUND = 'agentic_commerce.client.validation.not_found';

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_STATUSES = [
        AgenticCommerceConfig::CLIENT_STATUS_ACTIVE,
        AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
    ];

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected ConsentRevokerInterface $consentRevoker
    ) {
    }

    public function updateAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        $agenticCommerceClientCollectionResponseTransfer = new AgenticCommerceClientCollectionResponseTransfer();
        $validAgenticCommerceClientTransfers = [];

        foreach ($agenticCommerceClientCollectionRequestTransfer->getAgenticCommerceClients() as $index => $agenticCommerceClientTransfer) {
            $errorMessage = $this->validateAgenticCommerceClient($agenticCommerceClientTransfer);

            if ($errorMessage !== null) {
                $agenticCommerceClientCollectionResponseTransfer->addError($this->createError($errorMessage, (string)$index));

                continue;
            }

            $validAgenticCommerceClientTransfers[$index] = $agenticCommerceClientTransfer;
        }

        // An unset flag counts as transactional, so a caller that forgets it never gets a partial update.
        if ($agenticCommerceClientCollectionRequestTransfer->getIsTransactional() !== false) {
            if ($agenticCommerceClientCollectionResponseTransfer->getErrors()->count() > 0) {
                return $agenticCommerceClientCollectionResponseTransfer->setAgenticCommerceClients(
                    $agenticCommerceClientCollectionRequestTransfer->getAgenticCommerceClients(),
                );
            }

            return $this->getTransactionHandler()->handleTransaction(
                fn (): AgenticCommerceClientCollectionResponseTransfer => $this->executeUpdateAgenticCommerceClientCollectionTransaction(
                    array_values($validAgenticCommerceClientTransfers),
                    $agenticCommerceClientCollectionResponseTransfer,
                ),
            );
        }

        foreach ($agenticCommerceClientCollectionRequestTransfer->getAgenticCommerceClients() as $index => $agenticCommerceClientTransfer) {
            if (!isset($validAgenticCommerceClientTransfers[$index])) {
                $agenticCommerceClientCollectionResponseTransfer->addAgenticCommerceClient($agenticCommerceClientTransfer);

                continue;
            }

            $agenticCommerceClientCollectionResponseTransfer = $this->getTransactionHandler()->handleTransaction(
                fn (): AgenticCommerceClientCollectionResponseTransfer => $this->executeUpdateAgenticCommerceClientCollectionTransaction(
                    [$validAgenticCommerceClientTransfers[$index]],
                    $agenticCommerceClientCollectionResponseTransfer,
                ),
            );
        }

        return $agenticCommerceClientCollectionResponseTransfer;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceClientTransfer> $agenticCommerceClientTransfers
     */
    protected function executeUpdateAgenticCommerceClientCollectionTransaction(
        array $agenticCommerceClientTransfers,
        AgenticCommerceClientCollectionResponseTransfer $agenticCommerceClientCollectionResponseTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        foreach ($agenticCommerceClientTransfers as $agenticCommerceClientTransfer) {
            $clientIdentifier = $agenticCommerceClientTransfer->getClientIdentifierOrFail();
            $status = $agenticCommerceClientTransfer->getStatusOrFail();

            $this->agenticCommerceEntityManager->updateAgenticCommerceClientStatus($clientIdentifier, $status);
            $agenticCommerceClientCollectionResponseTransfer->addAgenticCommerceClient(
                $this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientIdentifier) ?? $agenticCommerceClientTransfer,
            );

            if ($status !== AgenticCommerceConfig::CLIENT_STATUS_BLOCKED) {
                continue;
            }

            // Runs even when the client was blocked already, so a repeated block also catches consents a failed earlier run left active.
            $agenticCommerceConsentCollectionResponseTransfer = $this->consentRevoker->revokeAgenticCommerceConsentsBySystem(
                (new AgenticCommerceConsentRevocationRequestTransfer())
                    ->setClientIdentifier($clientIdentifier)
                    ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED),
            );

            foreach ($agenticCommerceConsentCollectionResponseTransfer->getAgenticCommerceConsents() as $revokedAgenticCommerceConsentTransfer) {
                $agenticCommerceClientCollectionResponseTransfer->addAgenticCommerceConsent($revokedAgenticCommerceConsentTransfer);
            }
        }

        return $agenticCommerceClientCollectionResponseTransfer;
    }

    protected function validateAgenticCommerceClient(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): ?string
    {
        if (!in_array($agenticCommerceClientTransfer->getStatus(), static::SUPPORTED_STATUSES, true)) {
            return static::ERROR_STATUS_UNSUPPORTED;
        }

        $clientIdentifier = (string)$agenticCommerceClientTransfer->getClientIdentifier();

        if ($clientIdentifier === '') {
            return static::ERROR_CLIENT_IDENTIFIER_REQUIRED;
        }

        if ($this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientIdentifier) === null) {
            return static::ERROR_CLIENT_NOT_FOUND;
        }

        return null;
    }

    protected function createError(string $message, string $entityIdentifier): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage($message)
            ->setEntityIdentifier($entityIdentifier);
    }
}
