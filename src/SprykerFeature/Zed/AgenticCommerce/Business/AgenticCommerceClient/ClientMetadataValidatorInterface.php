<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataValidationResponseTransfer;

interface ClientMetadataValidatorInterface
{
    /**
     * Validates the metadata of a self-registered client, from a Client ID Metadata Document (CIMD) or a Dynamic Client Registration (DCR).
     * `$allowedRedirectHosts === null` applies no host policy; loopback redirect URIs always pass the host policy.
     *
     * @param list<string>|null $allowedRedirectHosts
     */
    public function validate(
        AgenticCommerceClientMetadataTransfer $clientMetadataTransfer,
        ?array $allowedRedirectHosts,
        string $registrationType
    ): AgenticCommerceClientMetadataValidationResponseTransfer;
}
