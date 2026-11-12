<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;

interface ProtectedResourceReaderInterface
{
    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResources(): array;

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResourcesFailClosed(): array;

    public function findProtectedResourceByResourceUri(string $resourceUri): ?AgenticCommerceProtectedResourceTransfer;

    public function findProtectedResourceByPath(string $path): ?AgenticCommerceProtectedResourceTransfer;

    public function findProtectedResourceByMetadataPath(string $resourcePath): ?AgenticCommerceProtectedResourceTransfer;

    public function getProtectedResourceMetadataUrl(AgenticCommerceProtectedResourceTransfer $agenticCommerceProtectedResourceTransfer): string;

    public function getDelegatedAccessPathPattern(): string;
}
