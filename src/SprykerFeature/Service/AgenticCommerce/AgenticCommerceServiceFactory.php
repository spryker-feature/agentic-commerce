<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce;

use Spryker\Service\Kernel\AbstractServiceFactory;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Service\AgenticCommerce\Host\HostNormalizer;
use SprykerFeature\Service\AgenticCommerce\Host\HostNormalizerInterface;
use SprykerFeature\Service\AgenticCommerce\Metadata\OauthMetadataBuilder;
use SprykerFeature\Service\AgenticCommerce\Metadata\OauthMetadataBuilderInterface;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceDeclarationBuilder;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceDeclarationBuilderInterface;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceReader;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceReaderInterface;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceValidator;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceValidatorInterface;
use SprykerFeature\Service\AgenticCommerce\Scope\McpToolScopeReader;
use SprykerFeature\Service\AgenticCommerce\Scope\McpToolScopeReaderInterface;
use SprykerFeature\Service\AgenticCommerce\Scope\ScopeRoleMapper;
use SprykerFeature\Service\AgenticCommerce\Scope\ScopeRoleMapperInterface;

/**
 * @method \SprykerFeature\Service\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceServiceFactory extends AbstractServiceFactory
{
    use LoggerTrait;

    protected ?ProtectedResourceReaderInterface $protectedResourceReader = null;

    public function createOauthMetadataBuilder(): OauthMetadataBuilderInterface
    {
        return new OauthMetadataBuilder($this->getConfig(), $this->getProtectedResourceReader());
    }

    public function getProtectedResourceReader(): ProtectedResourceReaderInterface
    {
        if ($this->protectedResourceReader === null) {
            $this->protectedResourceReader = $this->createProtectedResourceReader();
        }

        return $this->protectedResourceReader;
    }

    public function createProtectedResourceReader(): ProtectedResourceReaderInterface
    {
        return new ProtectedResourceReader(
            $this->getProtectedResourceProviderPlugins(),
            $this->createProtectedResourceValidator(),
            $this->getLogger(),
        );
    }

    public function createProtectedResourceValidator(): ProtectedResourceValidatorInterface
    {
        return new ProtectedResourceValidator($this->createScopeRoleMapper(), $this->createHostNormalizer());
    }

    public function createProtectedResourceDeclarationBuilder(): ProtectedResourceDeclarationBuilderInterface
    {
        return new ProtectedResourceDeclarationBuilder($this->getConfig());
    }

    public function createScopeRoleMapper(): ScopeRoleMapperInterface
    {
        return new ScopeRoleMapper();
    }

    public function createMcpToolScopeReader(): McpToolScopeReaderInterface
    {
        return new McpToolScopeReader($this->getMcpToolScopeProviderPlugins());
    }

    public function createHostNormalizer(): HostNormalizerInterface
    {
        return new HostNormalizer();
    }

    /**
     * @return list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface>
     */
    public function getProtectedResourceProviderPlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_PROTECTED_RESOURCE_PROVIDER);
    }

    /**
     * @return list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceMcpToolScopeProviderPluginInterface>
     */
    public function getMcpToolScopeProviderPlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_MCP_TOOL_SCOPE_PROVIDER);
    }
}
