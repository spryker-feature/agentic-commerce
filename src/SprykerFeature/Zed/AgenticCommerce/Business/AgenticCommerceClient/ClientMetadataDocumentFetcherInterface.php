<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataDocumentTransfer;

interface ClientMetadataDocumentFetcherInterface
{
    /**
     * Fetches a Client ID Metadata Document (CIMD) over HTTPS from the address the host resolved to once, without proxy or redirects, within the configured time and size limits.
     * Returns the decoded JSON object and its clamped cache lifetime, or the failure reason `address_refused` or `fetch_failed`.
     */
    public function fetch(string $documentUrl): AgenticCommerceClientMetadataDocumentTransfer;
}
