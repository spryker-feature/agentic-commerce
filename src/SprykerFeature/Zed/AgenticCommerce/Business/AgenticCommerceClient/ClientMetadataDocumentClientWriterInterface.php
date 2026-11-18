<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;

interface ClientMetadataDocumentClientWriterInterface
{
    /**
     * Creates the public client of a validated Client ID Metadata Document (CIMD) as `domain_verified` and `active` with the configured scope ceiling,
     * or, for `$storedClientTransfer`, updates only its metadata and cache times, never its name, status, scope ceiling, registration type or trust level.
     * Returns `null` when another OAuth client already holds the identifier; a client created concurrently from the same document is returned instead.
     */
    public function writeClient(
        AgenticCommerceClientMetadataTransfer $clientMetadataTransfer,
        ?AgenticCommerceClientTransfer $storedClientTransfer,
        int $cacheLifetimeSeconds
    ): ?AgenticCommerceClientTransfer;
}
