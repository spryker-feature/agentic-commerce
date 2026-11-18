<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group AgenticCommerceClient
 * @group ClientMetadataValidatorTest
 * Add your own group annotations below this line
 */
class ClientMetadataValidatorTest extends Unit
{
    protected const string REGISTRATION_TYPE_DOCUMENT = AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT;

    protected const string REGISTRATION_TYPE_DYNAMIC = AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC;

    protected const string SCOPE = 'agentic-commerce:principal-read';

    protected const string SCOPE_GLOSSARY_KEY = 'agentic_commerce.scope.agentic-commerce.principal-read';

    protected const int REDIRECT_URIS_ABOVE_MAXIMUM = 6;

    protected const int CLIENT_NAME_ABOVE_MAXIMUM_LENGTH = 101;

    protected const int URI_MAX_LENGTH = 2048;

    protected const int SOFTWARE_FIELD_ABOVE_MAXIMUM_LENGTH = 256;

    protected AgenticCommerceBusinessTester $tester;

    protected function _before(): void
    {
        $this->tester->setScopeProviderPlugins([
            $this->tester->createScopeProviderPlugin([static::SCOPE => static::SCOPE_GLOSSARY_KEY]),
        ]);
    }

    public function testGivenValidMetadataWhenValidatedThenItIsValidAndUnknownScopesAreDropped(): void
    {
        // Arrange
        $validator = $this->tester->getFactory()->createClientMetadataValidator();

        // Act
        $validationResponseTransfer = $validator->validate($this->createValidMetadata(), null, static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame([static::SCOPE], $validationResponseTransfer->getAcceptedScopes());
        $this->assertSame(['authorization_code', 'refresh_token'], $validationResponseTransfer->getAcceptedGrantTypes());
        $this->assertSame('Example app', $validationResponseTransfer->getClientName());
    }

    /**
     * @dataProvider invalidMetadataDataProvider
     */
    public function testGivenInvalidMetadataWhenValidatedThenTheMatchingErrorCodeIsReturned(callable $mutation, string $expectedErrorCode): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata();
        $mutation($clientMetadataTransfer);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame($expectedErrorCode, $validationResponseTransfer->getErrorCode());
        $this->assertNotEmpty($validationResponseTransfer->getErrorDescription());
    }

    /**
     * @return array<string, array{callable, string}>
     */
    public function invalidMetadataDataProvider(): array
    {
        $invalidRedirectUri = AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI;
        $invalidClientMetadata = AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA;

        return [
            'no redirect uri' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris([]), $invalidRedirectUri],
            'six redirect uris' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(array_map(fn (int $index): string => 'https://client.example/callback' . $index, range(1, static::REDIRECT_URIS_ABOVE_MAXIMUM))), $invalidRedirectUri],
            'fragment' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['https://client.example/callback#x']), $invalidRedirectUri],
            'plain http to a public host' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['http://client.example/callback']), $invalidRedirectUri],
            'plain http to an upper-case loopback host' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['http://LOCALHOST/callback']), $invalidRedirectUri],
            'plain http to an expanded IPv6 loopback address' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['http://[0:0:0:0:0:0:0:1]/callback']), $invalidRedirectUri],
            'plain http to a short IPv4 loopback address' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['http://127.1/callback']), $invalidRedirectUri],
            'wildcard' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['https://*.client.example/callback']), $invalidRedirectUri],
            'redirect uri with user and password' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris(['https://claude.ai:secret@client.example/callback']), $invalidRedirectUri],
            'redirect uri with whitespace' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setRedirectUris([' https://client.example/callback']), $invalidRedirectUri],
            'confidential client' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setTokenEndpointAuthMethod('client_secret_basic'), $invalidClientMetadata],
            'confidential client without none support' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setTokenEndpointAuthMethod('client_secret_basic')->setTokenEndpointAuthMethodsSupported(['client_secret_basic']), $invalidClientMetadata],
            'private key without none support' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setTokenEndpointAuthMethod('private_key_jwt')->setTokenEndpointAuthMethodsSupported(['private_key_jwt']), $invalidClientMetadata],
            'implicit grant in a registration' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setGrantTypes(['implicit']), $invalidClientMetadata],
            'extra grant in a registration' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setGrantTypes(['authorization_code', 'client_credentials']), $invalidClientMetadata],
            'token response type' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setResponseTypes(['token']), $invalidClientMetadata],
            'name too long' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName(str_repeat('a', static::CLIENT_NAME_ABOVE_MAXIMUM_LENGTH)), $invalidClientMetadata],
            'whitespace-only name' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName(" \t "), $invalidClientMetadata],
            'name of no-break and ideographic spaces' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName("\u{A0}\u{3000}"), $invalidClientMetadata],
            'name with a control character' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName("Vendor\napp"), $invalidClientMetadata],
            'name with a right-to-left override' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName("Vendor \u{202E}ppa"), $invalidClientMetadata],
            'name with a left-to-right embedding' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName("\u{202A}Vendor app"), $invalidClientMetadata],
            'name with a first strong isolate' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName("Vendor \u{2068}app\u{2069}"), $invalidClientMetadata],
            'name with a left-to-right isolate' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientName("\u{2066}Vendor app"), $invalidClientMetadata],
            'http logo' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setLogoUri('http://client.example/logo.png'), $invalidClientMetadata],
            'logo with user' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setLogoUri('https://trusted-brand.example@client.example/logo.png'), $invalidClientMetadata],
            'client uri too long' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setClientUri('https://client.example/' . str_repeat('a', static::URI_MAX_LENGTH)), $invalidClientMetadata],
            'software id too long' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setSoftwareId(str_repeat('a', static::SOFTWARE_FIELD_ABOVE_MAXIMUM_LENGTH)), $invalidClientMetadata],
            'software version too long' => [fn (AgenticCommerceClientMetadataTransfer $metadata) => $metadata->setSoftwareVersion(str_repeat('a', static::SOFTWARE_FIELD_ABOVE_MAXIMUM_LENGTH)), $invalidClientMetadata],
        ];
    }

    public function testGivenPrivateKeyAuthenticationWithNoneAlsoSupportedWhenValidatedThenTheClientIsAcceptedAsPublic(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()
            ->setTokenEndpointAuthMethod('private_key_jwt')
            ->setTokenEndpointAuthMethodsSupported(['none', 'private_key_jwt']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DOCUMENT);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
    }

    public function testGivenNoTokenEndpointAuthenticationMethodWhenValidatedThenTheClientIsAcceptedAsPublic(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setTokenEndpointAuthMethod(null);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
    }

    public function testGivenADocumentWithAnUnsupportedExtraGrantTypeWhenValidatedThenTheGrantTypeIsDroppedAndTheDocumentIsValid(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setGrantTypes(['authorization_code', 'refresh_token', 'urn:ietf:params:oauth:grant-type:jwt-bearer']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DOCUMENT);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame(['authorization_code', 'refresh_token'], $validationResponseTransfer->getAcceptedGrantTypes());
    }

    public function testGivenADocumentWithoutTheAuthorizationCodeGrantWhenValidatedThenItIsInvalid(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setGrantTypes(['refresh_token', 'client_credentials']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DOCUMENT);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $validationResponseTransfer->getErrorCode());
    }

    public function testGivenARegistrationWithOnlyTheAuthorizationCodeGrantWhenValidatedThenOnlyThatGrantIsAccepted(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setGrantTypes(['authorization_code']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame(['authorization_code'], $validationResponseTransfer->getAcceptedGrantTypes());
    }

    /**
     * @dataProvider acceptedClientNameDataProvider
     */
    public function testGivenANameInAnyScriptWithoutControlOrOverrideCharactersWhenValidatedThenTheNameIsKept(string $clientName): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setClientName($clientName);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame($clientName, $validationResponseTransfer->getClientName());
    }

    /**
     * @return array<string, array{string}>
     */
    public function acceptedClientNameDataProvider(): array
    {
        return [
            'right-to-left script' => ["\u{645}\u{62A}\u{62C}\u{631}"],
            'emoji with a zero width joiner' => ["\u{1F469}\u{200D}\u{1F4BB} Vendor app"],
            'surrounding spaces' => [' Vendor app '],
        ];
    }

    /**
     * @dataProvider missingClientNameDataProvider
     */
    public function testGivenNoClientNameWhenValidatedThenTheHostIsUsedAsTheName(
        ?string $clientName,
        string $registrationType,
        string $expectedClientName
    ): void {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setClientName($clientName)->setRedirectUris(['https://Callback.example/cb']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, null, $registrationType);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame($expectedClientName, $validationResponseTransfer->getClientName());
    }

    /**
     * @return array<string, array{string|null, string, string}>
     */
    public function missingClientNameDataProvider(): array
    {
        return [
            'metadata document uses the client id host' => [null, static::REGISTRATION_TYPE_DOCUMENT, 'client.example'],
            'dynamic registration uses the redirect host' => [null, static::REGISTRATION_TYPE_DYNAMIC, 'callback.example'],
            'an empty name counts as missing' => ['', static::REGISTRATION_TYPE_DYNAMIC, 'callback.example'],
        ];
    }

    public function testGivenAnAllowlistWhenARedirectHostOnlyEndsWithAnAllowedHostThenTheRedirectUriIsRefused(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setRedirectUris(['https://claude.ai.evil.example/callback']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, ['claude.ai'], static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $validationResponseTransfer->getErrorCode());
    }

    public function testGivenAnAllowlistWhenOneOfSeveralRedirectHostsIsNotAllowedThenTheRedirectUrisAreRefused(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setRedirectUris(['https://claude.ai/callback', 'https://evil.example/callback']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, ['claude.ai'], static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $validationResponseTransfer->getErrorCode());
    }

    public function testGivenAnAllowlistWhenTheRedirectHostDiffersOnlyInLetterCaseThenTheRedirectUriIsAccepted(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setRedirectUris(['https://Claude.AI/api/mcp/auth_callback']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, ['claude.ai'], static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
    }

    /**
     * @dataProvider loopbackRedirectUriDataProvider
     */
    public function testGivenAnAllowlistWhenTheRedirectUriIsLoopbackThenItIsAccepted(string $loopbackRedirectUri): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setRedirectUris([$loopbackRedirectUri]);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, ['claude.ai'], static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
    }

    /**
     * @return array<string, list<string>>
     */
    public function loopbackRedirectUriDataProvider(): array
    {
        return [
            'ipv4 with port' => ['http://127.0.0.1:53682/callback'],
            'localhost without port (Claude Code)' => ['http://localhost/callback'],
            'localhost with port (Gemini CLI shape)' => ['http://localhost:7777/oauth/callback'],
            'ipv6' => ['http://[::1]:8080/callback'],
        ];
    }

    public function testGivenAnAllowlistWhenTheRedirectUriIsHttpsToALoopbackHostThenItIsRefused(): void
    {
        // Arrange
        $clientMetadataTransfer = $this->createValidMetadata()->setRedirectUris(['https://localhost/callback']);

        // Act
        $validationResponseTransfer = $this->tester->getFactory()->createClientMetadataValidator()->validate($clientMetadataTransfer, ['claude.ai'], static::REGISTRATION_TYPE_DYNAMIC);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $validationResponseTransfer->getErrorCode());
    }

    protected function createValidMetadata(): AgenticCommerceClientMetadataTransfer
    {
        return (new AgenticCommerceClientMetadataTransfer())
            ->setClientId('https://client.example/oauth/client.json')
            ->setClientName('Example app')
            ->setRedirectUris(['https://client.example/callback'])
            ->setTokenEndpointAuthMethod('none')
            ->setScope(static::SCOPE . ' unknown:scope');
    }
}
