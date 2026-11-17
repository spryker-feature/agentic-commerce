<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string FACADE_OAUTH = 'FACADE_OAUTH';

    public const string FACADE_OAUTH_CODE_FLOW = 'FACADE_OAUTH_CODE_FLOW';

    public const string FACADE_CUSTOMER = 'FACADE_CUSTOMER';

    public const string FACADE_STORE = 'FACADE_STORE';

    public const string FACADE_MERCHANT = 'FACADE_MERCHANT';

    public const string FACADE_CATEGORY = 'FACADE_CATEGORY';

    public const string FACADE_PRODUCT_CATEGORY = 'FACADE_PRODUCT_CATEGORY';

    public const string FACADE_COMPANY_USER = 'FACADE_COMPANY_USER';

    public const string FACADE_COMPANY_ROLE = 'FACADE_COMPANY_ROLE';

    public const string FACADE_COMPANY_UNIT_ADDRESS = 'FACADE_COMPANY_UNIT_ADDRESS';

    public const string FACADE_OAUTH_COMPANY_USER = 'FACADE_OAUTH_COMPANY_USER';

    public const string FACADE_GLOSSARY = 'FACADE_GLOSSARY';

    public const string FACADE_USER = 'FACADE_USER';

    public const string FACADE_QUOTE = 'FACADE_QUOTE';

    public const string FACADE_QUOTE_APPROVAL = 'FACADE_QUOTE_APPROVAL';

    public const string FACADE_PERMISSION = 'FACADE_PERMISSION';

    public const string SERVICE_UTIL_ENCODING = 'SERVICE_UTIL_ENCODING';

    public const string SERVICE_AGENTIC_COMMERCE = 'SERVICE_AGENTIC_COMMERCE';

    public const string SERVICE_REQUEST_CONSENT_UUID = 'SERVICE_REQUEST_CONSENT_UUID';

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Plugin\Application\AgenticCommerceBackendGatewayApplicationPlugin::SERVICE_REQUEST_CONSENT_UUID
     */
    protected const string APPLICATION_SERVICE_REQUEST_CONSENT_UUID = 'agentic_commerce_request_consent_uuid';

    public const string SERVICE_REQUEST_ACTING_COMPANY_USER_UUID = 'SERVICE_REQUEST_ACTING_COMPANY_USER_UUID';

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Plugin\Application\AgenticCommerceBackendGatewayApplicationPlugin::SERVICE_REQUEST_ACTING_COMPANY_USER_UUID
     */
    protected const string APPLICATION_SERVICE_REQUEST_ACTING_COMPANY_USER_UUID = 'agentic_commerce_request_acting_company_user_uuid';

    public const string CLIENT_GUZZLE = 'CLIENT_GUZZLE';

    public const string CLIENT_AGENTIC_COMMERCE = 'CLIENT_AGENTIC_COMMERCE';

    public const string PLUGINS_AGENTIC_COMMERCE_SCOPE_PROVIDER = 'PLUGINS_AGENTIC_COMMERCE_SCOPE_PROVIDER';

    public const string PLUGINS_AGENTIC_COMMERCE_PRINCIPAL_RESOLVER = 'PLUGINS_AGENTIC_COMMERCE_PRINCIPAL_RESOLVER';

    public const string PLUGINS_AGENTIC_COMMERCE_SCOPE_FILTER = 'PLUGINS_AGENTIC_COMMERCE_SCOPE_FILTER';

    public const string PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE = 'PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE';

    public const string PLUGINS_AGENTIC_COMMERCE_CONSENT_EVENT_RETENTION = 'PLUGINS_AGENTIC_COMMERCE_CONSENT_EVENT_RETENTION';

    public const string PROPEL_QUERY_OAUTH_CLIENT = 'PROPEL_QUERY_OAUTH_CLIENT';

    public const string PLUGINS_AGENTIC_COMMERCE_CONSENT_CONSTRAINT = 'PLUGINS_AGENTIC_COMMERCE_CONSENT_CONSTRAINT';

    public const string PLUGINS_AGENTIC_COMMERCE_ORDER_PLACEMENT_CHECKER = 'PLUGINS_AGENTIC_COMMERCE_ORDER_PLACEMENT_CHECKER';

    public const string PLUGINS_AGENTIC_COMMERCE_ORDER_CONSTRAINT_CHECKER = 'PLUGINS_AGENTIC_COMMERCE_ORDER_CONSTRAINT_CHECKER';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addOauthFacade($container);
        $container = $this->addOauthCodeFlowFacade($container);
        $container = $this->addCustomerFacade($container);
        $container = $this->addStoreFacade($container);
        $container = $this->addMerchantFacade($container);
        $container = $this->addCategoryFacade($container);
        $container = $this->addProductCategoryFacade($container);
        $container = $this->addCompanyUserFacade($container);
        $container = $this->addCompanyRoleFacade($container);
        $container = $this->addCompanyUnitAddressFacade($container);
        $container = $this->addOauthCompanyUserFacade($container);
        $container = $this->addQuoteFacade($container);
        $container = $this->addQuoteApprovalFacade($container);
        $container = $this->addPermissionFacade($container);
        $container = $this->addUtilEncodingService($container);
        $container = $this->addAgenticCommerceService($container);
        $container = $this->addAgenticCommerceScopeProviderPlugins($container);
        $container = $this->addAgenticCommercePrincipalResolverPlugins($container);
        $container = $this->addAgenticCommerceScopeFilterPlugins($container);
        $container = $this->addAgenticCommerceConsentPostRevokePlugins($container);
        $container = $this->addAgenticCommerceConsentEventRetentionPlugins($container);
        $container = $this->addAgenticCommerceConsentConstraintPlugins($container);
        $container = $this->addAgenticCommerceOrderConstraintCheckerPlugins($container);
        $container = $this->addAgenticCommerceOrderPlacementCheckerPlugins($container);
        $container = $this->addGuzzleClient($container);
        $container = $this->addRequestConsentUuid($container);
        $container = $this->addRequestActingCompanyUserUuid($container);
        $container = $this->addUserFacade($container);
        $container = $this->addAgenticCommerceClient($container);

        return $container;
    }

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addGlossaryFacade($container);
        $container = $this->addUserFacade($container);
        $container = $this->addAgenticCommerceClient($container);

        return $container;
    }

    public function providePersistenceLayerDependencies(Container $container): Container
    {
        $container = parent::providePersistenceLayerDependencies($container);
        $container = $this->addOauthClientPropelQuery($container);
        $container = $this->addAgenticCommerceService($container);

        return $container;
    }

    protected function addAgenticCommerceClient(Container $container): Container
    {
        $container->set(static::CLIENT_AGENTIC_COMMERCE, function (Container $container) {
            return $container->getLocator()->agenticCommerce()->client();
        });

        return $container;
    }

    protected function addOauthClientPropelQuery(Container $container): Container
    {
        $container->set(static::PROPEL_QUERY_OAUTH_CLIENT, $container->factory(function () {
            return SpyOauthClientQuery::create();
        }));

        return $container;
    }

    protected function addOauthFacade(Container $container): Container
    {
        $container->set(static::FACADE_OAUTH, function (Container $container) {
            return $container->getLocator()->oauth()->facade();
        });

        return $container;
    }

    protected function addOauthCodeFlowFacade(Container $container): Container
    {
        $container->set(static::FACADE_OAUTH_CODE_FLOW, function (Container $container) {
            return $container->getLocator()->oauthCodeFlow()->facade();
        });

        return $container;
    }

    protected function addCustomerFacade(Container $container): Container
    {
        $container->set(static::FACADE_CUSTOMER, function (Container $container) {
            return $container->getLocator()->customer()->facade();
        });

        return $container;
    }

    protected function addStoreFacade(Container $container): Container
    {
        $container->set(static::FACADE_STORE, function (Container $container) {
            return $container->getLocator()->store()->facade();
        });

        return $container;
    }

    protected function addMerchantFacade(Container $container): Container
    {
        $container->set(static::FACADE_MERCHANT, function (Container $container) {
            return $container->getLocator()->merchant()->facade();
        });

        return $container;
    }

    protected function addCategoryFacade(Container $container): Container
    {
        $container->set(static::FACADE_CATEGORY, function (Container $container) {
            return $container->getLocator()->category()->facade();
        });

        return $container;
    }

    protected function addProductCategoryFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT_CATEGORY, function (Container $container) {
            return $container->getLocator()->productCategory()->facade();
        });

        return $container;
    }

    protected function addCompanyUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_USER, function (Container $container) {
            return $container->getLocator()->companyUser()->facade();
        });

        return $container;
    }

    protected function addCompanyUnitAddressFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_UNIT_ADDRESS, function (Container $container) {
            return $container->getLocator()->companyUnitAddress()->facade();
        });

        return $container;
    }

    protected function addCompanyRoleFacade(Container $container): Container
    {
        $container->set(static::FACADE_COMPANY_ROLE, function (Container $container) {
            return $container->getLocator()->companyRole()->facade();
        });

        return $container;
    }

    protected function addOauthCompanyUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_OAUTH_COMPANY_USER, function (Container $container) {
            return $container->getLocator()->oauthCompanyUser()->facade();
        });

        return $container;
    }

    protected function addQuoteFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE, function (Container $container) {
            return $container->getLocator()->quote()->facade();
        });

        return $container;
    }

    protected function addQuoteApprovalFacade(Container $container): Container
    {
        $container->set(static::FACADE_QUOTE_APPROVAL, function (Container $container) {
            return $container->getLocator()->quoteApproval()->facade();
        });

        return $container;
    }

    protected function addPermissionFacade(Container $container): Container
    {
        $container->set(static::FACADE_PERMISSION, function (Container $container) {
            return $container->getLocator()->permission()->facade();
        });

        return $container;
    }

    protected function addGlossaryFacade(Container $container): Container
    {
        $container->set(static::FACADE_GLOSSARY, function (Container $container) {
            return $container->getLocator()->glossary()->facade();
        });

        return $container;
    }

    protected function addUserFacade(Container $container): Container
    {
        $container->set(static::FACADE_USER, function (Container $container) {
            return $container->getLocator()->user()->facade();
        });

        return $container;
    }

    protected function addUtilEncodingService(Container $container): Container
    {
        $container->set(static::SERVICE_UTIL_ENCODING, function (Container $container) {
            return $container->getLocator()->utilEncoding()->service();
        });

        return $container;
    }

    protected function addAgenticCommerceService(Container $container): Container
    {
        $container->set(static::SERVICE_AGENTIC_COMMERCE, function (Container $container) {
            return $container->getLocator()->agenticCommerce()->service();
        });

        return $container;
    }

    /**
     * Read on every use: the application service exists only while Zed handles a backend gateway request.
     */
    protected function addRequestConsentUuid(Container $container): Container
    {
        $container->set(static::SERVICE_REQUEST_CONSENT_UUID, $container->factory(function (Container $container): ?string {
            if (!$container->hasApplicationService(static::APPLICATION_SERVICE_REQUEST_CONSENT_UUID)) {
                return null;
            }

            return $container->getApplicationService(static::APPLICATION_SERVICE_REQUEST_CONSENT_UUID);
        }));

        return $container;
    }

    /**
     * Read on every use: the application service exists only while Zed handles a backend gateway request.
     */
    protected function addRequestActingCompanyUserUuid(Container $container): Container
    {
        $container->set(static::SERVICE_REQUEST_ACTING_COMPANY_USER_UUID, $container->factory(function (Container $container): ?string {
            if (!$container->hasApplicationService(static::APPLICATION_SERVICE_REQUEST_ACTING_COMPANY_USER_UUID)) {
                return null;
            }

            return $container->getApplicationService(static::APPLICATION_SERVICE_REQUEST_ACTING_COMPANY_USER_UUID);
        }));

        return $container;
    }

    /**
     * The explicit `CurlHandler` keeps every request on curl, which the Client ID Metadata Document (CIMD) fetch needs for its address pin and size limit.
     */
    protected function addGuzzleClient(Container $container): Container
    {
        $container->set(static::CLIENT_GUZZLE, function () {
            return new Client(['handler' => HandlerStack::create(new CurlHandler())]);
        });

        return $container;
    }

    protected function addAgenticCommerceScopeProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_SCOPE_PROVIDER, function () {
            return $this->getAgenticCommerceScopeProviderPlugins();
        });

        return $container;
    }

    protected function addAgenticCommercePrincipalResolverPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_PRINCIPAL_RESOLVER, function () {
            return $this->getAgenticCommercePrincipalResolverPlugins();
        });

        return $container;
    }

    protected function addAgenticCommerceScopeFilterPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_SCOPE_FILTER, function () {
            return $this->getAgenticCommerceScopeFilterPlugins();
        });

        return $container;
    }

    protected function addAgenticCommerceConsentPostRevokePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, function () {
            return $this->getAgenticCommerceConsentPostRevokePlugins();
        });

        return $container;
    }

    protected function addAgenticCommerceConsentEventRetentionPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_CONSENT_EVENT_RETENTION, function () {
            return $this->getAgenticCommerceConsentEventRetentionPlugins();
        });

        return $container;
    }

    protected function addAgenticCommerceConsentConstraintPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_CONSENT_CONSTRAINT, function () {
            return $this->getAgenticCommerceConsentConstraintPlugins();
        });

        return $container;
    }

    protected function addAgenticCommerceOrderConstraintCheckerPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_ORDER_CONSTRAINT_CHECKER, function () {
            return $this->getAgenticCommerceOrderConstraintCheckerPlugins();
        });

        return $container;
    }

    protected function addAgenticCommerceOrderPlacementCheckerPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_ORDER_PLACEMENT_CHECKER, function () {
            return $this->getAgenticCommerceOrderPlacementCheckerPlugins();
        });

        return $container;
    }

    /**
     * Specification:
     * - Each plugin declares the scopes of the module that ships it; the scopes of all plugins are merged in stack order, the first declaration of a name wins.
     *
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeProviderPluginInterface>
     */
    protected function getAgenticCommerceScopeProviderPlugins(): array
    {
        return [];
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface>
     */
    protected function getAgenticCommerceConsentPostRevokePlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Housekeeping keeps every consent event a plugin retains, its consent and that consent's client.
     * - Without a plugin housekeeping prunes no consent event and no inactive consent at all, and logs a warning on every run.
     *
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentEventRetentionPluginInterface>
     */
    protected function getAgenticCommerceConsentEventRetentionPlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - The stack is first-applicable: the first plugin in stack order that returns a principal candidate supplies all candidates.
     *
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommercePrincipalResolverPluginInterface>
     */
    protected function getAgenticCommercePrincipalResolverPlugins(): array
    {
        return [];
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeFilterPluginInterface>
     */
    protected function getAgenticCommerceScopeFilterPlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Each plugin declares one per-consent constraint; the consent page offers the applicable ones in stack order.
     * - A stored constraint whose type no plugin declares is ignored when read and stays in the database.
     *
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface>
     */
    protected function getAgenticCommerceConsentConstraintPlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Each plugin enforces one consent constraint type when an order is placed; they run in stack order before the order placement checker plugins, and the first refusal wins.
     * - A stored constraint whose type no plugin checks is skipped with a logged warning, so unwiring a plugin removes its enforcement.
     *
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceOrderConstraintCheckerPluginInterface>
     */
    protected function getAgenticCommerceOrderConstraintCheckerPlugins(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Each plugin checks the order placement of one kind of principal. Every plugin applicable for the principal is asked in stack order, and the first result that is not `allowed` is returned.
     * - The consent page hint of the order limit uses the first applicable plugin only.
     * - Without an applicable plugin, nothing is checked beyond the generic consent rules.
     *
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceOrderPlacementCheckerPluginInterface>
     */
    protected function getAgenticCommerceOrderPlacementCheckerPlugins(): array
    {
        return [];
    }
}
