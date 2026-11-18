<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

interface ClientScopeCeilingResolverInterface
{
    /**
     * Returns the scope ceiling a new Client ID Metadata Document (CIMD) client gets: its entry in the configured ceiling map, else the configured default, reduced to the supported scopes.
     *
     * @return list<string>
     */
    public function resolveClientIdMetadataDocumentScopeCeiling(string $clientIdentifier): array;

    /**
     * Returns the scope ceiling a new Dynamic Client Registration (DCR) client gets: the entry of its `software_id` in the configured ceiling map, else the configured default, reduced to the supported scopes.
     *
     * @return list<string>
     */
    public function resolveDynamicClientRegistrationScopeCeiling(?string $softwareId): array;
}
