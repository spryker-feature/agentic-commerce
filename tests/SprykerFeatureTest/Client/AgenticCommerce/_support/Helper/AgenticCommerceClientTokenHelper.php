<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\Helper;

use Codeception\Module;
use DateTimeImmutable;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\RegisteredClaims;
use League\OAuth2\Server\CryptKey;
use OpenSSLAsymmetricKey;
use Psr\Log\LoggerInterface;
use Spryker\Client\Oauth\OauthDependencyProvider;
use Spryker\Client\OauthCryptography\Communication\Plugin\Oauth\BearerTokenAuthorizationValidatorPlugin;
use Spryker\Client\OauthCryptography\Communication\Plugin\Oauth\FileSystemKeyLoaderPlugin;
use Spryker\Shared\Config\Config;
use Spryker\Shared\Log\Config\LoggerConfigInterface;
use Spryker\Shared\Oauth\OauthConstants;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Client\AgenticCommerce\Plugin\Oauth\AgenticCommerceTokenRejectingAccessTokenValidatorPlugin;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceService;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceFactory;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

class AgenticCommerceClientTokenHelper extends Module
{
    use ConfigHelperTrait;
    use DependencyHelperTrait;

    public const string ISSUER = 'http://glue-storefront.eu.spryker.local';

    public const string RESOURCE_URI = self::ISSUER . '/agentic-commerce';

    public const string CLIENT_ID = 'procurement-assistant';

    public const string STORE_NAME = 'DE';

    public const string CONSENT_UUID = 'f3a1d2c4-0000-4000-8000-000000000001';

    public const string CUSTOMER_REFERENCE = 'DE--7';

    public const string SCOPE = 'agentic-commerce:principal-read';

    public const string PRINCIPAL_RESOURCE_NAME = 'principal';

    public const string STOREFRONT_AUDIENCE = 'frontend';

    public const string USER_IDENTIFIER_KEY_CUSTOMER_REFERENCE = 'customer_reference';

    public const string OVERRIDE_REMOVE = '__remove__';

    protected const string TOKEN_LIFETIME = '+10 minutes';

    protected const string ISSUED_BEFORE_NOW = '-1 minute';

    protected const int RSA_KEY_BITS = 2048;

    protected const int TOKEN_IDENTIFIER_BYTE_LENGTH = 20;

    protected const string KEY_DETAILS_KEY = 'key';

    /**
     * @var array{string, string}|null
     */
    protected static ?array $delegatedAccessKeyPair = null;

    public function configureDelegatedAccessTokenValidation(): void
    {
        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
        $this->getConfigHelper()->setConfig(AgenticCommerceConstants::PUBLIC_KEY, $this->getDelegatedAccessKeyPair()[1]);
    }

    /**
     * @param list<\Spryker\Client\OauthExtension\Dependency\Plugin\AccessTokenValidatorPluginInterface> $accessTokenValidatorPlugins
     */
    public function configureStorefrontTokenValidation(array $accessTokenValidatorPlugins): void
    {
        $this->getDependencyHelper()->setDependency(OauthDependencyProvider::PLUGINS_KEY_LOADER, [new FileSystemKeyLoaderPlugin()]);
        $this->getDependencyHelper()->setDependency(OauthDependencyProvider::PLUGINS_AUTHORIZATION_VALIDATOR, [new BearerTokenAuthorizationValidatorPlugin()]);
        $this->getDependencyHelper()->setDependency(OauthDependencyProvider::PLUGINS_ACCESS_TOKEN_VALIDATOR, $accessTokenValidatorPlugins);
    }

    /**
     * Declares the principal resource, at the path of `RESOURCE_URI`, as the project wiring does; feature tests run without it.
     */
    public function declarePrincipalResource(): void
    {
        $this->declareProtectedResources([[
            'name' => static::PRINCIPAL_RESOURCE_NAME,
            'pathPrefix' => (string)parse_url(static::RESOURCE_URI, PHP_URL_PATH),
            'resourceUri' => static::RESOURCE_URI,
            'scopes' => [static::SCOPE],
        ]]);
    }

    /**
     * Gives the Client layer a real Service whose declared protected resources are exactly the given ones.
     *
     * @param list<array<string, mixed>> $resources
     */
    public function declareProtectedResources(array $resources): void
    {
        $this->getDependencyHelper()->setDependency(AgenticCommerceDependencyProvider::SERVICE_AGENTIC_COMMERCE, $this->createAgenticCommerceService($resources));
    }

    /**
     * Builds a real Service whose declared protected resources are exactly the given ones, logging to the given logger when there is one.
     *
     * @param list<array<string, mixed>> $resources
     */
    public function createAgenticCommerceService(array $resources, ?LoggerInterface $logger = null): AgenticCommerceServiceInterface
    {
        $protectedResourceProviderPlugin = new class ($resources) implements AgenticCommerceProtectedResourceProviderPluginInterface {
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

        $agenticCommerceServiceFactory = new class ([$protectedResourceProviderPlugin], $logger) extends AgenticCommerceServiceFactory {
            /**
             * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface> $protectedResourceProviderPlugins
             */
            public function __construct(protected array $protectedResourceProviderPlugins, protected ?LoggerInterface $logger)
            {
            }

            public function getProtectedResourceProviderPlugins(): array
            {
                return $this->protectedResourceProviderPlugins;
            }

            protected function getLogger(?LoggerConfigInterface $loggerConfig = null): LoggerInterface
            {
                return $this->logger ?? parent::getLogger($loggerConfig);
            }
        };

        return (new AgenticCommerceService())->setFactory($agenticCommerceServiceFactory);
    }

    public function createAgenticCommerceTokenRejectingAccessTokenValidatorPlugin(): AgenticCommerceTokenRejectingAccessTokenValidatorPlugin
    {
        return new AgenticCommerceTokenRejectingAccessTokenValidatorPlugin();
    }

    /**
     * @param array<string, mixed> $claimOverrides Claim name to value; {@link static::OVERRIDE_REMOVE} leaves the claim out.
     */
    public function signTokenWithTheDelegatedKey(array $claimOverrides = [], ?string $privateKey = null): string
    {
        $claims = array_merge([
            RegisteredClaims::ISSUER => static::ISSUER,
            RegisteredClaims::AUDIENCE => static::RESOURCE_URI,
            RegisteredClaims::SUBJECT => $this->buildUserIdentifier(),
            RegisteredClaims::ID => $this->generateTokenIdentifier(),
            RegisteredClaims::ISSUED_AT => new DateTimeImmutable(static::ISSUED_BEFORE_NOW),
            RegisteredClaims::NOT_BEFORE => new DateTimeImmutable(static::ISSUED_BEFORE_NOW),
            RegisteredClaims::EXPIRATION_TIME => new DateTimeImmutable(static::TOKEN_LIFETIME),
            AgenticCommerceConfig::CLAIM_CLIENT_ID => static::CLIENT_ID,
            AgenticCommerceConfig::CLAIM_SCOPES => [AgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER, static::SCOPE],
            AgenticCommerceConfig::CLAIM_STORE => static::STORE_NAME,
            AgenticCommerceConfig::CLAIM_CONSENT_UUID => static::CONSENT_UUID,
        ], $claimOverrides);

        return $this->buildToken($claims, $privateKey ?? $this->getDelegatedAccessKeyPair()[0]);
    }

    public function buildStorefrontAccessToken(): string
    {
        return $this->buildToken([
            RegisteredClaims::AUDIENCE => static::STOREFRONT_AUDIENCE,
            RegisteredClaims::SUBJECT => $this->buildUserIdentifier(),
            RegisteredClaims::ID => $this->generateTokenIdentifier(),
            RegisteredClaims::ISSUED_AT => new DateTimeImmutable(static::ISSUED_BEFORE_NOW),
            RegisteredClaims::NOT_BEFORE => new DateTimeImmutable(static::ISSUED_BEFORE_NOW),
            RegisteredClaims::EXPIRATION_TIME => new DateTimeImmutable(static::TOKEN_LIFETIME),
            AgenticCommerceConfig::CLAIM_SCOPES => [AgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER],
        ], $this->getStorefrontPrivateKey());
    }

    public function getStorefrontPrivateKey(): string
    {
        return (new CryptKey((string)Config::get(OauthConstants::PRIVATE_KEY_PATH), null, false))->getKeyContents();
    }

    public function getStorefrontPublicKey(): string
    {
        return (new CryptKey((string)Config::get(OauthConstants::PUBLIC_KEY_PATH), null, false))->getKeyContents();
    }

    public function generatePrivateKey(): string
    {
        openssl_pkey_export($this->createRsaKey(), $privateKey);

        return $privateKey;
    }

    /**
     * @param array<string, mixed> $claims
     */
    protected function buildToken(array $claims, string $privateKey): string
    {
        $configuration = Configuration::forAsymmetricSigner(new Sha256(), InMemory::plainText($privateKey), InMemory::plainText($privateKey));
        $builder = $configuration->builder();

        foreach ($claims as $name => $value) {
            if ($value === static::OVERRIDE_REMOVE) {
                continue;
            }

            $builder = match ($name) {
                RegisteredClaims::ISSUER => $builder->issuedBy($value),
                RegisteredClaims::AUDIENCE => $builder->permittedFor($value),
                RegisteredClaims::SUBJECT => $builder->relatedTo($value),
                RegisteredClaims::ID => $builder->identifiedBy($value),
                RegisteredClaims::ISSUED_AT => $builder->issuedAt($value),
                RegisteredClaims::NOT_BEFORE => $builder->canOnlyBeUsedAfter($value),
                RegisteredClaims::EXPIRATION_TIME => $builder->expiresAt($value),
                default => $builder->withClaim($name, $value),
            };
        }

        return $builder->getToken($configuration->signer(), $configuration->signingKey())->toString();
    }

    protected function buildUserIdentifier(): string
    {
        return json_encode([static::USER_IDENTIFIER_KEY_CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE], JSON_THROW_ON_ERROR);
    }

    protected function generateTokenIdentifier(): string
    {
        return bin2hex(random_bytes(static::TOKEN_IDENTIFIER_BYTE_LENGTH));
    }

    /**
     * @return array{string, string}
     */
    protected function getDelegatedAccessKeyPair(): array
    {
        if (static::$delegatedAccessKeyPair !== null) {
            return static::$delegatedAccessKeyPair;
        }

        $rsaKey = $this->createRsaKey();
        openssl_pkey_export($rsaKey, $privateKey);
        $keyDetails = openssl_pkey_get_details($rsaKey);

        static::$delegatedAccessKeyPair = [$privateKey, $keyDetails === false ? '' : (string)$keyDetails[static::KEY_DETAILS_KEY]];

        return static::$delegatedAccessKeyPair;
    }

    protected function createRsaKey(): OpenSSLAsymmetricKey
    {
        $rsaKey = openssl_pkey_new(['private_key_bits' => static::RSA_KEY_BITS, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($rsaKey === false) {
            $this->fail('An RSA key could not be generated.');
        }

        return $rsaKey;
    }
}
