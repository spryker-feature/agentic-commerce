<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Scope;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class SupportedScopeReader implements SupportedScopeReaderInterface
{
    protected const string GLOSSARY_KEY_PREFIX = 'agentic_commerce.scope.';

    protected const string SCOPE_PART_SEPARATOR = ':';

    protected const string GLOSSARY_KEY_SEPARATOR = '.';

    protected const string SOURCE_CONFIG = 'AgenticCommerceConstants::SUPPORTED_SCOPES';

    /**
     * @var list<string>
     */
    protected const array RESERVED_ROLES = [
        'ROLE_USER',
        SharedAgenticCommerceConfig::ROLE_CUSTOMER,
        SharedAgenticCommerceConfig::ROLE_COMPANY_USER,
        SharedAgenticCommerceConfig::ROLE_AGENTIC_COMMERCE,
    ];

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeProviderPluginInterface> $scopeProviderPlugins
     */
    public function __construct(
        protected array $scopeProviderPlugins,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AgenticCommerceConfig $agenticCommerceConfig,
    ) {
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getSupportedScopes(): array
    {
        $scopeTransfers = [];
        $scopeNamesByRole = [];

        foreach ($this->scopeProviderPlugins as $scopeProviderPlugin) {
            foreach ($scopeProviderPlugin->getScopes() as $scopeTransfer) {
                $this->addScope($scopeTransfers, $scopeNamesByRole, $scopeTransfer, $scopeProviderPlugin::class);
            }
        }

        foreach ($this->agenticCommerceConfig->getConfiguredSupportedScopes() as $scopeName) {
            if (!is_string($scopeName)) {
                throw new AgenticCommerceConfigurationException(sprintf('%s declares a scope of type %s, which is not a string.', static::SOURCE_CONFIG, get_debug_type($scopeName)));
            }

            $this->addScope($scopeTransfers, $scopeNamesByRole, $this->createConfigScopeTransfer($scopeName), static::SOURCE_CONFIG);
        }

        return array_values($scopeTransfers);
    }

    /**
     * @param list<string> $scopeNames
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getScopesByNames(array $scopeNames): array
    {
        $supportedScopeTransfers = [];

        foreach ($this->getSupportedScopes() as $supportedScopeTransfer) {
            $supportedScopeTransfers[$supportedScopeTransfer->getNameOrFail()] = $supportedScopeTransfer;
        }

        return array_map(
            fn (string $scopeName): AgenticCommerceScopeTransfer => $supportedScopeTransfers[$scopeName] ?? $this->createConfigScopeTransfer($scopeName),
            $scopeNames,
        );
    }

    public function getScopeCollection(AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer): AgenticCommerceScopeCollectionTransfer
    {
        $scopeNames = array_values($agenticCommerceScopeCriteriaTransfer->getAgenticCommerceScopeConditionsOrFail()->getNames());

        return (new AgenticCommerceScopeCollectionTransfer())->setScopes(new ArrayObject($this->getScopesByNames($scopeNames)));
    }

    /**
     * @return list<string>
     */
    public function getSupportedScopeNames(): array
    {
        return array_map(
            static fn (AgenticCommerceScopeTransfer $scopeTransfer): string => $scopeTransfer->getNameOrFail(),
            $this->getSupportedScopes(),
        );
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\AgenticCommerceScopeTransfer> $scopeTransfers
     * @param array<string, string> $scopeNamesByRole
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function addScope(
        array &$scopeTransfers,
        array &$scopeNamesByRole,
        AgenticCommerceScopeTransfer $scopeTransfer,
        string $source
    ): void {
        $scopeName = $scopeTransfer->getNameOrFail();

        if (isset($scopeTransfers[$scopeName])) {
            return;
        }

        if (!$this->agenticCommerceService->isDelegatedAccessScope($scopeName)) {
            throw new AgenticCommerceConfigurationException(sprintf('%s declares the scope "%s", which is not of the form <resource>:<action>.', $source, $scopeName));
        }

        $role = $this->agenticCommerceService->mapScopeToRole($scopeName);

        if (in_array($role, static::RESERVED_ROLES, true) || isset($scopeNamesByRole[$role])) {
            throw new AgenticCommerceConfigurationException(sprintf('%s declares the scope "%s", whose role %s is reserved or already used.', $source, $scopeName, $role));
        }

        $scopeNamesByRole[$role] = $scopeName;
        $scopeTransfers[$scopeName] = $scopeTransfer;
    }

    protected function createConfigScopeTransfer(string $scopeName): AgenticCommerceScopeTransfer
    {
        return (new AgenticCommerceScopeTransfer())
            ->setName($scopeName)
            ->setGlossaryKey(static::GLOSSARY_KEY_PREFIX . str_replace(static::SCOPE_PART_SEPARATOR, static::GLOSSARY_KEY_SEPARATOR, $scopeName));
    }
}
