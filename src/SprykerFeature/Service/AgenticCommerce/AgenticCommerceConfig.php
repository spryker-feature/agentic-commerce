<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce;

use Spryker\Service\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig getSharedConfig()
 */
class AgenticCommerceConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Returns the issuer identifier of the delegated access authorization server, the Glue storefront base URL.
     * - Returns an empty string when delegated access is not configured.
     *
     * @api
     */
    public function getIssuer(): string
    {
        return $this->getSharedConfig()->getIssuer();
    }

    /**
     * Specification:
     * - Returns the canonical URI of the delegated access resource.
     *
     * @api
     */
    public function getResourceUri(): string
    {
        return $this->getSharedConfig()->getResourceUri();
    }

    /**
     * Specification:
     * - Returns the canonical URI of the commerce tools resource, or an empty string when it is not configured.
     *
     * @api
     */
    public function getMcpResourceUri(): string
    {
        return $this->getSharedConfig()->getMcpResourceUri();
    }

    /**
     * Specification:
     * - Returns the URL of the storefront page where a customer signs in and gives consent to a client.
     *
     * @api
     */
    public function getAuthorizationEndpoint(): string
    {
        return $this->getSharedConfig()->getAuthorizationEndpoint();
    }

    /**
     * Specification:
     * - Returns the URL where a client redeems authorization codes and refresh tokens.
     *
     * @api
     */
    public function getTokenEndpoint(): string
    {
        return $this->getSharedConfig()->getTokenEndpoint();
    }

    /**
     * Specification:
     * - Returns the URL where a client revokes its refresh token.
     *
     * @api
     */
    public function getRevocationEndpoint(): string
    {
        return $this->getSharedConfig()->getRevocationEndpoint();
    }

    /**
     * Specification:
     * - Returns the delegated scopes a client can be given.
     *
     * @api
     *
     * @return list<string>
     */
    public function getSupportedScopes(): array
    {
        return $this->getSharedConfig()->getSupportedScopes();
    }

    /**
     * Specification:
     * - Returns the URL where a client registers itself, or an empty string when it is not configured.
     *
     * @api
     */
    public function getRegistrationEndpoint(): string
    {
        return $this->getSharedConfig()->getRegistrationEndpoint();
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
}
