<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use DateTimeImmutable;
use DateTimeInterface;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceSalesOrderAttributionTransfer;

/**
 * "Active" always means status `active` and `expires_at` in the future.
 */
interface AgenticCommerceRepositoryInterface
{
    public function findAgenticCommerceClientByClientIdentifier(string $clientIdentifier): ?AgenticCommerceClientTransfer;

    public function getAgenticCommerceClientCollection(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer
    ): AgenticCommerceClientCollectionTransfer;

    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    public function countAgenticCommerceConsents(AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer): int;

    public function findAgenticCommerceConsentByUuid(string $uuid): ?AgenticCommerceConsentTransfer;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByPrincipal(string $principalType, string $principalReference): array;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
        string $clientIdentifier,
        string $principalType,
        string $principalReference,
        int $idStore
    ): array;

    /**
     * Same selection as `getActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore()`, as a locking read: the consents stay locked until the open transaction ends.
     * Must run before any plain read of its transaction; only the consent lock of the customer may come before it.
     *
     * @return list<string>
     */
    public function lockActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
        string $clientIdentifier,
        string $principalType,
        string $principalReference,
        int $idStore
    ): array;

    /**
     * Locks the consent lock row of the customer until the open transaction ends, so consents of one customer are created one after another.
     * Must be the first statement of its transaction.
     * Returns `false` when the row does not exist; `AgenticCommerceEntityManagerInterface::createAgenticCommerceConsentLock()` creates it.
     */
    public function lockAgenticCommerceConsentLockByIdCustomer(int $idCustomer): bool;

    /**
     * Locks the consent row until the open transaction ends, so orders under one consent are saved one after another.
     * Returns `false` when no consent has the uuid.
     */
    public function lockAgenticCommerceConsentByUuid(string $consentUuid): bool;

    /**
     * Locks the client row until the open transaction ends, so a client is deleted only while no consent can be created for it.
     * Must be the first statement of its transaction.
     * Returns the client's OAuth client identifier, or `null` when no client has the id.
     */
    public function lockAgenticCommerceClientById(int $idAgenticCommerceClient): ?string;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByClient(string $clientIdentifier): array;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByCustomerReference(string $customerReference): array;

    /**
     * Returns the events oldest first.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer>
     */
    public function getAgenticCommerceConsentEvents(int $idAgenticCommerceConsent): array;

    /**
     * Returns every stored constraint of the consent, oldest first, whether or not a plugin declares its type.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getAgenticCommerceConsentConstraintsByIdConsent(int $idAgenticCommerceConsent): array;

    /**
     * Returns every stored constraint of the consents, oldest first, whether or not a plugin declares its type; a consent without constraints has no entry.
     *
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>>
     */
    public function getAgenticCommerceConsentConstraintsGroupedByIdConsent(array $idAgenticCommerceConsents): array;

    /**
     * Returns every stored constraint of the consent, oldest first, whether or not a plugin declares its type.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getAgenticCommerceConsentConstraintsByConsentUuid(string $consentUuid): array;

    /**
     * Returns the newest event of the consent among `$events`; of two events written in the same second the later written one.
     *
     * @param list<string> $events
     */
    public function findLatestAgenticCommerceConsentEvent(string $consentUuid, array $events): ?AgenticCommerceConsentEventTransfer;

    /**
     * Returns the event expanded with its consent's uuid, effective status, revocation time, client identifier, client URI and customer.
     */
    public function findAgenticCommerceConsentEventById(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer;

    /**
     * Returns the attribution with only the id of its consent event set in `consentEvent`, or no `consentEvent` once the event is gone.
     */
    public function findAgenticCommerceSalesOrderAttribution(int $idSalesOrder): ?AgenticCommerceSalesOrderAttributionTransfer;

    /**
     * Returns consents with status `active` whose `expires_at` has passed and whose id is greater than `$idAgenticCommerceConsentAfter`, ordered by id.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer>
     */
    public function getAgenticCommerceConsentsPastExpiry(int $idAgenticCommerceConsentAfter, int $limit): array;

    /**
     * Returns the ids of clients of the registration types created before `$createdBefore` with an id greater than `$idAgenticCommerceClientAfter`, ordered by id.
     *
     * @param list<string> $registrationTypes
     *
     * @return list<int>
     */
    public function getAgenticCommerceClientIdsCreatedBefore(
        array $registrationTypes,
        DateTimeInterface $createdBefore,
        int $idAgenticCommerceClientAfter,
        int $limit
    ): array;

    /**
     * Returns those of the clients that at least one consent references, whatever its status.
     *
     * @param list<int> $idAgenticCommerceClients
     *
     * @return list<int>
     */
    public function getAgenticCommerceClientIdsWithConsent(array $idAgenticCommerceClients): array;

    /**
     * Returns the ids of events created before `$createdBefore` with an id greater than `$idAgenticCommerceConsentEventAfter`, ordered by id.
     *
     * @return list<int>
     */
    public function getAgenticCommerceConsentEventIdsCreatedBefore(
        DateTimeInterface $createdBefore,
        int $idAgenticCommerceConsentEventAfter,
        int $limit
    ): array;

    /**
     * Returns the ids of events created before `$createdBefore` that still hold an IP address or a user agent, with an id greater than `$idAgenticCommerceConsentEventAfter`, ordered by id.
     *
     * @return list<int>
     */
    public function getAgenticCommerceConsentEventIdsWithClientDataCreatedBefore(
        DateTimeInterface $createdBefore,
        int $idAgenticCommerceConsentEventAfter,
        int $limit
    ): array;

    /**
     * Returns those of the events that are the newest event of their consent: the latest `created_at`, of two in the same second the higher id.
     *
     * @param list<int> $idAgenticCommerceConsentEvents
     *
     * @return list<int>
     */
    public function getNewestAgenticCommerceConsentEventIds(array $idAgenticCommerceConsentEvents): array;

    /**
     * Returns the ids of `revoked` and `expired` consents last changed before `$updatedBefore` with an id greater than `$idAgenticCommerceConsentAfter`, ordered by id.
     *
     * @return list<int>
     */
    public function getInactiveAgenticCommerceConsentIds(DateTimeInterface $updatedBefore, int $idAgenticCommerceConsentAfter, int $limit): array;

    /**
     * Returns the ids of every event of the consents, grouped by consent id; a consent without events has no entry.
     *
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, list<int>>
     */
    public function getAgenticCommerceConsentEventIdsGroupedByIdConsent(array $idAgenticCommerceConsents): array;

    /**
     * Returns the client name of the newest `granted` or `re_consented` event per consent: the name the customer saw at consent.
     * A consent without such an event, or whose event holds no client name, is not in the result.
     *
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, string>
     */
    public function getClientNamesAtConsentIndexedByIdConsent(array $idAgenticCommerceConsents): array;

    /**
     * Returns those of the events an order attribution references.
     * For a referenced event without a client name, such as `scopes_narrowed`, the latest `granted` or `re_consented` event of its consent counts as
     * referenced too: the Back Office block of the order falls back to it (`findLatestAgenticCommerceConsentEvent()`, same order).
     *
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function getReferencedConsentEventIds(array $consentEventIds): array;

    /**
     * Returns the latest grand total of every order attributed to the consent and placed at or after `$placedSince`,
     * leaving out orders whose items are all in one of `$canceledOmsStateNames`.
     *
     * @param list<string> $canceledOmsStateNames
     *
     * @return array<int, int>
     */
    public function getAgenticCommerceAttributedGrandTotalsIndexedByIdSalesOrder(
        int $idAgenticCommerceConsent,
        DateTimeImmutable $placedSince,
        array $canceledOmsStateNames
    ): array;

    public function findAgenticCommerceCartHandoffByTokenHash(string $tokenHash): ?AgenticCommerceCartHandoffTransfer;
}
