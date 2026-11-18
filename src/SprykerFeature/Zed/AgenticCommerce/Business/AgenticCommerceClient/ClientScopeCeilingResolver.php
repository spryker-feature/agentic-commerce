<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;

class ClientScopeCeilingResolver implements ClientScopeCeilingResolverInterface
{
    use LoggerTrait;

    public function __construct(
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected SupportedScopeReaderInterface $supportedScopeReader
    ) {
    }

    /**
     * @return list<string>
     */
    public function resolveClientIdMetadataDocumentScopeCeiling(string $clientIdentifier): array
    {
        return $this->filterSupportedScopes(
            $this->agenticCommerceConfig->getClientIdMetadataDocumentScopeCeilingMap()[$clientIdentifier]
                ?? $this->agenticCommerceConfig->getClientIdMetadataDocumentDefaultScopeCeiling(),
        );
    }

    /**
     * @return list<string>
     */
    public function resolveDynamicClientRegistrationScopeCeiling(?string $softwareId): array
    {
        $scopeCeilingMap = $this->agenticCommerceConfig->getDynamicClientRegistrationScopeCeilingMap();

        return $this->filterSupportedScopes(
            $softwareId !== null && isset($scopeCeilingMap[$softwareId])
                ? $scopeCeilingMap[$softwareId]
                : $this->agenticCommerceConfig->getDynamicClientRegistrationDefaultScopeCeiling(),
        );
    }

    /**
     * @param list<string> $scopeCeiling
     *
     * @return list<string>
     */
    protected function filterSupportedScopes(array $scopeCeiling): array
    {
        $supportedScopeCeiling = array_values(array_unique(array_intersect($scopeCeiling, $this->supportedScopeReader->getSupportedScopeNames())));
        $unsupportedScopes = array_values(array_diff($scopeCeiling, $supportedScopeCeiling));

        if ($unsupportedScopes !== []) {
            $this->getLogger()->warning('Configured scope ceiling names scopes that are not supported; they are left out.', ['scopes' => $unsupportedScopes]);
        }

        return $supportedScopeCeiling;
    }
}
