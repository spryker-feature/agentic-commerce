<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * The customer side of connecting an app and the company admin's view of the company's connected apps. No path uses the prefix that the firewall of `AgentPage` claims.
 */
class AgenticCommerceRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    public const string ROUTE_NAME_AUTHORIZE = 'agentic-commerce/authorize';

    public const string ROUTE_NAME_CONSENT = 'agentic-commerce/consent';

    public const string ROUTE_NAME_RELOGIN = 'agentic-commerce/relogin';

    public const string ROUTE_NAME_CONNECTED_APPS = 'customer/connected-apps';

    public const string ROUTE_NAME_CONNECTED_APPS_DISCONNECT = 'customer/connected-apps/disconnect';

    public const string ROUTE_NAME_COMPANY_CONNECTED_APPS = 'company/connected-apps';

    public const string ROUTE_NAME_COMPANY_CONNECTED_APPS_REVOKE = 'company/connected-apps/revoke';

    public const string ROUTE_NAME_CART_HANDOFF = 'agentic-commerce/cart-handoff';

    public const string PARAMETER_TOKEN = 'token';

    protected const string CART_HANDOFF_TOKEN_PATTERN = '[A-Za-z0-9_-]{43}';

    protected const string MODULE_NAME = 'AgenticCommerce';

    /**
     * {@inheritDoc}
     * - Adds `GET /connect/authorize`, `GET|POST /connect/consent`, `GET /connect/relogin`, `GET /customer/connected-apps`, `POST /customer/connected-apps/disconnect`, `GET /company/connected-apps`, `POST /company/connected-apps/revoke` and `GET /agentic-commerce/cart-handoff/{token}`.
     *
     * @api
     */
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addAuthorizeRoute($routeCollection);
        $routeCollection = $this->addConsentRoute($routeCollection);
        $routeCollection = $this->addReloginRoute($routeCollection);
        $routeCollection = $this->addConnectedAppsRoute($routeCollection);
        $routeCollection = $this->addConnectedAppsDisconnectRoute($routeCollection);
        $routeCollection = $this->addCompanyConnectedAppsRoute($routeCollection);
        $routeCollection = $this->addCompanyConnectedAppsRevokeRoute($routeCollection);
        $routeCollection = $this->addCartHandoffRoute($routeCollection);

        return $routeCollection;
    }

    protected function addAuthorizeRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute('/connect/authorize', static::MODULE_NAME, 'Authorize');
        $routeCollection->add(static::ROUTE_NAME_AUTHORIZE, $route);

        return $routeCollection;
    }

    protected function addConsentRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute('/connect/consent', static::MODULE_NAME, 'Consent');
        $route = $route->setMethods([Request::METHOD_GET, Request::METHOD_POST]);
        $routeCollection->add(static::ROUTE_NAME_CONSENT, $route);

        return $routeCollection;
    }

    protected function addReloginRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute('/connect/relogin', static::MODULE_NAME, 'Consent', 'reloginAction');
        $routeCollection->add(static::ROUTE_NAME_RELOGIN, $route);

        return $routeCollection;
    }

    protected function addConnectedAppsRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute('/customer/connected-apps', static::MODULE_NAME, 'ConnectedApps');
        $routeCollection->add(static::ROUTE_NAME_CONNECTED_APPS, $route);

        return $routeCollection;
    }

    protected function addConnectedAppsDisconnectRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute('/customer/connected-apps/disconnect', static::MODULE_NAME, 'ConnectedApps', 'disconnectAction');
        $routeCollection->add(static::ROUTE_NAME_CONNECTED_APPS_DISCONNECT, $route);

        return $routeCollection;
    }

    protected function addCompanyConnectedAppsRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute('/company/connected-apps', static::MODULE_NAME, 'CompanyConnectedApps');
        $routeCollection->add(static::ROUTE_NAME_COMPANY_CONNECTED_APPS, $route);

        return $routeCollection;
    }

    protected function addCompanyConnectedAppsRevokeRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute('/company/connected-apps/revoke', static::MODULE_NAME, 'CompanyConnectedApps', 'revokeAction');
        $routeCollection->add(static::ROUTE_NAME_COMPANY_CONNECTED_APPS_REVOKE, $route);

        return $routeCollection;
    }

    protected function addCartHandoffRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildGetRoute('/agentic-commerce/cart-handoff/{' . static::PARAMETER_TOKEN . '}', static::MODULE_NAME, 'CartHandoff');
        $route = $route->setRequirement(static::PARAMETER_TOKEN, static::CART_HANDOFF_TOKEN_PATTERN);
        $routeCollection->add(static::ROUTE_NAME_CART_HANDOFF, $route);

        return $routeCollection;
    }
}
