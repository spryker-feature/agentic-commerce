<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentRevokerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class AgenticCommerceClientStatusUpdater implements AgenticCommerceClientStatusUpdaterInterface
{
    use TransactionTrait;

    protected const string ERROR_STATUS_UNSUPPORTED = 'agentic_commerce.client.validation.status_unsupported';

    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_NOT_FOUND = 'agentic_commerce.client.validation.not_found';

    protected const string ERROR_CRITERIA_REQUIRED = 'agentic_commerce.client.validation.criteria_required';

    protected const string ERROR_REDIRECT_HOST_INVALID = 'agentic_commerce.client.validation.redirect_host_invalid';

    /**
     * Longest host that fits `spy_agentic_commerce_client.blocked_by` after its `redirect_host:` prefix.
     */
    protected const int MAX_FAMILY_BLOCK_HOST_LENGTH = 255;

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_STATUSES = [
        SharedAgenticCommerceConfig::CLIENT_STATUS_ACTIVE,
        SharedAgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
    ];

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected ConsentRevokerInterface $consentRevoker,
        protected RedirectUriValidatorInterface $redirectUriValidator,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AgenticCommerceConfig $agenticCommerceConfig
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

            if ($status !== SharedAgenticCommerceConfig::CLIENT_STATUS_BLOCKED) {
                continue;
            }

            // Runs even when the client was blocked already, so a repeated block also catches consents a failed earlier run left active.
            $this->addRevokedConsents($agenticCommerceClientCollectionResponseTransfer, $this->revokeConsentsOfBlockedClient($clientIdentifier));
        }

        return $agenticCommerceClientCollectionResponseTransfer;
    }

    protected function revokeConsentsOfBlockedClient(string $clientIdentifier): AgenticCommerceConsentCollectionResponseTransfer
    {
        return $this->consentRevoker->revokeAgenticCommerceConsentsBySystem(
            (new AgenticCommerceConsentRevocationRequestTransfer())
                ->setClientIdentifier($clientIdentifier)
                ->setRevokedBy(SharedAgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED),
        );
    }

    protected function addRevokedConsents(
        AgenticCommerceClientCollectionResponseTransfer $agenticCommerceClientCollectionResponseTransfer,
        AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer
    ): void {
        foreach ($agenticCommerceConsentCollectionResponseTransfer->getAgenticCommerceConsents() as $revokedAgenticCommerceConsentTransfer) {
            $agenticCommerceClientCollectionResponseTransfer->addAgenticCommerceConsent($revokedAgenticCommerceConsentTransfer);
        }
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

    public function updateAgenticCommerceClientStatusByCriteria(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer,
        string $status
    ): AgenticCommerceClientCollectionResponseTransfer {
        if (!in_array($status, static::SUPPORTED_STATUSES, true)) {
            return $this->createErrorResponse(static::ERROR_STATUS_UNSUPPORTED);
        }

        if ($this->hasInvalidRedirectHost($agenticCommerceClientCriteriaTransfer->getAgenticCommerceClientConditions())) {
            return $this->createErrorResponse(static::ERROR_REDIRECT_HOST_INVALID);
        }

        $agenticCommerceClientConditionsTransfer = $this->normalizeSelectors($agenticCommerceClientCriteriaTransfer->getAgenticCommerceClientConditions());

        if ($agenticCommerceClientConditionsTransfer === null) {
            return $this->createErrorResponse(static::ERROR_CRITERIA_REQUIRED);
        }

        $agenticCommerceClientCollectionTransfer = $this->agenticCommerceRepository->getAgenticCommerceClientCollection(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions($agenticCommerceClientConditionsTransfer),
        );
        $agenticCommerceClientCollectionResponseTransfer = new AgenticCommerceClientCollectionResponseTransfer();

        foreach ($agenticCommerceClientCollectionTransfer->getAgenticCommerceClients() as $agenticCommerceClientTransfer) {
            $agenticCommerceClientCollectionResponseTransfer->addAgenticCommerceClient($this->getTransactionHandler()->handleTransaction(
                fn (): AgenticCommerceClientTransfer => $this->updateStatusAndFamilyBlock($agenticCommerceClientTransfer, $agenticCommerceClientConditionsTransfer, $status),
            ));

            if ($status !== SharedAgenticCommerceConfig::CLIENT_STATUS_BLOCKED) {
                continue;
            }

            // Outside the status transaction, so the revocation commits batch by batch and writes its cache tombstones only after its own commit.
            $this->addRevokedConsents(
                $agenticCommerceClientCollectionResponseTransfer,
                $this->revokeConsentsOfBlockedClient($agenticCommerceClientTransfer->getClientIdentifierOrFail()),
            );
        }

        return $agenticCommerceClientCollectionResponseTransfer;
    }

    protected function updateStatusAndFamilyBlock(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer,
        AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer,
        string $status
    ): AgenticCommerceClientTransfer {
        $clientIdentifier = $agenticCommerceClientTransfer->getClientIdentifierOrFail();
        $this->agenticCommerceEntityManager->updateAgenticCommerceClientStatus($clientIdentifier, $status);
        $blockedBy = $status === SharedAgenticCommerceConfig::CLIENT_STATUS_BLOCKED
            ? $this->findFamilyBlock($agenticCommerceClientTransfer, $agenticCommerceClientConditionsTransfer)
            : null;

        if ($blockedBy !== null) {
            $this->agenticCommerceEntityManager->updateAgenticCommerceClientBlockedBy($clientIdentifier, $blockedBy);
        }

        return $this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientIdentifier) ?? $agenticCommerceClientTransfer;
    }

    /**
     * A client holds one family block, and the first one stays: a later family block never replaces it. The software id
     * wins over a redirect host when the criteria name both, because the software id names one product while a redirect
     * host can be shared by many; of several redirect hosts, the first one the client uses wins. A host too long for the
     * column records no family block; no DNS host is that long.
     */
    protected function findFamilyBlock(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer,
        AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer
    ): ?string {
        $softwareId = $agenticCommerceClientTransfer->getSoftwareId();

        if ($softwareId !== null && in_array($softwareId, $agenticCommerceClientConditionsTransfer->getSoftwareIds(), true)) {
            return SharedAgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId;
        }

        $clientRedirectHosts = array_map($this->redirectUriValidator->findHost(...), $agenticCommerceClientTransfer->getRedirectUris());

        foreach ($agenticCommerceClientConditionsTransfer->getRedirectHosts() as $redirectHost) {
            if (strlen($redirectHost) <= static::MAX_FAMILY_BLOCK_HOST_LENGTH && in_array($redirectHost, $clientRedirectHosts, true)) {
                return SharedAgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . $redirectHost;
            }
        }

        return null;
    }

    /**
     * A redirect host that is empty is left to `normalizeSelectors()`; any other host must be valid once normalized.
     * A loopback host is shared by every native client, and `BlockedClientMatcher` never applies a loopback family block, so it is refused.
     */
    protected function hasInvalidRedirectHost(?AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer): bool
    {
        $loopbackRedirectHosts = $this->agenticCommerceService->normalizeHosts($this->agenticCommerceConfig->getLoopbackRedirectHosts());

        foreach ($agenticCommerceClientConditionsTransfer?->getRedirectHosts() ?? [] as $redirectHost) {
            $redirectHost = trim($redirectHost);

            if ($redirectHost === '') {
                continue;
            }

            $normalizedRedirectHost = $this->agenticCommerceService->findNormalizedHost($redirectHost);

            if ($normalizedRedirectHost === null || in_array($normalizedRedirectHost, $loopbackRedirectHosts, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns null when no selector is given or one of them is empty, because an empty software id or host would match
     * every client without one. Hosts are normalized, so the family block records the one form the lookup compares with.
     */
    protected function normalizeSelectors(
        ?AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer
    ): ?AgenticCommerceClientConditionsTransfer {
        if ($agenticCommerceClientConditionsTransfer === null) {
            return null;
        }

        $clientIdentifiers = $agenticCommerceClientConditionsTransfer->getClientIdentifiers();
        $softwareIds = $agenticCommerceClientConditionsTransfer->getSoftwareIds();
        $redirectHosts = array_map(
            fn (string $redirectHost): string => (string)$this->agenticCommerceService->findNormalizedHost(trim($redirectHost)),
            $agenticCommerceClientConditionsTransfer->getRedirectHosts(),
        );

        if ($clientIdentifiers === [] && $softwareIds === [] && $redirectHosts === []) {
            return null;
        }

        if (in_array('', [...$clientIdentifiers, ...$softwareIds, ...$redirectHosts], true)) {
            return null;
        }

        return (clone $agenticCommerceClientConditionsTransfer)->setRedirectHosts(array_values($redirectHosts));
    }

    protected function createErrorResponse(string $message): AgenticCommerceClientCollectionResponseTransfer
    {
        return (new AgenticCommerceClientCollectionResponseTransfer())
            ->addError((new ErrorTransfer())->setMessage($message));
    }

    protected function createError(string $message, string $entityIdentifier): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage($message)
            ->setEntityIdentifier($entityIdentifier);
    }
}
