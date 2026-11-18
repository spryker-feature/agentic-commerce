<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientTransfer;

interface SelfRegisteredClientWriterInterface
{
    /**
     * Creates a client that registered itself, through a Client ID Metadata Document (CIMD) or a Dynamic Client Registration (DCR),
     * in one transaction: the public OAuth client (identifier, name, first redirect URI) and the delegated-access client as given.
     */
    public function createClient(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): AgenticCommerceClientTransfer;

    /**
     * Writes a refreshed Client ID Metadata Document (CIMD) to a stored client in one transaction: the URIs and fetch timestamps
     * of the delegated-access client, and the name and first redirect URI of its public OAuth client.
     */
    public function updateClientMetadata(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): void;
}
