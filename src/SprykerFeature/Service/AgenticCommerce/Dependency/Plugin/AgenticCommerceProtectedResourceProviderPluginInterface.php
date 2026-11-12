<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Dependency\Plugin;

/**
 * Use this plugin to declare the protected resources that the module shipping it serves on the Glue storefront.
 */
interface AgenticCommerceProtectedResourceProviderPluginInterface
{
    /**
     * Specification:
     * - Returns the protected resources the module that ships this plugin serves on the Glue storefront.
     * - `name` is a unique lower-case key; it never appears in a URL.
     * - `pathPrefix` starts with `/`; requests under it are handled by the delegated-access firewall.
     * - `resourceUri` is the absolute URI delegated tokens for this resource carry as their audience; its path equals `pathPrefix`; it comes from environment configuration.
     * - `scopes` lists the delegated-access scopes the resource checks; they are published as `scopes_supported` in the resource's RFC 9728 OAuth 2.0 Protected Resource Metadata.
     * - Every API Platform operation of the resource carries `extraProperties: {agenticCommerce: true}`.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResources(): array;
}
