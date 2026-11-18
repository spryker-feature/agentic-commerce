<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use DateTimeInterface;

interface AgenticCommerceRetentionReaderInterface
{
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
     * Returns those of the events an order attribution references.
     * For a referenced event without a client name, such as `scopes_narrowed`, the latest `granted` or `re_consented` event of its consent counts as
     * referenced too: the Back Office block of the order falls back to it (`findLatestAgenticCommerceConsentEvent()`, same order).
     *
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function getReferencedConsentEventIds(array $consentEventIds): array;
}
