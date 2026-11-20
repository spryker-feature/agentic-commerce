<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use GuzzleHttp\ClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\CompanyRole\Business\CompanyRoleFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;
use Spryker\Zed\OauthCodeFlow\Business\OauthCodeFlowFacadeInterface;
use Spryker\Zed\Permission\Business\PermissionFacadeInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\AgenticCommerceClientCreator;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\AgenticCommerceClientCreatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\AgenticCommerceClientStatusUpdater;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\AgenticCommerceClientStatusUpdaterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\BlockedClientMatcher;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\BlockedClientMatcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentClientWriter;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentClientWriterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFailureCache;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFailureCacheInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFetcher;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFetcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentRefusalChecker;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentRefusalCheckerInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataMapper;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataMapperInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientResolver;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientResolverInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientScopeCeilingResolver;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientScopeCeilingResolverInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientScopeCeilingUpdater;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientScopeCeilingUpdaterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\DynamicClientRegistrar;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\DynamicClientRegistrarInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuard;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuardInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\RedirectUriValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\RedirectUriValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\SelfRegisteredClientWriter;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\SelfRegisteredClientWriterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsBuilder;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsBuilderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsParser;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsParserInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsTypeReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationDetailsTypeReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationRequestValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationRequestValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\RedirectUriMatcher;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\RedirectUriMatcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\RequestedScopeNarrower;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\RequestedScopeNarrowerInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentAuthorizationCodeIssuer;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentAuthorizationCodeIssuerInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentCacheInvalidator;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentCacheInvalidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintWriter;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintWriterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentCreator;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentCreatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentEventReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentEventReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentEventWriter;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentEventWriterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentResolver;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentResolverInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentRevoker;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentRevokerInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentUsageValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentUsageValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\PrincipalResolver;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\PrincipalResolverInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ScopeNarrower;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ScopeNarrowerInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Key\KeyPairDistinctnessValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\Key\KeyPairDistinctnessValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Key\RefreshTokenEncryptionKeyDistinctnessValidator;
use SprykerFeature\Zed\AgenticCommerce\Business\Key\RefreshTokenEncryptionKeyDistinctnessValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Request\RequestConsentUuidReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Request\RequestConsentUuidReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\ScopeFilter\CompanyRolePermission\CompanyRolePermissionScopeFilter;
use SprykerFeature\Zed\AgenticCommerce\Business\ScopeFilter\CompanyRolePermission\CompanyRolePermissionScopeFilterInterface;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface getEntityManager()
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class AgenticCommerceBusinessFactory extends AbstractBusinessFactory
{
    use LoggerTrait;

    public function createSupportedScopeReader(): SupportedScopeReaderInterface
    {
        return new SupportedScopeReader(
            $this->getAgenticCommerceScopeProviderPlugins(),
            $this->getAgenticCommerceService(),
            $this->getConfig(),
        );
    }

    public function createAgenticCommerceClientCreator(): AgenticCommerceClientCreatorInterface
    {
        return new AgenticCommerceClientCreator(
            $this->getEntityManager(),
            $this->getOauthFacade(),
            $this->createRedirectUriValidator(),
            $this->createSupportedScopeReader(),
        );
    }

    public function createRedirectUriValidator(): RedirectUriValidatorInterface
    {
        return new RedirectUriValidator($this->getAgenticCommerceService(), $this->getConfig());
    }

    public function createClientMetadataValidator(): ClientMetadataValidatorInterface
    {
        return new ClientMetadataValidator(
            $this->createRedirectUriValidator(),
            $this->createRedirectUriMatcher(),
            $this->getAgenticCommerceService(),
            $this->createSupportedScopeReader(),
        );
    }

    public function createClientMetadataDocumentFetcher(): ClientMetadataDocumentFetcherInterface
    {
        return new ClientMetadataDocumentFetcher(
            $this->getGuzzleClient(),
            $this->createHostAddressGuard(),
            $this->getConfig(),
        );
    }

    public function createHostAddressGuard(): HostAddressGuardInterface
    {
        return new HostAddressGuard();
    }

    public function createClientResolver(): ClientResolverInterface
    {
        return new ClientResolver(
            $this->getRepository(),
            $this->createClientMetadataDocumentFetcher(),
            $this->createClientMetadataMapper(),
            $this->createClientMetadataValidator(),
            $this->createClientMetadataDocumentClientWriter(),
            $this->createBlockedClientMatcher(),
            $this->createClientMetadataDocumentFailureCache(),
            $this->createClientMetadataDocumentRefusalChecker(),
        );
    }

    public function createClientMetadataDocumentRefusalChecker(): ClientMetadataDocumentRefusalCheckerInterface
    {
        return new ClientMetadataDocumentRefusalChecker(
            $this->createBlockedClientMatcher(),
            $this->createClientMetadataDocumentFailureCache(),
            $this->getAgenticCommerceClient(),
            $this->getAgenticCommerceService(),
            $this->getConfig(),
        );
    }

    public function createClientMetadataDocumentFailureCache(): ClientMetadataDocumentFailureCacheInterface
    {
        return new ClientMetadataDocumentFailureCache($this->getAgenticCommerceClient(), $this->getConfig());
    }

    public function createBlockedClientMatcher(): BlockedClientMatcherInterface
    {
        return new BlockedClientMatcher(
            $this->getRepository(),
            $this->createRedirectUriValidator(),
            $this->createRedirectUriMatcher(),
            $this->getAgenticCommerceService(),
            $this->getConfig(),
        );
    }

    public function createClientMetadataMapper(): ClientMetadataMapperInterface
    {
        return new ClientMetadataMapper();
    }

    public function createClientMetadataDocumentClientWriter(): ClientMetadataDocumentClientWriterInterface
    {
        return new ClientMetadataDocumentClientWriter(
            $this->getRepository(),
            $this->getOauthFacade(),
            $this->createClientScopeCeilingResolver(),
            $this->createSelfRegisteredClientWriter(),
        );
    }

    public function createSelfRegisteredClientWriter(): SelfRegisteredClientWriterInterface
    {
        return new SelfRegisteredClientWriter(
            $this->getOauthFacade(),
            $this->getEntityManager(),
        );
    }

    public function createDynamicClientRegistrar(): DynamicClientRegistrarInterface
    {
        return new DynamicClientRegistrar(
            $this->createClientMetadataMapper(),
            $this->createClientMetadataValidator(),
            $this->createClientScopeCeilingResolver(),
            $this->createSelfRegisteredClientWriter(),
            $this->createBlockedClientMatcher(),
            $this->getConfig(),
        );
    }

    public function createClientScopeCeilingResolver(): ClientScopeCeilingResolverInterface
    {
        return new ClientScopeCeilingResolver(
            $this->getConfig(),
            $this->createSupportedScopeReader(),
        );
    }

    public function createAgenticCommerceClientStatusUpdater(): AgenticCommerceClientStatusUpdaterInterface
    {
        return new AgenticCommerceClientStatusUpdater(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createConsentRevoker(),
            $this->createRedirectUriValidator(),
            $this->getAgenticCommerceService(),
            $this->getConfig(),
        );
    }

    public function createClientScopeCeilingUpdater(): ClientScopeCeilingUpdaterInterface
    {
        return new ClientScopeCeilingUpdater(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createSupportedScopeReader(),
        );
    }

    public function createConsentRevoker(): ConsentRevokerInterface
    {
        return new ConsentRevoker(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createConsentEventWriter(),
            $this->getAgenticCommerceConsentPostRevokePlugins(),
            $this->getConfig(),
            $this->createConsentCacheInvalidator(),
        );
    }

    public function createConsentCacheInvalidator(): ConsentCacheInvalidatorInterface
    {
        return new ConsentCacheInvalidator($this->getAgenticCommerceClient(), $this->getStoreFacade(), $this->getLogger());
    }

    public function createConsentReader(): ConsentReaderInterface
    {
        return new ConsentReader($this->getRepository(), $this->createConsentConstraintReader());
    }

    public function createConsentUsageValidator(): ConsentUsageValidatorInterface
    {
        return new ConsentUsageValidator(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createPrincipalResolver(),
            $this->createConsentRevoker(),
            $this->getConfig(),
        );
    }

    public function createConsentCreator(): ConsentCreatorInterface
    {
        return new ConsentCreator(
            $this->createAuthorizationRequestValidator(),
            $this->createConsentResolver(),
            $this->createConsentAuthorizationCodeIssuer(),
            $this->createConsentRevoker(),
            $this->createConsentEventWriter(),
            $this->createConsentConstraintValidator(),
            $this->createConsentConstraintWriter(),
            $this->getRepository(),
            $this->getEntityManager(),
        );
    }

    public function createConsentResolver(): ConsentResolverInterface
    {
        return new ConsentResolver(
            $this->createPrincipalResolver(),
            $this->createScopeNarrower(),
            $this->getCustomerFacade(),
            $this->getStoreFacade(),
            $this->getConfig(),
        );
    }

    public function createConsentAuthorizationCodeIssuer(): ConsentAuthorizationCodeIssuerInterface
    {
        return new ConsentAuthorizationCodeIssuer($this->getOauthCodeFlowFacade(), $this->getConfig());
    }

    public function createConsentEventWriter(): ConsentEventWriterInterface
    {
        return new ConsentEventWriter($this->getEntityManager(), $this->getConfig());
    }

    public function createConsentEventReader(): ConsentEventReaderInterface
    {
        return new ConsentEventReader($this->getRepository());
    }

    public function createConsentConstraintValidator(): ConsentConstraintValidatorInterface
    {
        return new ConsentConstraintValidator($this->getAgenticCommerceConsentConstraintPlugins(), $this->getStoreFacade(), $this->createAuthorizationDetailsParser());
    }

    public function createConsentConstraintWriter(): ConsentConstraintWriterInterface
    {
        return new ConsentConstraintWriter($this->getAgenticCommerceConsentConstraintPlugins(), $this->getRepository(), $this->getEntityManager());
    }

    public function createConsentConstraintReader(): ConsentConstraintReaderInterface
    {
        return new ConsentConstraintReader($this->getAgenticCommerceConsentConstraintPlugins(), $this->getRepository());
    }

    public function createPrincipalResolver(): PrincipalResolverInterface
    {
        return new PrincipalResolver($this->getAgenticCommercePrincipalResolverPlugins());
    }

    public function createScopeNarrower(): ScopeNarrowerInterface
    {
        return new ScopeNarrower($this->getAgenticCommerceScopeFilterPlugins(), $this->createSupportedScopeReader());
    }

    public function createAuthorizationRequestValidator(): AuthorizationRequestValidatorInterface
    {
        return new AuthorizationRequestValidator(
            $this->createClientResolver(),
            $this->getConfig(),
            $this->createRedirectUriMatcher(),
            $this->createRequestedScopeNarrower(),
            $this->createSupportedScopeReader(),
            $this->getAgenticCommerceService(),
            $this->createAuthorizationDetailsParser(),
            $this->getStoreFacade(),
        );
    }

    public function createAuthorizationDetailsBuilder(): AuthorizationDetailsBuilderInterface
    {
        return new AuthorizationDetailsBuilder($this->getAgenticCommerceConsentConstraintPlugins(), $this->createConsentConstraintReader());
    }

    public function createAuthorizationDetailsTypeReader(): AuthorizationDetailsTypeReaderInterface
    {
        return new AuthorizationDetailsTypeReader($this->getAgenticCommerceConsentConstraintPlugins());
    }

    public function createAuthorizationDetailsParser(): AuthorizationDetailsParserInterface
    {
        return new AuthorizationDetailsParser($this->getAgenticCommerceConsentConstraintPlugins());
    }

    public function createRedirectUriMatcher(): RedirectUriMatcherInterface
    {
        return new RedirectUriMatcher($this->getConfig());
    }

    public function createRequestedScopeNarrower(): RequestedScopeNarrowerInterface
    {
        return new RequestedScopeNarrower();
    }

    public function createKeyPairDistinctnessValidator(): KeyPairDistinctnessValidatorInterface
    {
        return new KeyPairDistinctnessValidator($this->getConfig());
    }

    public function createRefreshTokenEncryptionKeyDistinctnessValidator(): RefreshTokenEncryptionKeyDistinctnessValidatorInterface
    {
        return new RefreshTokenEncryptionKeyDistinctnessValidator($this->getConfig());
    }

    public function createCompanyRolePermissionScopeFilter(): CompanyRolePermissionScopeFilterInterface
    {
        return new CompanyRolePermissionScopeFilter(
            $this->getCompanyUserFacade(),
            $this->getCompanyRoleFacade(),
            $this->getConfig(),
        );
    }

    public function createRequestConsentUuidReader(): RequestConsentUuidReaderInterface
    {
        return new RequestConsentUuidReader($this->getRequestConsentUuid());
    }

    public function getRequestConsentUuid(): ?string
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::SERVICE_REQUEST_CONSENT_UUID);
    }

    public function getGuzzleClient(): ClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_GUZZLE);
    }

    public function getOauthFacade(): OauthFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_OAUTH);
    }

    public function getOauthCodeFlowFacade(): OauthCodeFlowFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_OAUTH_CODE_FLOW);
    }

    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_CUSTOMER);
    }

    public function getCompanyUserFacade(): CompanyUserFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_COMPANY_USER);
    }

    public function getCompanyRoleFacade(): CompanyRoleFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_COMPANY_ROLE);
    }

    public function getPermissionFacade(): PermissionFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_PERMISSION);
    }

    public function getAgenticCommerceClient(): AgenticCommerceClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_AGENTIC_COMMERCE);
    }

    public function getStoreFacade(): StoreFacadeInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::FACADE_STORE);
    }

    public function getAgenticCommerceService(): AgenticCommerceServiceInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::SERVICE_AGENTIC_COMMERCE);
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeProviderPluginInterface>
     */
    public function getAgenticCommerceScopeProviderPlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_SCOPE_PROVIDER);
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommercePrincipalResolverPluginInterface>
     */
    public function getAgenticCommercePrincipalResolverPlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_PRINCIPAL_RESOLVER);
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeFilterPluginInterface>
     */
    public function getAgenticCommerceScopeFilterPlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_SCOPE_FILTER);
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface>
     */
    public function getAgenticCommerceConsentPostRevokePlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE);
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface>
     */
    public function getAgenticCommerceConsentConstraintPlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_CONSTRAINT);
    }
}
