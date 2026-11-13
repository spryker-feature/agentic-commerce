<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Redis;

interface AgenticCommerceRedisWrapperInterface
{
    /**
     * Specification:
     * - Returns the stored value, or null when the key does not exist.
     */
    public function get(string $key): ?string;

    /**
     * Specification:
     * - Stores the value and lets it expire after the given number of seconds.
     */
    public function setex(string $key, int $seconds, string $value): bool;

    /**
     * Specification:
     * - Stores the value without an expiry.
     */
    public function set(string $key, string $value): bool;

    /**
     * Specification:
     * - Stores the value with an expiry only when the key does not exist yet.
     * - Returns false when the key exists.
     */
    public function setIfAbsent(string $key, string $value, int $seconds): bool;

    /**
     * Specification:
     * - Deletes the keys and returns the number of deleted keys.
     *
     * @param list<string> $keys
     */
    public function del(array $keys): int;

    /**
     * Specification:
     * - Lets the next call reach Redis again after a failure latched every call of the process.
     */
    public function resetAvailability(): void;
}
