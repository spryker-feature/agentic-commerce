<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Shared\AgenticCommerce;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Shared
 * @group AgenticCommerce
 * @group AgenticCommerceConfigTest
 * Add your own group annotations below this line
 */
class AgenticCommerceConfigTest extends Unit
{
    protected AgenticCommerceSharedTester $tester;

    public function testGivenResourceColonActionScopesWhenReadingSupportedScopesThenTheConfiguredScopesAreReturnedInOrder(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, ['catalog:read', 'agentic-commerce:principal-read']);

        // Act
        $supportedScopes = (new AgenticCommerceConfig())->getSupportedScopes();

        // Assert
        $this->assertSame(['catalog:read', 'agentic-commerce:principal-read'], $supportedScopes);
    }

    public function testGivenNoProjectConfigurationWhenReadingTheRegistrationSwitchesThenBothAreOff(): void
    {
        // Arrange
        $this->tester->removeConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED);
        $this->tester->removeConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED);
        $sharedConfig = new AgenticCommerceConfig();

        // Act
        $isClientIdMetadataDocumentEnabled = $sharedConfig->isClientIdMetadataDocumentEnabled();
        $isDynamicClientRegistrationEnabled = $sharedConfig->isDynamicClientRegistrationEnabled();

        // Assert
        $this->assertFalse($isClientIdMetadataDocumentEnabled);
        $this->assertFalse($isDynamicClientRegistrationEnabled);
    }

    public function testGivenNoProjectConfigurationWhenReadingTheClientDataCaptureSwitchThenItIsOff(): void
    {
        // Arrange
        $this->tester->removeConfig(AgenticCommerceConstants::CONSENT_EVENT_CLIENT_DATA_CAPTURED);

        // Act
        $isConsentEventClientDataCaptured = (new AgenticCommerceConfig())->isConsentEventClientDataCaptured();

        // Assert
        $this->assertFalse($isConsentEventClientDataCaptured);
    }

    public function testGivenTheClientDataCaptureSwitchIsOnWhenReadingItThenItIsOn(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CONSENT_EVENT_CLIENT_DATA_CAPTURED, true);

        // Act
        $isConsentEventClientDataCaptured = (new AgenticCommerceConfig())->isConsentEventClientDataCaptured();

        // Assert
        $this->assertTrue($isConsentEventClientDataCaptured);
    }

    public function testGivenBothRegistrationSwitchesAreOnWhenReadingThemThenBothAreOn(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);
        $sharedConfig = new AgenticCommerceConfig();

        // Act
        $isClientIdMetadataDocumentEnabled = $sharedConfig->isClientIdMetadataDocumentEnabled();
        $isDynamicClientRegistrationEnabled = $sharedConfig->isDynamicClientRegistrationEnabled();

        // Assert
        $this->assertTrue($isClientIdMetadataDocumentEnabled);
        $this->assertTrue($isDynamicClientRegistrationEnabled);
    }

    public function testGivenNoIssuerWhenReadingTheRegistrationEndpointThenItIsEmpty(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Act
        $registrationEndpoint = (new AgenticCommerceConfig())->getRegistrationEndpoint();

        // Assert
        $this->assertSame('', $registrationEndpoint);
    }

    public function testGivenAnIssuerWithATrailingSlashWhenReadingTheIssuerAndItsUrlsThenNoUrlHasADoubleSlash(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, 'https://glue.example/');
        $sharedConfig = new AgenticCommerceConfig();

        // Act
        $issuer = $sharedConfig->getIssuer();
        $tokenEndpoint = $sharedConfig->getTokenEndpoint();
        $resourceUri = $sharedConfig->getResourceUri();
        $registrationEndpoint = $sharedConfig->getRegistrationEndpoint();

        // Assert
        $this->assertSame('https://glue.example', $issuer);
        $this->assertSame('https://glue.example/oauth/token', $tokenEndpoint);
        $this->assertSame('https://glue.example/agentic-commerce', $resourceUri);
        $this->assertSame('https://glue.example/oauth/register', $registrationEndpoint);
    }

    public function testGivenNoConfiguredScopesWhenReadingSupportedScopesThenNoScopeIsSupported(): void
    {
        // Arrange
        $this->tester->removeConfig(AgenticCommerceConstants::SUPPORTED_SCOPES);

        // Act
        $supportedScopes = (new AgenticCommerceConfig())->getSupportedScopes();

        // Assert
        $this->assertSame([], $supportedScopes);
    }

    /**
     * @dataProvider malformedScopeDataProvider
     */
    public function testGivenAScopeWithoutTheResourceColonActionShapeWhenReadingSupportedScopesThenAConfigurationExceptionIsThrown(string $malformedScope): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, ['catalog:read', $malformedScope]);

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        (new AgenticCommerceConfig())->getSupportedScopes();
    }

    public function testGivenTheIssuerTheAuthorizationEndpointTheResourceUriAndThePublicKeyWhenCheckingTheConfigurationThenDelegatedAccessIsConfigured(): void
    {
        // Arrange
        $this->configureDelegatedAccess();

        // Act
        $isConfigured = (new AgenticCommerceConfig())->isConfigured();

        // Assert
        $this->assertTrue($isConfigured);
    }

    /**
     * @dataProvider requiredConfigurationKeyDataProvider
     */
    public function testGivenOneRequiredValueIsMissingWhenCheckingTheConfigurationThenDelegatedAccessIsNotConfigured(string $missingConfigurationKey): void
    {
        // Arrange
        $this->configureDelegatedAccess();
        $this->tester->setConfig($missingConfigurationKey, '');

        // Act
        $isConfigured = (new AgenticCommerceConfig())->isConfigured();

        // Assert
        $this->assertFalse($isConfigured);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function requiredConfigurationKeyDataProvider(): array
    {
        return [
            'issuer' => [AgenticCommerceConstants::ISSUER],
            'authorization endpoint' => [AgenticCommerceConstants::AUTHORIZATION_ENDPOINT],
            'public key' => [AgenticCommerceConstants::PUBLIC_KEY],
        ];
    }

    /**
     * @dataProvider publicProtocolPathDataProvider
     */
    public function testGivenAPublicProtocolPathWhenMatchingThePublicProtocolPathPatternThenItMatches(string $path): void
    {
        // Act
        $isMatching = $this->matchesLikeTheSymfonyFirewall(AgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN, $path);

        // Assert
        $this->assertTrue($isMatching);
    }

    /**
     * @dataProvider protectedDelegatedAccessPathDataProvider
     */
    public function testGivenAProtectedDelegatedAccessPathWhenMatchingThePublicProtocolPathPatternThenItDoesNotMatch(string $path): void
    {
        // Act
        $isMatching = $this->matchesLikeTheSymfonyFirewall(AgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN, $path);

        // Assert
        $this->assertFalse($isMatching);
    }

    /**
     * @return array<string, array{string}>
     */
    public function publicProtocolPathDataProvider(): array
    {
        return [
            'token endpoint' => ['/oauth/token'],
            'revocation endpoint' => ['/oauth/revoke'],
            'authorization server metadata' => ['/.well-known/oauth-authorization-server'],
            'protected resource metadata' => ['/.well-known/oauth-protected-resource/agentic-commerce'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function protectedDelegatedAccessPathDataProvider(): array
    {
        return [
            'protected resource' => ['/agentic-commerce/principal'],
            'path below the token endpoint' => ['/oauth/token/extra'],
            'other oauth path' => ['/oauth/authorize'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public function malformedScopeDataProvider(): array
    {
        return [
            'hyphen instead of colon' => ['catalog-read'],
            'upper case resource' => ['Cart:write'],
            'empty action' => ['cart:'],
            'storefront identity scope' => ['customer'],
        ];
    }

    protected function configureDelegatedAccess(): void
    {
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, 'https://glue.shop.test');
        $this->tester->setConfig(AgenticCommerceConstants::AUTHORIZATION_ENDPOINT, 'https://shop.test/connect/authorize');
        $this->tester->setConfig(AgenticCommerceConstants::PUBLIC_KEY, 'public-key');
    }

    protected function matchesLikeTheSymfonyFirewall(string $pathPattern, string $path): bool
    {
        return (new PathRequestMatcher($pathPattern))->matches(Request::create($path));
    }
}
