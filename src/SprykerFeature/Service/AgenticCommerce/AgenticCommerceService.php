<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationServerMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Spryker\Service\Kernel\AbstractService;

/**
 * @method \SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceFactory getFactory()
 */
class AgenticCommerceService extends AbstractService implements AgenticCommerceServiceInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function buildProtectedResourceMetadata(): AgenticCommerceProtectedResourceMetadataTransfer
    {
        return $this->getFactory()->createOauthMetadataBuilder()->buildProtectedResourceMetadata();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function buildAuthorizationServerMetadata(): AgenticCommerceAuthorizationServerMetadataTransfer
    {
        return $this->getFactory()->createOauthMetadataBuilder()->buildAuthorizationServerMetadata();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function mapScopeToRole(string $scope): string
    {
        return $this->getFactory()->createScopeRoleMapper()->mapScopeToRole($scope);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isDelegatedAccessScope(string $scope): bool
    {
        return $this->getFactory()->createScopeRoleMapper()->isDelegatedAccessScope($scope);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProtectedResources(): array
    {
        return $this->getFactory()->getProtectedResourceReader()->getProtectedResources();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResourcesFailClosed(): array
    {
        return $this->getFactory()->getProtectedResourceReader()->getProtectedResourcesFailClosed();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findProtectedResourceByResourceUri(string $resourceUri): ?AgenticCommerceProtectedResourceTransfer
    {
        return $this->getFactory()->getProtectedResourceReader()->findProtectedResourceByResourceUri($resourceUri);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findProtectedResourceByPath(string $path): ?AgenticCommerceProtectedResourceTransfer
    {
        return $this->getFactory()->getProtectedResourceReader()->findProtectedResourceByPath($path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDelegatedAccessPathPattern(): string
    {
        return $this->getFactory()->getProtectedResourceReader()->getDelegatedAccessPathPattern();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findProtectedResourceByMetadataPath(string $resourcePath): ?AgenticCommerceProtectedResourceTransfer
    {
        return $this->getFactory()->getProtectedResourceReader()->findProtectedResourceByMetadataPath($resourcePath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProtectedResourceMetadataUrl(AgenticCommerceProtectedResourceTransfer $agenticCommerceProtectedResourceTransfer): string
    {
        return $this->getFactory()->getProtectedResourceReader()->getProtectedResourceMetadataUrl($agenticCommerceProtectedResourceTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findNormalizedHost(string $host): ?string
    {
        return $this->getFactory()->createHostNormalizer()->findNormalizedHost($host);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $hosts
     *
     * @return list<string>
     */
    public function normalizeHosts(array $hosts): array
    {
        return $this->getFactory()->createHostNormalizer()->normalizeHosts($hosts);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getMcpToolScopes(): array
    {
        return $this->getFactory()->createMcpToolScopeReader()->getMcpToolScopes();
    }
}
