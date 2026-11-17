<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use DateTimeInterface;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;

interface AgenticCommerceEntityManagerInterface
{
    public function createAgenticCommerceClient(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer
    ): AgenticCommerceClientTransfer;

    /**
     * Returns true only when a client with the identifier existed and its status changed.
     */
    public function updateAgenticCommerceClientStatus(string $clientIdentifier, string $status): bool;

    public function updateAgenticCommerceClientBlockedBy(string $clientIdentifier, string $blockedBy): void;

    public function updateAgenticCommerceClientMetadata(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer
    ): AgenticCommerceClientTransfer;

    /**
     * Returns true only when a client with the identifier existed and its scope ceiling changed.
     *
     * @param list<string> $scopeCeiling
     */
    public function updateAgenticCommerceClientScopeCeiling(string $clientIdentifier, array $scopeCeiling): bool;

    /**
     * Creates the consent lock row of the customer unless it exists; a row another request creates at the same time is kept.
     * Call it outside a transaction: a failed insert aborts an open PostgreSQL transaction.
     */
    public function createAgenticCommerceConsentLock(int $idCustomer): void;

    public function createAgenticCommerceConsent(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): AgenticCommerceConsentTransfer;

    /**
     * Moves an `active` consent to `revoked` and clears its refresh token identifier.
     * Returns false when the consent was not `active`, so of two concurrent revocations only one returns true.
     */
    public function revokeAgenticCommerceConsent(int $idAgenticCommerceConsent, string $revokedBy): bool;

    public function updateAgenticCommerceConsentLastUsedAt(int $idAgenticCommerceConsent, int $writeIntervalInSeconds): void;

    /**
     * Replaces the identifier only on an `active`, unexpired consent that still holds `$currentRefreshTokenIdentifier`,
     * so of two refreshes presenting the same token only one returns true.
     */
    public function updateAgenticCommerceConsentRefreshTokenIdentifier(
        int $idAgenticCommerceConsent,
        ?string $currentRefreshTokenIdentifier,
        string $newRefreshTokenIdentifier
    ): bool;

    /**
     * @param list<string> $grantedScopes
     */
    public function updateAgenticCommerceConsentGrantedScopes(int $idAgenticCommerceConsent, array $grantedScopes): void;

    public function createAgenticCommerceConsentEvent(
        AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer
    ): AgenticCommerceConsentEventTransfer;

    public function createAgenticCommerceConsentConstraint(
        int $idAgenticCommerceConsent,
        AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer
    ): void;

    public function deleteAgenticCommerceConsentConstraints(int $idAgenticCommerceConsent): void;

    /**
     * @param list<string> $types
     */
    public function deleteAgenticCommerceConsentConstraintsByTypes(int $idAgenticCommerceConsent, array $types): void;

    public function createAgenticCommerceSalesOrderAttribution(int $idSalesOrder, int $idAgenticCommerceConsentEvent): void;

    /**
     * Moves an `active` consent whose `expires_at` has passed to `expired`.
     * Returns false when the consent was not such a consent any more, so of two concurrent housekeeping runs only one returns true.
     */
    public function expireAgenticCommerceConsent(int $idAgenticCommerceConsent): bool;

    public function deleteAgenticCommerceClient(int $idAgenticCommerceClient): void;

    public function deleteOauthClientByIdentifier(string $clientIdentifier): void;

    /**
     * Returns the number of events deleted; an event already gone is not counted.
     *
     * @param list<int> $idAgenticCommerceConsentEvents
     */
    public function deleteAgenticCommerceConsentEvents(array $idAgenticCommerceConsentEvents): int;

    /**
     * @param list<int> $idAgenticCommerceConsentEvents
     */
    public function deleteAgenticCommerceConsentEventIpAddressesAndUserAgents(array $idAgenticCommerceConsentEvents): int;

    /**
     * @param list<int> $idAgenticCommerceConsents
     */
    public function deleteInactiveAgenticCommerceConsents(array $idAgenticCommerceConsents, DateTimeInterface $updatedBefore): int;

    public function createAgenticCommerceCartHandoff(
        AgenticCommerceCartHandoffTransfer $agenticCommerceCartHandoffTransfer
    ): AgenticCommerceCartHandoffTransfer;

    /**
     * Sets `used_at` of a hand-off that is neither used nor expired and returns true only when it did, so two concurrent redemptions cannot both win.
     */
    public function markAgenticCommerceCartHandoffUsed(int $idAgenticCommerceCartHandoff): bool;

    public function deleteOpenAgenticCommerceCartHandoffs(int $idAgenticCommerceConsent): int;
}
