<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce;

use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\MultiCart\MultiCartClientInterface;
use Spryker\Client\Permission\PermissionClientInterface;
use Spryker\Client\Session\SessionClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapper;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapperInterface;
use SprykerFeature\Yves\AgenticCommerce\Authentication\FreshAuthenticationChecker;
use SprykerFeature\Yves\AgenticCommerce\Authentication\FreshAuthenticationCheckerInterface;
use SprykerFeature\Yves\AgenticCommerce\Authorization\AuthorizationRequestParker;
use SprykerFeature\Yves\AgenticCommerce\Authorization\AuthorizationRequestParkerInterface;
use SprykerFeature\Yves\AgenticCommerce\Authorization\AuthorizationResponseUrlBuilder;
use SprykerFeature\Yves\AgenticCommerce\Authorization\AuthorizationResponseUrlBuilderInterface;
use SprykerFeature\Yves\AgenticCommerce\CartHandoff\CartHandoffTokenParker;
use SprykerFeature\Yves\AgenticCommerce\CartHandoff\CartHandoffTokenParkerInterface;
use SprykerFeature\Yves\AgenticCommerce\Consent\ConsentCollectionRequestBuilder;
use SprykerFeature\Yves\AgenticCommerce\Consent\ConsentCollectionRequestBuilderInterface;
use SprykerFeature\Yves\AgenticCommerce\Consent\ConsentViewDataBuilder;
use SprykerFeature\Yves\AgenticCommerce\Consent\ConsentViewDataBuilderInterface;
use SprykerFeature\Yves\AgenticCommerce\Form\ConsentForm;
use SprykerFeature\Yves\AgenticCommerce\Form\DataProvider\ConsentFormDataProvider;
use SprykerFeature\Yves\AgenticCommerce\Form\DataProvider\ConsentFormDataProviderInterface;
use SprykerFeature\Yves\AgenticCommerce\Form\DisconnectForm;
use SprykerFeature\Yves\AgenticCommerce\KillSwitch\KillSwitchRouteGuard;
use SprykerFeature\Yves\AgenticCommerce\KillSwitch\KillSwitchRouteGuardInterface;
use SprykerFeature\Yves\AgenticCommerce\Principal\PrincipalLabelResolver;
use SprykerFeature\Yves\AgenticCommerce\Principal\PrincipalLabelResolverInterface;
use SprykerFeature\Yves\AgenticCommerce\RateLimit\AuthorizeRateLimiter;
use SprykerFeature\Yves\AgenticCommerce\RateLimit\AuthorizeRateLimiterInterface;
use SprykerFeature\Yves\AgenticCommerce\Scope\ScopeGlossaryKeyReader;
use SprykerFeature\Yves\AgenticCommerce\Scope\ScopeGlossaryKeyReaderInterface;
use SprykerFeature\Yves\AgenticCommerce\SecurityHeader\FramingProtectionHeaderExpander;
use SprykerFeature\Yves\AgenticCommerce\SecurityHeader\FramingProtectionHeaderExpanderInterface;
use SprykerShop\Yves\CustomerPage\Form\LoginForm;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceFactory extends AbstractFactory
{
    /**
     * @uses \Spryker\Yves\Form\Plugin\Application\FormApplicationPlugin::SERVICE_FORM_FACTORY_ALIAS
     */
    protected const string SERVICE_FORM_FACTORY = 'FORM_FACTORY';

    public function createRequestClientDataMapper(): RequestClientDataMapperInterface
    {
        return new RequestClientDataMapper();
    }

    public function createAuthorizationRequestParker(): AuthorizationRequestParkerInterface
    {
        return new AuthorizationRequestParker($this->getSessionClient(), $this->getConfig());
    }

    public function createCartHandoffTokenParker(): CartHandoffTokenParkerInterface
    {
        return new CartHandoffTokenParker($this->getSessionClient());
    }

    public function createFreshAuthenticationChecker(): FreshAuthenticationCheckerInterface
    {
        return new FreshAuthenticationChecker($this->getSessionClient(), $this->getConfig());
    }

    public function createAuthorizationResponseUrlBuilder(): AuthorizationResponseUrlBuilderInterface
    {
        return new AuthorizationResponseUrlBuilder($this->getConfig());
    }

    public function createFramingProtectionHeaderExpander(): FramingProtectionHeaderExpanderInterface
    {
        return new FramingProtectionHeaderExpander($this->getConfig());
    }

    public function createAuthorizeRateLimiter(): AuthorizeRateLimiterInterface
    {
        return new AuthorizeRateLimiter($this->getAgenticCommerceClient());
    }

    public function createKillSwitchRouteGuard(): KillSwitchRouteGuardInterface
    {
        return new KillSwitchRouteGuard($this->getAgenticCommerceClient(), $this->getStoreClient());
    }

    public function createPrincipalLabelResolver(): PrincipalLabelResolverInterface
    {
        return new PrincipalLabelResolver($this->getAgenticCommerceClient());
    }

    public function createConsentFormDataProvider(): ConsentFormDataProviderInterface
    {
        return new ConsentFormDataProvider(
            $this->getAgenticCommerceClient(),
            $this->getStoreClient(),
            $this->getCurrencyClient(),
            $this->getConfig(),
        );
    }

    public function createConsentCollectionRequestBuilder(): ConsentCollectionRequestBuilderInterface
    {
        return new ConsentCollectionRequestBuilder(
            $this->createConsentFormDataProvider(),
            $this->createRequestClientDataMapper(),
            $this->getStoreClient(),
        );
    }

    public function createConsentViewDataBuilder(): ConsentViewDataBuilderInterface
    {
        return new ConsentViewDataBuilder();
    }

    public function createScopeGlossaryKeyReader(): ScopeGlossaryKeyReaderInterface
    {
        return new ScopeGlossaryKeyReader($this->getAgenticCommerceClient());
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     */
    public function getConsentForm(array $data, array $options): FormInterface
    {
        return $this->getFormFactory()->create(ConsentForm::class, $data, $options);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function getDisconnectForm(array $data = []): FormInterface
    {
        return $this->getFormFactory()->create(DisconnectForm::class, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function getLoginForm(array $data = []): FormInterface
    {
        return $this->getFormFactory()->create(LoginForm::class, $data);
    }

    public function getAgenticCommerceClient(): AgenticCommerceClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_AGENTIC_COMMERCE);
    }

    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getMultiCartClient(): MultiCartClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_MULTI_CART);
    }

    public function getSessionClient(): SessionClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_SESSION);
    }

    public function getStoreClient(): StoreClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_STORE);
    }

    public function getCurrencyClient(): CurrencyClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_CURRENCY);
    }

    public function getGlossaryStorageClient(): GlossaryStorageClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_GLOSSARY_STORAGE);
    }

    public function getPermissionClient(): PermissionClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_PERMISSION);
    }

    public function getRouter(): ChainRouterInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::SERVICE_ROUTER);
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->getProvidedDependency(static::SERVICE_FORM_FACTORY);
    }
}
