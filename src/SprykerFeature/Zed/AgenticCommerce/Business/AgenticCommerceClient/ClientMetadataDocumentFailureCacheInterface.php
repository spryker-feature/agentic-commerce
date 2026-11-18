<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

interface ClientMetadataDocumentFailureCacheInterface
{
    /**
     * Returns true while a failed fetch of the client identifier's document is cached; false on a miss, when the cache is off and when the storage is not reachable.
     */
    public function isFailureCached(string $clientIdentifier): bool;

    /**
     * Caches the failed fetch for `AgenticCommerceConfig::getClientIdMetadataDocumentFailureCacheTtlSeconds()` seconds; does nothing while that is `0`.
     */
    public function cacheFailure(string $clientIdentifier): void;
}
