<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientResolveResponseTransfer;

interface ClientResolverInterface
{
    /**
     * Returns the stored client of the identifier. An HTTPS URL identifier without a stored client, or whose Client ID Metadata Document (CIMD) cache expired,
     * is resolved by fetching and validating its document and creating or updating the client.
     * A response without client and without `failureReason` means the client is unknown; a `failureReason` names why the document was refused.
     */
    public function resolveClient(string $clientIdentifier): AgenticCommerceClientResolveResponseTransfer;
}
