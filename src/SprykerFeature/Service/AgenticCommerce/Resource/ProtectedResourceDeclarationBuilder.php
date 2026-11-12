<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class ProtectedResourceDeclarationBuilder implements ProtectedResourceDeclarationBuilderInterface
{
    protected const string RESOURCE_NAME_PRINCIPAL = 'principal';

    protected const string SCOPE_PRINCIPAL_READ = 'agentic-commerce:principal-read';

    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    public function buildMcpProtectedResources(): array
    {
        return $this->buildProtectedResources(
            SharedAgenticCommerceConfig::RESOURCE_NAME_MCP,
            $this->agenticCommerceConfig->getMcpResourceUri(),
            [
                SharedAgenticCommerceConfig::SCOPE_CATALOG_READ,
                SharedAgenticCommerceConfig::SCOPE_COMPANY_READ,
                SharedAgenticCommerceConfig::SCOPE_CART_READ,
                SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
                SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
            ],
        );
    }

    public function buildPrincipalProtectedResources(): array
    {
        return $this->buildProtectedResources(
            static::RESOURCE_NAME_PRINCIPAL,
            $this->agenticCommerceConfig->getResourceUri(),
            [static::SCOPE_PRINCIPAL_READ],
        );
    }

    /**
     * @param list<string> $scopes
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    protected function buildProtectedResources(string $name, string $resourceUri, array $scopes): array
    {
        if ($resourceUri === '') {
            return [];
        }

        return [
            (new AgenticCommerceProtectedResourceTransfer())
                ->setName($name)
                ->setPathPrefix((string)parse_url($resourceUri, PHP_URL_PATH))
                ->setResourceUri($resourceUri)
                ->setScopes($scopes),
        ];
    }
}
