<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce;

use Spryker\Yves\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

/**
 * @method \SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig getSharedConfig()
 */
class AgenticCommerceConfig extends AbstractBundleConfig
{
    protected const int FRESH_AUTHENTICATION_WINDOW_IN_SECONDS = 600;

    protected const int AUTHORIZATION_REQUEST_LIFETIME_IN_SECONDS = 600;

    /**
     * @uses \SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin::ROUTE_NAME_AUTHORIZE
     * @uses \SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CONSENT
     * @uses \SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin::ROUTE_NAME_RELOGIN
     *
     * @var list<string>
     */
    protected const array FRAMING_PROTECTED_ROUTE_NAMES = [
        'agentic-commerce/authorize',
        'agentic-commerce/consent',
        'agentic-commerce/relogin',
    ];

    /**
     * Specification:
     * - Returns the OAuth issuer identifier of the region's delegated access authorization server (RFC 8414 `issuer`), the Glue storefront URL.
     * - The authorization response carries it as the RFC 9207 `iss` parameter.
     * - Returns an empty string when it is not configured.
     *
     * @api
     */
    public function getIssuer(): string
    {
        return $this->getSharedConfig()->getIssuer();
    }

    /**
     * Specification:
     * - Returns the storefront URL of the authorization endpoint.
     * - Only the host of this URL serves the authorization endpoint and the consent page.
     * - Returns an empty string when it is not configured.
     *
     * @api
     */
    public function getAuthorizationEndpoint(): string
    {
        return $this->getSharedConfig()->getAuthorizationEndpoint();
    }

    /**
     * Specification:
     * - Returns whether delegated access is switched on, as `\SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig::isConfigured()` decides, so the pages and the Glue protocol endpoints switch on together.
     * - The pages of the module answer `404` otherwise.
     *
     * @api
     */
    public function isConfigured(): bool
    {
        return $this->getSharedConfig()->isConfigured();
    }

    /**
     * Specification:
     * - Returns the names of the routes whose pages must never be framed: the authorize, consent and re-login pages.
     *
     * @api
     *
     * @return list<string>
     */
    public function getFramingProtectedRouteNames(): array
    {
        return static::FRAMING_PROTECTED_ROUTE_NAMES;
    }

    /**
     * Specification:
     * - Returns how many seconds ago the customer may have last signed in with their password, including multi-factor authentication where configured, to connect an app.
     *
     * @api
     */
    public function getFreshAuthenticationWindowInSeconds(): int
    {
        return static::FRESH_AUTHENTICATION_WINDOW_IN_SECONDS;
    }

    /**
     * Specification:
     * - Returns the consent constraint types, each with the scope it belongs to.
     * - The consent page hides the field of a constraint while its scope is unticked, and then neither requires nor submits its value.
     * - The field of a constraint type without an entry is offered whichever scopes are ticked.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getConsentConstraintScopes(): array
    {
        return [
            SharedAgenticCommerceConfig::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
            SharedAgenticCommerceConfig::CONSTRAINT_TYPE_ORDER_BUDGET => SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
            SharedAgenticCommerceConfig::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST => SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
            SharedAgenticCommerceConfig::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST => SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE,
        ];
    }

    /**
     * Specification:
     * - Returns how many seconds a validated authorization request waits in the session for the customer to sign in and decide.
     *
     * @api
     */
    public function getAuthorizationRequestLifetimeInSeconds(): int
    {
        return static::AUTHORIZATION_REQUEST_LIFETIME_IN_SECONDS;
    }
}
