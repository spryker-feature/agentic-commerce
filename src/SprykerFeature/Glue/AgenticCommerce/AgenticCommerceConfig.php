<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce;

use Spryker\Glue\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig getSharedConfig()
 */
class AgenticCommerceConfig extends AbstractBundleConfig
{
    /**
     * @uses \Spryker\Glue\CustomersRestApi\Api\Storefront\EventSubscriber\CustomerIdentityRequestSubscriber::ATTRIBUTE_CUSTOMER_REFERENCE
     */
    public const string ATTRIBUTE_CUSTOMER_REFERENCE = 'customerReference';

    /**
     * @uses \Spryker\Glue\CustomersRestApi\Api\Storefront\EventSubscriber\CustomerIdentityRequestSubscriber::ATTRIBUTE_ID_CUSTOMER
     */
    public const string ATTRIBUTE_ID_CUSTOMER = 'idCustomer';

    public const string ATTRIBUTE_REFUSED_MCP_TOOL = '_agentic_commerce_refused_mcp_tool';

    public const string ATTRIBUTE_MISSING_SCOPE = '_agentic_commerce_missing_scope';

    protected const string FIREWALL_NAME = 'agentic_commerce';

    /**
     * Specification:
     * - Returns whether delegated access is switched on, as `\SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig::isConfigured()` decides.
     * - While it is off, the protocol endpoints answer `404`.
     *
     * @api
     */
    public function isConfigured(): bool
    {
        return $this->getSharedConfig()->isConfigured();
    }

    /**
     * Specification:
     * - Returns the OAuth issuer identifier of the delegated access authorization server; protected resource metadata names it in `authorization_servers` (RFC 9728 section 2).
     *
     * @api
     */
    public function getIssuer(): string
    {
        return $this->getSharedConfig()->getIssuer();
    }

    /**
     * Specification:
     * - Returns the canonical URI of the delegated access resource; its path names the protected resource metadata document (RFC 9728, OAuth 2.0 Protected Resource Metadata, section 3.1).
     *
     * @api
     */
    public function getResourceUri(): string
    {
        return $this->getSharedConfig()->getResourceUri();
    }

    /**
     * Specification:
     * - Returns the URL of the protected resource metadata document that `401` and `403` answers advertise in `WWW-Authenticate` (RFC 9728, section 5.1).
     *
     * @api
     */
    public function getProtectedResourceMetadataUrl(): string
    {
        return $this->getSharedConfig()->getProtectedResourceMetadataUrl();
    }

    /**
     * Specification:
     * - Returns the name of the Glue storefront firewall that authenticates delegated access tokens.
     * - The project security configuration must declare the firewall under this name.
     *
     * @api
     */
    public function getFirewallName(): string
    {
        return static::FIREWALL_NAME;
    }

    /**
     * Specification:
     * - Returns whether a Client ID Metadata Document (CIMD) URL is accepted as `client_id`.
     *
     * @api
     */
    public function isClientIdMetadataDocumentEnabled(): bool
    {
        return $this->getSharedConfig()->isClientIdMetadataDocumentEnabled();
    }

    /**
     * Specification:
     * - Returns whether the Dynamic Client Registration (DCR) endpoint accepts registrations.
     *
     * @api
     */
    public function isDynamicClientRegistrationEnabled(): bool
    {
        return $this->getSharedConfig()->isDynamicClientRegistrationEnabled();
    }

    /**
     * Specification:
     * - Returns the maximum size in bytes of a Dynamic Client Registration (DCR) request body; a larger body is refused before Zed is called.
     *
     * @api
     */
    public function getDynamicClientRegistrationRequestMaxBytes(): int
    {
        return $this->getSharedConfig()->getClientMetadataMaxBytes();
    }

    /**
     * Specification:
     * - Returns the seconds a valid consent check of Zed stays in the consent cache; `0` switches the cache off.
     *
     * @api
     */
    public function getConsentCacheTtlSeconds(): int
    {
        return $this->getSharedConfig()->getConsentCacheTtlSeconds();
    }
}
