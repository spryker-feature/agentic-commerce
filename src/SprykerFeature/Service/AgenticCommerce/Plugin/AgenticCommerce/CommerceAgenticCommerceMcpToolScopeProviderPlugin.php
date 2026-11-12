<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Plugin\AgenticCommerce;

use Spryker\Service\Kernel\AbstractPlugin;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceMcpToolScopeProviderPluginInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class CommerceAgenticCommerceMcpToolScopeProviderPlugin extends AbstractPlugin implements AgenticCommerceMcpToolScopeProviderPluginInterface
{
    protected const string NO_SCOPE = '';

    /**
     * {@inheritDoc}
     * - Declares the scope of the commerce tools: catalog tools need `catalog:read`, the company tool `company:read`, read tools `cart:read`, cart and quote tools `cart:write`, checkout tools `order:place`.
     * - Declares `health-check`, which the MCP server ships itself, without a scope.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getToolScopes(): array
    {
        return [
            'get-company-context' => SharedAgenticCommerceConfig::SCOPE_COMPANY_READ,
            'search-products' => SharedAgenticCommerceConfig::SCOPE_CATALOG_READ,
            'get-product' => SharedAgenticCommerceConfig::SCOPE_CATALOG_READ,
            'list-orders' => SharedAgenticCommerceConfig::SCOPE_CART_READ,
            'get-order' => SharedAgenticCommerceConfig::SCOPE_CART_READ,
            'reorder' => SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
            'list-shopping-lists' => SharedAgenticCommerceConfig::SCOPE_CART_READ,
            'add-shopping-list-to-cart' => SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-cart' => SharedAgenticCommerceConfig::SCOPE_CART_READ,
            'update-cart' => SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-checkout-options' => SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
            'place-order' => SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
            'request-approval' => SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-approval-status' => SharedAgenticCommerceConfig::SCOPE_CART_READ,
            'create-quote-request' => SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
            'get-cart-handoff-link' => SharedAgenticCommerceConfig::SCOPE_CART_WRITE,
            'health-check' => static::NO_SCOPE,
        ];
    }
}
