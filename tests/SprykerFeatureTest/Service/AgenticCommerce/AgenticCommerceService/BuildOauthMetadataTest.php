<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group BuildOauthMetadataTest
 * Add your own group annotations below this line
 */
class BuildOauthMetadataTest extends Unit
{
    protected const string ISSUER = 'https://glue-storefront.shop.test';

    protected const string RESOURCE_URI = self::ISSUER . '/agentic-commerce';

    protected const string AUTHORIZATION_ENDPOINT = 'https://shop.test/connect/authorize';

    protected const string TOKEN_ENDPOINT = self::ISSUER . '/oauth/token';

    protected const string REVOCATION_ENDPOINT = self::ISSUER . '/oauth/revoke';

    protected const string REGISTRATION_ENDPOINT = self::ISSUER . '/oauth/register';

    protected const string SCOPE = 'agentic-commerce:principal-read';

    protected const string OTHER_SCOPE = 'cart:read';

    protected const string OTHER_RESOURCE_URI = 'https://glue-storefront.shop.test/other';

    protected AgenticCommerceServiceTester $tester;

    protected function _before(): void
    {
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
        $this->tester->setConfig(AgenticCommerceConstants::AUTHORIZATION_ENDPOINT, static::AUTHORIZATION_ENDPOINT);
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, [static::SCOPE]);
    }

    public function testGivenTheConfiguredResourceWhenBuildingTheProtectedResourceMetadataThenItNamesTheResourceItsAuthorizationServerAndScopes(): void
    {
        // Act
        $metadata = $this->tester->getService()->buildProtectedResourceMetadata()->toArray(true, false);

        // Assert
        $this->assertSame(static::RESOURCE_URI, $metadata['resource']);
        $this->assertSame([static::ISSUER], $metadata['authorization_servers']);
        $this->assertSame([static::SCOPE], $metadata['scopes_supported']);
        $this->assertSame(['header'], $metadata['bearer_methods_supported']);
    }

    public function testGivenTheResourceAtTheConfiguredUriIsDeclaredAndNoScopeIsConfiguredWhenBuildingTheProtectedResourceMetadataThenItListsOnlyThatResourcesScopes(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, []);
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => static::RESOURCE_URI, 'scopes' => [static::SCOPE]],
            ['name' => 'other', 'pathPrefix' => '/other', 'resourceUri' => static::OTHER_RESOURCE_URI, 'scopes' => [static::OTHER_SCOPE]],
        ])]);

        // Act
        $metadata = $this->tester->getService()->buildProtectedResourceMetadata()->toArray(true, false);

        // Assert
        $this->assertSame(static::RESOURCE_URI, $metadata['resource']);
        $this->assertSame([static::SCOPE], $metadata['scopes_supported']);
    }

    public function testGivenTheDeclaredResourceAndTheSameScopeConfiguredWhenBuildingTheProtectedResourceMetadataThenTheScopeIsListedOnce(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, [static::SCOPE, static::OTHER_SCOPE]);
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => static::RESOURCE_URI, 'scopes' => [static::SCOPE]],
        ])]);

        // Act
        $metadata = $this->tester->getService()->buildProtectedResourceMetadata()->toArray(true, false);

        // Assert
        $this->assertSame([static::SCOPE, static::OTHER_SCOPE], $metadata['scopes_supported']);
    }

    public function testGivenTheConfiguredEndpointsWhenBuildingTheAuthorizationServerMetadataThenItAdvertisesTheCodeFlowWithS256ForPublicClients(): void
    {
        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertSame(static::ISSUER, $metadata['issuer']);
        $this->assertSame(static::AUTHORIZATION_ENDPOINT, $metadata['authorization_endpoint']);
        $this->assertSame(static::TOKEN_ENDPOINT, $metadata['token_endpoint']);
        $this->assertSame(['code'], $metadata['response_types_supported']);
        $this->assertSame(['authorization_code', 'refresh_token'], $metadata['grant_types_supported']);
        $this->assertSame(['S256'], $metadata['code_challenge_methods_supported']);
        $this->assertSame(['none'], $metadata['token_endpoint_auth_methods_supported']);
        $this->assertSame([static::SCOPE], $metadata['scopes_supported']);
        $this->assertTrue($metadata['authorization_response_iss_parameter_supported']);
    }

    public function testGivenResourcesWithOverlappingScopesAndAConfiguredScopeWhenBuildingTheAuthorizationServerMetadataThenItListsTheUnionInOrderWithoutDuplicates(): void
    {
        // Arrange
        $this->tester->setProtectedResourceProviderPlugins([$this->tester->createProtectedResourceProviderPlugin([
            ['name' => 'first', 'pathPrefix' => '/first', 'resourceUri' => 'https://glue.example/first', 'scopes' => ['catalog:read', 'cart:read']],
            ['name' => 'second', 'pathPrefix' => '/second', 'resourceUri' => 'https://glue.example/second', 'scopes' => ['cart:read', 'cart:write']],
        ])]);

        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertSame(['catalog:read', 'cart:read', 'cart:write', static::SCOPE], $metadata['scopes_supported']);
    }

    public function testGivenTheConfiguredRevocationEndpointWhenBuildingTheAuthorizationServerMetadataThenItAdvertisesRevocationForPublicClients(): void
    {
        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertSame(static::REVOCATION_ENDPOINT, $metadata['revocation_endpoint']);
        $this->assertSame(['none'], $metadata['revocation_endpoint_auth_methods_supported']);
    }

    public function testGivenBothRegistrationPathsDisabledWhenBuildingTheAuthorizationServerMetadataThenNeitherIsAdvertised(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, false);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, false);

        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertNull($metadata['client_id_metadata_document_supported']);
        $this->assertNull($metadata['registration_endpoint']);
    }

    public function testGivenBothRegistrationPathsEnabledWhenBuildingTheAuthorizationServerMetadataThenBothAreAdvertised(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);

        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertTrue($metadata['client_id_metadata_document_supported']);
        $this->assertSame(static::REGISTRATION_ENDPOINT, $metadata['registration_endpoint']);
    }

    public function testGivenOnlyClientIdMetadataDocumentsEnabledWhenBuildingTheAuthorizationServerMetadataThenNoRegistrationEndpointIsAdvertised(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, false);

        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertTrue($metadata['client_id_metadata_document_supported']);
        $this->assertNull($metadata['registration_endpoint']);
    }

    public function testGivenOnlyDynamicClientRegistrationEnabledWhenBuildingTheAuthorizationServerMetadataThenOnlyTheRegistrationEndpointIsAdvertised(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, false);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);

        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertNull($metadata['client_id_metadata_document_supported']);
        $this->assertSame(static::REGISTRATION_ENDPOINT, $metadata['registration_endpoint']);
    }

    public function testGivenDynamicClientRegistrationEnabledWithoutAConfiguredIssuerWhenBuildingTheAuthorizationServerMetadataThenNoRegistrationEndpointIsAdvertised(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertNull($metadata['registration_endpoint']);
    }

    public function testGivenAnyConfigurationWhenBuildingTheAuthorizationServerMetadataThenPublicClientsAndTheIssuerParameterAreAdvertised(): void
    {
        // Act
        $metadata = $this->tester->getService()->buildAuthorizationServerMetadata()->toArray(true, false);

        // Assert
        $this->assertSame(['none'], $metadata['token_endpoint_auth_methods_supported']);
        $this->assertTrue($metadata['authorization_response_iss_parameter_supported']);
    }
}
