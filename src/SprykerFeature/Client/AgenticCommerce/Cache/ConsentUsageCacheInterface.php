<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Cache;

use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;

interface ConsentUsageCacheInterface
{
    /**
     * Specification:
     * - Returns the cached valid consent check of the consent in the store.
     * - Returns null on a miss, on a revocation tombstone and when Redis is not reachable, so Zed decides.
     */
    public function find(string $storeName, string $consentUuid): ?AgenticCommerceConsentUsageResponseTransfer;

    /**
     * Specification:
     * - Caches a valid consent check only when the key is still absent, so it never replaces another entry or a tombstone.
     * - Adds the consent to the index of the customer's cached consents in the store.
     * - Does nothing for a refused check or while the cache is switched off.
     */
    public function save(
        string $storeName,
        string $consentUuid,
        string $customerReference,
        AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer
    ): void;

    /**
     * Specification:
     * - Replaces the cache entry of every consent with a tombstone that blocks every fill until it expires.
     * - Logs an error and returns when Redis is not reachable.
     *
     * @param list<string> $consentUuids
     */
    public function invalidate(string $storeName, array $consentUuids): void;

    /**
     * Specification:
     * - Tombstones every consent in the index of the customer's cached consents in the store.
     * - Logs an error and returns when Redis is not reachable.
     */
    public function invalidateByCustomerReference(string $storeName, string $customerReference): void;

    /**
     * Specification:
     * - Returns true once per `last_used_at` write interval and consent, so the caller asks Zed, which writes `last_used_at`.
     * - Returns true when Redis is not reachable.
     */
    public function shouldTouchLastUsedAt(string $storeName, string $consentUuid): bool;
}
