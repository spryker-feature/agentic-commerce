<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Shared\AgenticCommerce\Helper;

use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\OauthClientTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use League\OAuth2\Server\CryptTrait;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClient;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientRedirectHost;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentConstraint;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentConstraintQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEvent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Orm\Zed\Oauth\Persistence\SpyOauthClient;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use PDOException;
use PHPUnit\Framework\AssertionFailedError;
use Propel\Runtime\Exception\PropelException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spryker\Zed\Oauth\Business\OauthFacade;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;
use Spryker\Zed\OauthCodeFlow\OauthCodeFlowConfig;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceService;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceFactory;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuard;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuardInterface;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeProviderPluginInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepository;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\CountingOrderConstraintCheckerPlugin;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\CountingOrderPlacementCheckerPlugin;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelperTrait;
use SprykerTest\Shared\Store\Helper\StoreDataHelperTrait;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Zed\Testify\Helper\Business\BusinessHelperTrait;
use Throwable;

class AgenticCommerceHelper extends Module
{
    use BusinessHelperTrait;
    use ConfigHelperTrait;
    use CustomerDataHelperTrait;
    use DataCleanupHelperTrait;
    use DependencyHelperTrait;
    use StoreDataHelperTrait;

    public const string CLIENT_NAME = 'Procurement Assistant';

    public const string REDIRECT_URI = 'https://client.example/callback';

    public const string SCOPE = 'agentic-commerce:principal-read';

    public const string STORE_NAME = 'DE';

    public const string OTHER_STORE_NAME = 'AT';

    public const string LOCALE_NAME = 'en_US';

    public const string SEED_REFRESH_TOKEN_IDENTIFIER = 'refreshTokenIdentifier';

    public const string ISSUER = 'http://glue-storefront.eu.spryker.local';

    public const string RESOURCE_URI = self::ISSUER . '/agentic-commerce';

    public const string PRINCIPAL_RESOURCE_NAME = 'principal';

    /**
     * The S256 code challenge of RFC 7636 (Proof Key for Code Exchange by OAuth Public Clients), appendix B.
     */
    public const string CODE_CHALLENGE = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    public const string STATE = 'af0ifjsldkj';

    public const string TEST_SCOPE_A = 'test:scope-a';

    public const string TEST_SCOPE_B = 'test:scope-b';

    public const int CONSENT_DURATION_IN_DAYS = 7;

    public const string CONSTRAINT_INPUT_KIND = 'amount';

    public const string CONSTRAINT_LABEL_GLOSSARY_KEY_PATTERN = 'agentic_commerce.consent.constraint.%s';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string DEFAULT_CONSENT_LIFETIME = '+30 days';

    protected const string SCOPE_DELIMITER = ' ';

    public const string PUBLIC_ADDRESS = '8.8.8.8';

    protected const string CLIENT_METADATA_DOCUMENT_DIRECTORY = 'client-metadata-documents';

    protected const string HEADERS_FILE_SUFFIX = '.headers';

    protected const string JSON_FILE_EXTENSION = '.json';

    protected const int HTTP_STATUS_OK = 200;

    /**
     * @var int<1, max>
     */
    protected const int CHUNK_BYTES = 1024;

    protected const string HANDLER_STAT_PRIMARY_IP = 'primary_ip';

    protected const string SQLSTATE_DUPLICATE_KEY = '23000';

    protected const int MYSQL_ERROR_DUPLICATE_ENTRY = 1062;

    protected bool $areProtectedResourcesDeclared = false;

    /**
     * @var list<array{request: \Psr\Http\Message\RequestInterface, options: array<string, mixed>}>
     */
    protected array $httpHistory = [];

    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        $this->areProtectedResourcesDeclared = false;
        $this->httpHistory = [];
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveAgenticCommerceClient(array $seed = []): AgenticCommerceClientTransfer
    {
        $clientIdentifier = $seed[AgenticCommerceClientTransfer::CLIENT_IDENTIFIER] ?? sprintf('delegated-access-client-%s', uniqid());

        (new SpyOauthClient())
            ->setIdentifier($clientIdentifier)
            ->setName($seed[AgenticCommerceClientTransfer::NAME] ?? static::CLIENT_NAME)
            ->setRedirectUri(static::REDIRECT_URI)
            ->setIsConfidential(false)
            ->save();

        $agenticCommerceClientEntity = (new SpyAgenticCommerceClient())
            ->setFkOauthClient($clientIdentifier)
            ->setRedirectUris(json_encode($seed[AgenticCommerceClientTransfer::REDIRECT_URIS] ?? [static::REDIRECT_URI], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
            ->setScopeCeiling(json_encode($seed[AgenticCommerceClientTransfer::SCOPE_CEILING] ?? [static::SCOPE], JSON_THROW_ON_ERROR))
            ->setRegistrationType($seed[AgenticCommerceClientTransfer::REGISTRATION_TYPE] ?? AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED)
            ->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED)
            ->setStatus($seed[AgenticCommerceClientTransfer::STATUS] ?? AgenticCommerceConfig::CLIENT_STATUS_ACTIVE)
            ->setClientUri($seed[AgenticCommerceClientTransfer::CLIENT_URI] ?? null)
            ->setLogoUri($seed[AgenticCommerceClientTransfer::LOGO_URI] ?? null)
            ->setSoftwareId($seed[AgenticCommerceClientTransfer::SOFTWARE_ID] ?? null);

        if (isset($seed[AgenticCommerceClientTransfer::CREATED_AT])) {
            $agenticCommerceClientEntity->setCreatedAt((new DateTime($seed[AgenticCommerceClientTransfer::CREATED_AT]))->format(static::DATE_TIME_FORMAT));
        }

        $this->addRedirectHosts($agenticCommerceClientEntity, $seed[AgenticCommerceClientTransfer::REDIRECT_URIS] ?? [static::REDIRECT_URI]);
        $agenticCommerceClientEntity->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($clientIdentifier): void {
            $this->debug(sprintf('Deleting delegated access client: %s', $clientIdentifier));
            $this->deleteClientWithConsents($clientIdentifier);
        });

        return $this->findClient($clientIdentifier);
    }

    public function haveOauthClientWithoutAgenticCommerceClient(?string $clientIdentifier = null): string
    {
        $clientIdentifier ??= sprintf('delegated-access-client-%s', uniqid());

        (new SpyOauthClient())
            ->setIdentifier($clientIdentifier)
            ->setName(static::CLIENT_NAME)
            ->setRedirectUri(static::REDIRECT_URI)
            ->setIsConfidential(false)
            ->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($clientIdentifier): void {
            $this->deleteClientWithConsents($clientIdentifier);
        });

        return $clientIdentifier;
    }

    /**
     * @param array<string> $redirectUris
     */
    protected function addRedirectHosts(SpyAgenticCommerceClient $agenticCommerceClientEntity, array $redirectUris): void
    {
        $redirectHosts = (new AgenticCommerceService())->normalizeHosts(array_map(
            static fn (string $redirectUri): string => (string)parse_url($redirectUri, PHP_URL_HOST),
            $redirectUris,
        ));

        foreach (array_unique($redirectHosts) as $redirectHost) {
            $agenticCommerceClientEntity->addAgenticCommerceClientRedirectHost((new SpyAgenticCommerceClientRedirectHost())->setHost($redirectHost));
        }
    }

    protected function deleteClientWithConsents(string $clientIdentifier): void
    {
        $delegatedAccessClientQuery = SpyAgenticCommerceClientQuery::create()->filterByFkOauthClient($clientIdentifier);
        $idAgenticCommerceClient = $delegatedAccessClientQuery->findOne()?->getIdAgenticCommerceClient();

        if ($idAgenticCommerceClient !== null) {
            SpyAgenticCommerceConsentQuery::create()->filterByFkAgenticCommerceClient($idAgenticCommerceClient)->delete();
        }

        SpyAgenticCommerceClientQuery::create()->filterByFkOauthClient($clientIdentifier)->delete();
        SpyOauthClientQuery::create()->filterByIdentifier($clientIdentifier)->delete();
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveAgenticCommerceConsent(array $seed = []): AgenticCommerceConsentTransfer
    {
        return $this->haveAgenticCommerceConsentForCustomer($this->haveAgenticCommerceCustomer(), $seed);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveAgenticCommerceConsentForCustomer(CustomerTransfer $customerTransfer, array $seed = []): AgenticCommerceConsentTransfer
    {
        $clientIdentifier = $seed[AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER] ?? $this->haveAgenticCommerceClient()->getClientIdentifierOrFail();
        $storeTransfer = $this->getStoreDataHelper()->haveStore([StoreTransfer::NAME => $seed[AgenticCommerceConsentTransfer::STORE_NAME] ?? static::STORE_NAME]);

        $agenticCommerceConsentEntity = (new SpyAgenticCommerceConsent())
            ->setFkAgenticCommerceClient($this->findClient($clientIdentifier)->getIdAgenticCommerceClientOrFail())
            ->setFkCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setPrincipalType($seed[AgenticCommerceConsentTransfer::PRINCIPAL_TYPE] ?? AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
            ->setPrincipalReference($seed[AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE] ?? $customerTransfer->getCustomerReferenceOrFail())
            ->setFkStore($storeTransfer->getIdStoreOrFail())
            ->setGrantedScopes(json_encode($seed[AgenticCommerceConsentTransfer::GRANTED_SCOPES] ?? [static::SCOPE], JSON_THROW_ON_ERROR))
            ->setStatus($seed[AgenticCommerceConsentTransfer::STATUS] ?? AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->setExpiresAt($seed[AgenticCommerceConsentTransfer::EXPIRES_AT] ?? (new DateTime(static::DEFAULT_CONSENT_LIFETIME))->format(static::DATE_TIME_FORMAT))
            ->setLastUsedAt($seed[AgenticCommerceConsentTransfer::LAST_USED_AT] ?? null)
            ->setRefreshTokenIdentifier($seed[static::SEED_REFRESH_TOKEN_IDENTIFIER] ?? null);
        $agenticCommerceConsentEntity->save();

        return $this->findConsentByUuid((string)$agenticCommerceConsentEntity->getUuid());
    }

    /**
     * @param list<string> $supportedScopes
     */
    public function setSupportedScopes(array $supportedScopes): void
    {
        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, $supportedScopes);
    }

    public function createValidAuthorizationRequest(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer,
        ?string $resourceUri = null
    ): AgenticCommerceAuthorizationRequestTransfer {
        if ($resourceUri === null) {
            $this->getConfigHelper()->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);

            if (!$this->areProtectedResourcesDeclared) {
                $this->declarePrincipalResource();
            }
        }

        return (new AgenticCommerceAuthorizationRequestTransfer())
            ->setClientId($agenticCommerceClientTransfer->getClientIdentifierOrFail())
            ->setRedirectUri($agenticCommerceClientTransfer->getRedirectUris()[0] ?? static::REDIRECT_URI)
            ->setResponseType(AgenticCommerceConfig::RESPONSE_TYPE_CODE)
            ->setCodeChallenge(static::CODE_CHALLENGE)
            ->setCodeChallengeMethod(AgenticCommerceConfig::CODE_CHALLENGE_METHOD_S256)
            ->setResource($resourceUri ?? static::RESOURCE_URI)
            ->setState(static::STATE);
    }

    public function declarePrincipalResource(): void
    {
        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
        $this->declareProtectedResources([[
            'name' => static::PRINCIPAL_RESOURCE_NAME,
            'pathPrefix' => $this->getPrincipalResourcePath(),
            'resourceUri' => static::RESOURCE_URI,
            'scopes' => [static::SCOPE],
        ]]);
    }

    /**
     * @param list<array<string, mixed>> $resources
     */
    public function declareProtectedResources(array $resources): void
    {
        $agenticCommerceServiceFactory = new class ([$this->createProtectedResourceProviderPlugin($resources)]) extends AgenticCommerceServiceFactory {
            /**
             * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface> $protectedResourceProviderPlugins
             */
            public function __construct(protected array $protectedResourceProviderPlugins)
            {
            }

            public function getProtectedResourceProviderPlugins(): array
            {
                return $this->protectedResourceProviderPlugins;
            }
        };

        $this->areProtectedResourcesDeclared = true;
        $this->getDependencyHelper()->setDependency(
            AgenticCommerceDependencyProvider::SERVICE_AGENTIC_COMMERCE,
            (new AgenticCommerceService())->setFactory($agenticCommerceServiceFactory),
        );
    }

    /**
     * @param list<array<string, mixed>> $resources
     */
    public function createProtectedResourceProviderPlugin(array $resources): AgenticCommerceProtectedResourceProviderPluginInterface
    {
        return new class ($resources) implements AgenticCommerceProtectedResourceProviderPluginInterface {
            /**
             * @param list<array<string, mixed>> $resources
             */
            public function __construct(protected array $resources)
            {
            }

            public function getProtectedResources(): array
            {
                return array_map(
                    static fn (array $resource): AgenticCommerceProtectedResourceTransfer => (new AgenticCommerceProtectedResourceTransfer())->fromArray($resource, true),
                    $this->resources,
                );
            }
        };
    }

    public function areProtectedResourcesDeclared(): bool
    {
        return $this->areProtectedResourcesDeclared;
    }

    public function getPrincipalResourcePath(): string
    {
        return (string)parse_url(static::RESOURCE_URI, PHP_URL_PATH);
    }

    public function haveAgenticCommerceCustomer(): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([LocaleTransfer::LOCALE_NAME => static::LOCALE_NAME]);
    }

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommercePrincipalResolverPluginInterface> $principalResolverPlugins
     */
    public function setPrincipalResolverPlugins(array $principalResolverPlugins): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_PRINCIPAL_RESOLVER, $principalResolverPlugins);
    }

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeFilterPluginInterface> $scopeFilterPlugins
     */
    public function setScopeFilterPlugins(array $scopeFilterPlugins): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_SCOPE_FILTER, $scopeFilterPlugins);
    }

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeProviderPluginInterface> $scopeProviderPlugins
     */
    public function setScopeProviderPlugins(array $scopeProviderPlugins): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_SCOPE_PROVIDER, $scopeProviderPlugins);
    }

    /**
     * @param array<string, string> $nameToGlossaryKey
     */
    public function createScopeProviderPlugin(array $nameToGlossaryKey): AgenticCommerceScopeProviderPluginInterface
    {
        return new class ($nameToGlossaryKey) implements AgenticCommerceScopeProviderPluginInterface
        {
            /**
             * @param array<string, string> $nameToGlossaryKey
             */
            public function __construct(protected array $nameToGlossaryKey)
            {
            }

            public function getScopes(): array
            {
                $scopeTransfers = [];

                foreach ($this->nameToGlossaryKey as $name => $glossaryKey) {
                    $scopeTransfers[] = (new AgenticCommerceScopeTransfer())->setName((string)$name)->setGlossaryKey($glossaryKey);
                }

                return $scopeTransfers;
            }
        };
    }

    /**
     * @param list<string> $scopes
     */
    public function createConsentCollectionRequest(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer,
        CustomerTransfer $customerTransfer,
        array $scopes,
        ?string $principalType = null,
        ?string $principalReference = null,
        ?string $storeName = null,
        ?string $resourceUri = null
    ): AgenticCommerceConsentCollectionRequestTransfer {
        $storeName ??= static::STORE_NAME;
        $this->getStoreDataHelper()->haveStore([StoreTransfer::NAME => $storeName]);

        $agenticCommerceConsentTransfer = (new AgenticCommerceConsentTransfer())
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setStoreName($storeName)
            ->setDurationInDays(static::CONSENT_DURATION_IN_DAYS)
            ->setGrantedScopes($scopes)
            ->setPrincipal((new AgenticCommercePrincipalTransfer())
                ->setPrincipalType($principalType ?? AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
                ->setPrincipalReference($principalReference ?? $customerTransfer->getCustomerReferenceOrFail()));

        return (new AgenticCommerceConsentCollectionRequestTransfer())
            ->setIsTransactional(true)
            ->setAuthorizationRequest(
                $this->createValidAuthorizationRequest($agenticCommerceClientTransfer, $resourceUri)->setScope(implode(static::SCOPE_DELIMITER, $scopes)),
            )
            ->addAgenticCommerceConsent($agenticCommerceConsentTransfer);
    }

    public function countConsentsByCustomerReference(string $customerReference): int
    {
        return SpyAgenticCommerceConsentQuery::create()
            ->filterByCustomerReference($customerReference)
            ->count();
    }

    /**
     * @return list<string>
     */
    public function getConsentEventNames(string $consentUuid): array
    {
        return array_map(
            fn (AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer): string => $agenticCommerceConsentEventTransfer->getEventOrFail(),
            $this->getConsentEvents($consentUuid),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function decryptAuthorizationCode(string $authorizationCode): array
    {
        $authorizationCodeDecrypter = new class ((new OauthCodeFlowConfig())->getEncryptionKey()) {
            use CryptTrait;

            public function __construct(string $encryptionKey)
            {
                $this->setEncryptionKey($encryptionKey);
            }

            public function decryptAuthorizationCode(string $authorizationCode): string
            {
                return $this->decrypt($authorizationCode);
            }
        };

        return json_decode($authorizationCodeDecrypter->decryptAuthorizationCode($authorizationCode), true, flags: JSON_THROW_ON_ERROR);
    }

    public function haveAgenticCommerceConsentGrantedEvent(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, string $clientName): int
    {
        $agenticCommerceConsentEventEntity = (new SpyAgenticCommerceConsentEvent())
            ->setFkAgenticCommerceConsent($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail())
            ->setEvent(AgenticCommerceConfig::CONSENT_EVENT_GRANTED)
            ->setActor(AgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER)
            ->setScopes(json_encode($agenticCommerceConsentTransfer->getGrantedScopes(), JSON_THROW_ON_ERROR))
            ->setClientName($clientName)
            ->setConstraints($this->encodeConsentConstraints($agenticCommerceConsentTransfer));
        $agenticCommerceConsentEventEntity->save();

        return (int)$agenticCommerceConsentEventEntity->getIdAgenticCommerceConsentEvent();
    }

    public function renameClient(string $clientIdentifier, string $name): void
    {
        SpyOauthClientQuery::create()->filterByIdentifier($clientIdentifier)->findOne()?->setName($name)->save();
    }

    public function findClient(string $clientIdentifier): AgenticCommerceClientTransfer
    {
        return (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientIdentifier)
            ?? throw new AssertionFailedError(sprintf('Delegated-access client "%s" does not exist.', $clientIdentifier));
    }

    public function findClientOrNull(string $clientIdentifier): ?AgenticCommerceClientTransfer
    {
        return (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientIdentifier);
    }

    /**
     * Blocks the client as a block by software id or redirect host would: `$blockedBy` is the family block it records, `null` for a client blocked on its own.
     */
    public function blockClient(string $clientIdentifier, ?string $blockedBy = null): void
    {
        SpyAgenticCommerceClientQuery::create()
            ->filterByFkOauthClient($clientIdentifier)
            ->update([
                'Status' => AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
                'BlockedBy' => $blockedBy,
            ]);
    }

    public function countClients(): int
    {
        return SpyAgenticCommerceClientQuery::create()->count();
    }

    public function findOauthClient(string $clientIdentifier): OauthClientTransfer
    {
        return (new OauthFacade())->findClientByIdentifier((new OauthClientTransfer())->setIdentifier($clientIdentifier))
            ?? throw new AssertionFailedError(sprintf('OAuth client "%s" does not exist.', $clientIdentifier));
    }

    /**
     * @param list<string> $redirectUris
     * @param list<string> $scopeCeiling
     */
    public function haveClientMetadataDocumentClient(
        string $clientIdentifier,
        array $redirectUris,
        string $expiresAt,
        string $status = AgenticCommerceConfig::CLIENT_STATUS_ACTIVE,
        array $scopeCeiling = []
    ): AgenticCommerceClientTransfer {
        (new SpyOauthClient())
            ->setIdentifier($clientIdentifier)
            ->setName(static::CLIENT_NAME)
            ->setRedirectUri($redirectUris[0])
            ->setIsConfidential(false)
            ->save();

        $agenticCommerceClientEntity = (new SpyAgenticCommerceClient())
            ->setFkOauthClient($clientIdentifier)
            ->setRedirectUris(json_encode($redirectUris, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
            ->setScopeCeiling(json_encode($scopeCeiling, JSON_THROW_ON_ERROR))
            ->setRegistrationType(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT)
            ->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED)
            ->setStatus($status)
            ->setMetadataFetchedAt((new DateTime($expiresAt))->modify('-1 hour')->format(static::DATE_TIME_FORMAT))
            ->setMetadataExpiresAt((new DateTime($expiresAt))->format(static::DATE_TIME_FORMAT));
        $this->addRedirectHosts($agenticCommerceClientEntity, $redirectUris);
        $agenticCommerceClientEntity->save();

        $this->addClientCleanup($clientIdentifier);

        return $this->findClient($clientIdentifier);
    }

    public function createValidAuthorizationRequestForClientIdentifier(
        string $clientIdentifier,
        string $redirectUri
    ): AgenticCommerceAuthorizationRequestTransfer {
        $this->addClientCleanup($clientIdentifier);

        return $this->createValidAuthorizationRequest(
            (new AgenticCommerceClientTransfer())->setClientIdentifier($clientIdentifier)->setRedirectUris([$redirectUri]),
        );
    }

    public function mockClientMetadataDocumentResponse(ResponseInterface ...$responses): void
    {
        $handlerStack = HandlerStack::create($this->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, ...$responses));
        $handlerStack->push(Middleware::tap(function (RequestInterface $request, array $options): void {
            $this->httpHistory[] = ['request' => $request, 'options' => $options];
        }));

        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::CLIENT_GUZZLE, new Client(['handler' => $handlerStack]));
    }

    public function allowAllHostsInTheAddressGuard(): void
    {
        $this->getBusinessHelper()->mockFactoryMethod('createHostAddressGuard', $this->createAllHostsAllowedAddressGuard());
    }

    public function createAllHostsAllowedAddressGuard(): HostAddressGuardInterface
    {
        return new class (new HostAddressGuard()) implements HostAddressGuardInterface {
            public function __construct(protected HostAddressGuardInterface $hostAddressGuard)
            {
            }

            public function resolveAllowedAddress(string $host): string
            {
                return AgenticCommerceHelper::PUBLIC_ADDRESS;
            }

            public function isAllowedAddress(string $address): bool
            {
                return $this->hostAddressGuard->isAllowedAddress($address);
            }

            public function isSameAddress(string $firstAddress, string $secondAddress): bool
            {
                return $this->hostAddressGuard->isSameAddress($firstAddress, $secondAddress);
            }
        };
    }

    /**
     * @param list<string> $redirectUris
     * @param list<int|string>|null $errorInfo
     */
    public function simulateConcurrentCreationOfClientMetadataDocumentClient(
        string $clientIdentifier,
        array $redirectUris,
        ?array $errorInfo = null
    ): void {
        $this->getBusinessHelper()->mockFactoryMethod('getOauthFacade', Stub::makeEmpty(OauthFacadeInterface::class, [
            'findClientByIdentifier' => null,
            'saveClient' => function () use ($clientIdentifier, $redirectUris, $errorInfo): never {
                $this->haveClientMetadataDocumentClient($clientIdentifier, $redirectUris, '+1 hour');

                $duplicateKeyException = new PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry for key \'spy_oauth_client-identifier\'');
                $duplicateKeyException->errorInfo = $errorInfo ?? [static::SQLSTATE_DUPLICATE_KEY, static::MYSQL_ERROR_DUPLICATE_ENTRY, 'Duplicate entry'];

                throw new PropelException('Unable to execute INSERT statement [INSERT INTO `spy_oauth_client` ...]', 0, $duplicateKeyException);
            },
        ]));
    }

    public function getHttpRequestCount(): int
    {
        return count($this->httpHistory);
    }

    public function createClientMetadataDocumentFixtureResponse(string $fixtureName): ResponseInterface
    {
        $fixturePath = codecept_data_dir(static::CLIENT_METADATA_DOCUMENT_DIRECTORY . DIRECTORY_SEPARATOR . $fixtureName);
        $headers = json_decode((string)file_get_contents($fixturePath . static::HEADERS_FILE_SUFFIX . static::JSON_FILE_EXTENSION), true, flags: JSON_THROW_ON_ERROR);

        return new Response(static::HTTP_STATUS_OK, $headers, (string)file_get_contents($fixturePath . static::JSON_FILE_EXTENSION));
    }

    public function createClientMetadataDocumentHandler(string $primaryIp, ResponseInterface ...$responses): callable
    {
        return static function (RequestInterface $request, array $options) use (&$responses, $primaryIp): PromiseInterface {
            $response = array_shift($responses);

            if ($response === null) {
                return Create::rejectionFor(new RequestException('No response is left for this request.', $request));
            }

            try {
                if (isset($options['on_headers'])) {
                    $options['on_headers']($response);
                }

                $body = (string)$response->getBody();

                foreach ($body === '' ? [] : str_split($body, static::CHUNK_BYTES) as $chunk) {
                    if ($options['sink']->write($chunk) === 0) {
                        throw new RequestException('Failure writing output to destination', $request);
                    }
                }
            } catch (Throwable $exception) {
                return Create::rejectionFor($exception instanceof RequestException ? $exception : new RequestException($exception->getMessage(), $request, null, $exception));
            }

            if (isset($options['on_stats'])) {
                $options['on_stats'](new TransferStats($request, $response, 0.0, null, [static::HANDLER_STAT_PRIMARY_IP => $primaryIp]));
            }

            return Create::promiseFor($response->withBody($options['sink']));
        };
    }

    protected function addClientCleanup(string $clientIdentifier): void
    {
        $this->getDataCleanupHelper()->_addCleanup(function () use ($clientIdentifier): void {
            $this->deleteClientWithConsents($clientIdentifier);
        });
    }

    public function findConsentByUuid(string $consentUuid): AgenticCommerceConsentTransfer
    {
        return (new AgenticCommerceRepository())->findAgenticCommerceConsentByUuid($consentUuid)
            ?? throw new AssertionFailedError(sprintf('Delegated-access consent "%s" does not exist.', $consentUuid));
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer>
     */
    public function getConsentEvents(string $consentUuid): array
    {
        return (new AgenticCommerceRepository())->getAgenticCommerceConsentEvents(
            $this->findConsentByUuid($consentUuid)->getIdAgenticCommerceConsentOrFail(),
        );
    }

    public function haveConsentRefreshTokenIdentifier(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, string $refreshTokenIdentifier): void
    {
        SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->update(['RefreshTokenIdentifier' => $refreshTokenIdentifier]);
    }

    public function getLastConsentEvent(string $consentUuid): AgenticCommerceConsentEventTransfer
    {
        $agenticCommerceConsentEventTransfers = $this->getConsentEvents($consentUuid);

        return end($agenticCommerceConsentEventTransfers)
            ?: throw new AssertionFailedError(sprintf('Delegated-access consent "%s" has no event.', $consentUuid));
    }

    /**
     * @return list<string>
     */
    public function getActiveConsentUuidsOfTheSameClientPrincipalAndStore(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): array
    {
        return (new AgenticCommerceRepository())->getActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
            $agenticCommerceConsentTransfer->getClientIdentifierOrFail(),
            $agenticCommerceConsentTransfer->getPrincipalTypeOrFail(),
            $agenticCommerceConsentTransfer->getPrincipalReferenceOrFail(),
            $agenticCommerceConsentTransfer->getIdStoreOrFail(),
        );
    }

    public function createUsageRequest(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): AgenticCommerceConsentUsageRequestTransfer {
        return (new AgenticCommerceConsentUsageRequestTransfer())
            ->setConsentUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->setClientIdentifier($agenticCommerceConsentTransfer->getClientIdentifierOrFail())
            ->setCustomerReference($agenticCommerceConsentTransfer->getCustomerReferenceOrFail())
            ->setStoreName($agenticCommerceConsentTransfer->getStoreNameOrFail());
    }

    public function moveLastUsedAtBackBySeconds(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, int $seconds): void
    {
        $lastUsedAt = $this->findConsentByUuid($agenticCommerceConsentTransfer->getUuidOrFail())->getLastUsedAtOrFail();

        SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->update(['LastUsedAt' => $this->shiftTimestampBySeconds($lastUsedAt, -$seconds)]);
    }

    public function moveCreatedAtBackBySeconds(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, int $seconds): void
    {
        $createdAt = $this->findConsentByUuid($agenticCommerceConsentTransfer->getUuidOrFail())->getCreatedAtOrFail();

        SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->update(['CreatedAt' => $this->shiftTimestampBySeconds($createdAt, -$seconds)]);
    }

    protected function shiftTimestampBySeconds(string $timestamp, int $seconds): string
    {
        return (new DateTime($timestamp))->modify(sprintf('%+d seconds', $seconds))->format(static::DATE_TIME_FORMAT);
    }

    public function findConsentRefreshTokenIdentifier(string $consentUuid): ?string
    {
        return SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($consentUuid)
            ->findOne()
            ?->getRefreshTokenIdentifier();
    }

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $consentConstraintPlugins
     */
    public function setConsentConstraintPlugins(array $consentConstraintPlugins): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_CONSTRAINT, $consentConstraintPlugins);
    }

    /**
     * @param array<string, string> $options
     */
    public function createConstraintPlugin(
        string $type,
        bool $isApplicable,
        bool $isRequired,
        ?callable $validate = null,
        ?string $applicableScope = null,
        ?string $valueKey = null,
        array $options = []
    ): AgenticCommerceConsentConstraintPluginInterface {
        return new class ($type, $isApplicable, $isRequired, $validate, $applicableScope, $valueKey, $options) implements AgenticCommerceConsentConstraintPluginInterface {
            /**
             * @var callable|null
             */
            protected $validate;

            public function __construct(
                protected string $type,
                protected bool $isApplicable,
                protected bool $isRequired,
                ?callable $validate,
                protected ?string $applicableScope,
                protected ?string $valueKey,
                protected array $options
            ) {
                $this->validate = $validate;
            }

            public function getType(): string
            {
                return $this->type;
            }

            public function isApplicable(AgenticCommercePrincipalTransfer $principalTransfer, array $scopes): bool
            {
                return $this->isApplicable && ($this->applicableScope === null || in_array($this->applicableScope, $scopes, true));
            }

            public function isRequired(AgenticCommercePrincipalTransfer $principalTransfer, array $scopes): bool
            {
                return $this->isRequired;
            }

            public function getFieldDefinition(
                AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
            ): AgenticCommerceConstraintFieldTransfer {
                return (new AgenticCommerceConstraintFieldTransfer())
                    ->setLabelGlossaryKey(sprintf(AgenticCommerceHelper::CONSTRAINT_LABEL_GLOSSARY_KEY_PATTERN, $this->type))
                    ->setInputKind(AgenticCommerceHelper::CONSTRAINT_INPUT_KIND)
                    ->setCurrencyCode($constraintFieldRequestTransfer->getCurrencyCode())
                    ->setValueKey($this->valueKey)
                    ->setOptions($this->options);
            }

            public function validate(array $value, AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer): array
            {
                return $this->validate === null ? [] : ($this->validate)($value, $constraintFieldRequestTransfer);
            }
        };
    }

    /**
     * @param array<string, mixed> $value
     */
    public function haveAgenticCommerceConsentConstraint(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, string $type, array $value): void
    {
        (new SpyAgenticCommerceConsentConstraint())
            ->setFkAgenticCommerceConsent($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail())
            ->setType($type)
            ->setValue(json_encode($value, JSON_THROW_ON_ERROR))
            ->save();
    }

    protected function encodeConsentConstraints(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): string
    {
        $constraints = [];
        $agenticCommerceConsentConstraintEntities = SpyAgenticCommerceConsentConstraintQuery::create()
            ->filterByFkAgenticCommerceConsent($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail())
            ->find();

        foreach ($agenticCommerceConsentConstraintEntities as $agenticCommerceConsentConstraintEntity) {
            $constraints[] = [
                AgenticCommerceConsentConstraintTransfer::TYPE => $agenticCommerceConsentConstraintEntity->getType(),
                AgenticCommerceConsentConstraintTransfer::VALUE => json_decode($agenticCommerceConsentConstraintEntity->getValue(), true, 512, JSON_THROW_ON_ERROR),
            ];
        }

        return json_encode($constraints, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<string, mixed> $value
     */
    public function createConsentConstraint(string $type, array $value): AgenticCommerceConsentConstraintTransfer
    {
        return (new AgenticCommerceConsentConstraintTransfer())->setType($type)->setValue($value);
    }

    public function countConsentConstraintRows(int $idAgenticCommerceConsent): int
    {
        return SpyAgenticCommerceConsentConstraintQuery::create()
            ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent)
            ->count();
    }

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceOrderPlacementCheckerPluginInterface> $orderPlacementCheckerPlugins
     */
    public function setOrderPlacementCheckerPlugins(array $orderPlacementCheckerPlugins): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_ORDER_PLACEMENT_CHECKER, $orderPlacementCheckerPlugins);
    }

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceOrderConstraintCheckerPluginInterface> $orderConstraintCheckerPlugins
     */
    public function setOrderConstraintCheckerPlugins(array $orderConstraintCheckerPlugins): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_ORDER_CONSTRAINT_CHECKER, $orderConstraintCheckerPlugins);
    }

    public function createOrderConstraintCheckerPlugin(string $type, ?string $refusalReason = null): CountingOrderConstraintCheckerPlugin
    {
        return new CountingOrderConstraintCheckerPlugin($type, $refusalReason);
    }

    public function createCheckerPlugin(
        string $principalType,
        ?int $orderTotalLimit = null,
        string $result = AgenticCommerceConfig::RESULT_ALLOWED,
        ?string $approvalStatus = null
    ): CountingOrderPlacementCheckerPlugin {
        return new CountingOrderPlacementCheckerPlugin($principalType, $orderTotalLimit, $result, $approvalStatus);
    }

    /**
     * @param list<string> $scopes
     */
    public function createFieldRequest(string $principalType, array $scopes, ?string $currencyCode): AgenticCommerceConstraintFieldRequestTransfer
    {
        return (new AgenticCommerceConstraintFieldRequestTransfer())
            ->setPrincipal((new AgenticCommercePrincipalTransfer())->setPrincipalType($principalType))
            ->setScopes($scopes)
            ->setStoreName(static::STORE_NAME)
            ->setCurrencyCode($currencyCode);
    }
}
