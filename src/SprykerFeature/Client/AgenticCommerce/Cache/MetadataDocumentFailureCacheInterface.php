<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Cache;

interface MetadataDocumentFailureCacheInterface
{
    /**
     * Specification:
     * - Returns true while a failed fetch of the Client ID Metadata Document (CIMD) of the client identifier is stored.
     * - Returns false on a miss and when Redis is not reachable (fail open), after logging an error.
     */
    public function has(string $clientIdentifier): bool;

    /**
     * Specification:
     * - Stores the failure under a key built from a hash of the client identifier, for the given seconds.
     * - Removes a stored failure of the client identifier for `0` seconds or less.
     * - Logs an error when Redis is not reachable.
     */
    public function save(string $clientIdentifier, int $ttlSeconds): void;
}
