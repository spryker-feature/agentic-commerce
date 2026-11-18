<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig as ZedAgenticCommerceConfig;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group RegisterAgenticCommerceClientTest
 * Add your own group annotations below this line
 */
class RegisterAgenticCommerceClientTest extends Unit
{
    protected const string REDIRECT_HOST = 'claude.ai';

    protected const string REDIRECT_URI = 'https://claude.ai/api/mcp/auth_callback';

    protected const string SOFTWARE_ID = 'claude-connector';

    protected const string SCOPE_CART_READ = 'cart:read';

    /**
     * A random (version 4) UUID in its canonical lower-case form.
     */
    protected const string PATTERN_UUID_VERSION_4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    protected const int CLOCK_TOLERANCE_SECONDS = 60;

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_REDIRECT_HOST_ALLOWLIST, [static::REDIRECT_HOST]);
        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([
            AgenticCommerceHelper::SCOPE => 'agentic_commerce.scope.agentic-commerce.principal-read',
            static::SCOPE_CART_READ => 'agentic_commerce.scope.cart.read',
        ])]);
    }

    /**
     * Keys whose override is `null` are left out of the request body.
     *
     * @param array<string, mixed> $overrides
     */
    protected function createRegistrationRequest(array $overrides = []): AgenticCommerceClientRegistrationRequestTransfer
    {
        $registrationRequest = $overrides + [
            'client_name' => 'Claude',
            'redirect_uris' => [static::REDIRECT_URI],
            'token_endpoint_auth_method' => 'none',
            'scope' => AgenticCommerceHelper::SCOPE,
            'software_id' => static::SOFTWARE_ID,
            'software_version' => '1.0',
        ];

        return (new AgenticCommerceClientRegistrationRequestTransfer())
            ->setRawRequest(json_encode(array_filter($registrationRequest, static fn (mixed $value): bool => $value !== null), JSON_THROW_ON_ERROR));
    }

    public function testGivenAValidRequestWhenRegisteringThenAnUnverifiedDynamicPublicClientWithTheDefaultCeilingIsCreated(): void
    {
        // Arrange
        $registrationRequestTransfer = $this->createRegistrationRequest();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($registrationRequestTransfer);

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
        $clientIdentifier = $registrationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifierOrFail();
        $this->assertMatchesRegularExpression(static::PATTERN_UUID_VERSION_4, $clientIdentifier);
        $this->assertEqualsWithDelta(time(), $registrationResponseTransfer->getClientIdIssuedAtOrFail(), static::CLOCK_TOLERANCE_SECONDS);
        $this->assertSame([AgenticCommerceHelper::SCOPE], $registrationResponseTransfer->getAcceptedScopes());
        $this->assertSame([AgenticCommerceConfig::GRANT_TYPE_AUTHORIZATION_CODE, AgenticCommerceConfig::GRANT_TYPE_REFRESH_TOKEN], $registrationResponseTransfer->getGrantTypes());

        $clientTransfer = $this->tester->findClient($clientIdentifier);
        $this->assertSame(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC, $clientTransfer->getRegistrationType());
        $this->assertSame(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED, $clientTransfer->getTrustLevel());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $clientTransfer->getStatus());
        $this->assertSame([], $clientTransfer->getScopeCeiling());
        $this->assertSame('Claude', $clientTransfer->getName());
        $this->assertSame([static::REDIRECT_URI], $clientTransfer->getRedirectUris());
        $this->assertSame(static::SOFTWARE_ID, $clientTransfer->getSoftwareId());
        $this->assertSame('1.0', $clientTransfer->getSoftwareVersion());
        $this->assertSame($registrationRequestTransfer->getRawRequest(), $clientTransfer->getRegistrationMetadata());

        $oauthClientTransfer = $this->tester->findOauthClient($clientIdentifier);
        $this->assertFalse($oauthClientTransfer->getIsConfidential());
        $this->assertSame(static::REDIRECT_URI, $oauthClientTransfer->getRedirectUri());
    }

    public function testGivenAllowlistModeWhenARedirectHostOnlyStartsWithAnAllowedHostThenRegistrationFailsWithInvalidRedirectUri(): void
    {
        // Arrange
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['redirect_uris' => ['https://claude.ai.evil.example/cb']]),
        );

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    public function testGivenAllowlistModeWhenTheAllowlistNamesTheHostInUpperCaseThenRegistrationSucceeds(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationRedirectHostAllowlist', ['', 'CLAUDE.AI']);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($this->createRegistrationRequest());

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    public function testGivenASoftwareIdBlockWhenTheSameSoftwareIdRegistersAgainThenRegistrationFailsWithInvalidClientMetadataAndNothingIsCreated(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);
        $this->tester->blockClient($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId);
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['software_id' => $softwareId]),
        );

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $registrationResponseTransfer->getErrorCode());
        $this->assertStringNotContainsString(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, (string)$registrationResponseTransfer->getErrorDescription());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @dataProvider refusedClientNameDataProvider
     */
    public function testGivenARefusedClientNameWhenRegisteringThenRegistrationFailsWithInvalidClientMetadataAndNothingIsCreated(string $clientName): void
    {
        // Arrange
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['client_name' => $clientName]),
        );

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @return array<string, array{string}>
     */
    public function refusedClientNameDataProvider(): array
    {
        return [
            'whitespace only' => ['   '],
            'right-to-left override' => ["Claude \u{202E}edualC"],
        ];
    }

    public function testGivenAnActiveClientOfASoftwareIdWhenTheSameSoftwareIdRegistersAgainThenRegistrationSucceeds(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['software_id' => $softwareId]),
        );

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    /**
     * @dataProvider blockedRedirectHostDataProvider
     */
    public function testGivenARedirectHostBlockWhenAClientOfTheSameHostRegistersThenRegistrationFailsWithInvalidRedirectUriAndNothingIsCreated(
        string $storedHostPrefix,
        string $requestedHostPrefix
    ): void {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN);
        $host = sprintf('%s.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s%s/callback', $storedHostPrefix, $host)],
        ]);
        $this->tester->blockClient($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . sprintf('evil.%s', $host));
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest([
                'redirect_uris' => ['https://tunnel.example/callback', sprintf('https://%s%s/other-callback', $requestedHostPrefix, $host)],
                'software_id' => null,
            ]),
        );

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertStringNotContainsString(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, (string)$registrationResponseTransfer->getErrorDescription());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function blockedRedirectHostDataProvider(): array
    {
        return [
            'same case' => ['evil.', 'evil.'],
            'stored in mixed case' => ['Evil.', 'evil.'],
            'requested in mixed case' => ['evil.', 'EVIL.'],
        ];
    }

    public function testGivenALoopbackRedirectHostBlockWhenAnotherLoopbackClientRegistersThenRegistrationSucceeds(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => ['http://127.0.0.1:8765/callback'],
        ]);
        $this->tester->blockClient($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . '127.0.0.1');

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['redirect_uris' => ['http://127.0.0.1:53682/callback'], 'software_id' => null]),
        );

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    public function testGivenAClientBlockedOnItsOwnWhenAClientOfItsSoftwareIdAndHostRegistersThenRegistrationSucceeds(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN);
        $softwareId = uniqid('software-');
        $redirectUri = sprintf('https://%s.example/callback', uniqid('shared-'));
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId,
            AgenticCommerceClientTransfer::REDIRECT_URIS => [$redirectUri],
        ]);
        $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['redirect_uris' => [$redirectUri], 'software_id' => $softwareId]),
        );

        // Assert
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    public function testGivenASoftwareIdBlockWhenUnblockedThenTheSameSoftwareIdRegistersAgain(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);
        $this->tester->blockClient($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId);
        $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_ACTIVE));

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['software_id' => $softwareId]),
        );

        // Assert
        $this->assertNull($this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy());
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    public function testGivenOpenModeWhenARedirectHostIsNotOnTheAllowlistThenRegistrationSucceeds(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['redirect_uris' => ['https://tunnel.example/callback']]),
        );

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    /**
     * @dataProvider dynamicClientRegistrationModeDataProvider
     */
    public function testGivenEitherModeWhenAGeminiCliStyleLoopbackClientRegistersThenRegistrationSucceeds(string $mode): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', $mode);
        $registrationRequestTransfer = $this->createRegistrationRequest([
            'client_name' => 'Gemini CLI MCP Client',
            'redirect_uris' => ['http://localhost:7777/oauth/callback'],
            'grant_types' => [AgenticCommerceConfig::GRANT_TYPE_AUTHORIZATION_CODE, AgenticCommerceConfig::GRANT_TYPE_REFRESH_TOKEN],
            'response_types' => ['code'],
            'software_id' => null,
            'software_version' => null,
        ]);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($registrationRequestTransfer);

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
        $clientTransfer = $this->tester->findClient($registrationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifierOrFail());
        $this->assertSame('Gemini CLI MCP Client', $clientTransfer->getName());
        $this->assertNull($clientTransfer->getSoftwareId());
    }

    /**
     * @return array<string, list<string>>
     */
    public function dynamicClientRegistrationModeDataProvider(): array
    {
        return [
            'allowlist' => [AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_ALLOWLIST],
            'open' => [AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN],
        ];
    }

    public function testGivenTheShopListsTheSoftwareIdInTheCeilingMapWhenRegisteringThenTheClientGetsTheMappedCeiling(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationScopeCeilingMap', [static::SOFTWARE_ID => [static::SCOPE_CART_READ]]);
        $this->tester->mockConfigMethod('getDynamicClientRegistrationDefaultScopeCeiling', [AgenticCommerceHelper::SCOPE]);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($this->createRegistrationRequest());

        // Assert
        $clientIdentifier = $registrationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifierOrFail();
        $this->assertSame([static::SCOPE_CART_READ], $this->tester->findClient($clientIdentifier)->getScopeCeiling());
    }

    public function testGivenNoSoftwareIdWhenRegisteringThenTheClientGetsTheDefaultCeilingAndIsNamedAfterTheRedirectHostIfUnnamed(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationScopeCeilingMap', [static::SOFTWARE_ID => [static::SCOPE_CART_READ]]);
        $this->tester->mockConfigMethod('getDynamicClientRegistrationDefaultScopeCeiling', [AgenticCommerceHelper::SCOPE]);
        $registrationRequestTransfer = $this->createRegistrationRequest(['software_id' => null, 'client_name' => null]);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($registrationRequestTransfer);

        // Assert
        $clientTransfer = $this->tester->findClient($registrationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifierOrFail());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $clientTransfer->getScopeCeiling());
        $this->assertSame(static::REDIRECT_HOST, $clientTransfer->getName());
    }

    public function testGivenEmptyClientAndLogoUrisWhenRegisteringThenRegistrationSucceedsWithoutThem(): void
    {
        // Arrange
        $registrationRequestTransfer = $this->createRegistrationRequest(['client_uri' => '', 'logo_uri' => '']);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($registrationRequestTransfer);

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
        $clientTransfer = $this->tester->findClient($registrationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifierOrFail());
        $this->assertNull($clientTransfer->getClientUri());
        $this->assertNull($clientTransfer->getLogoUri());
    }

    public function testGivenARedirectUriListWithANonStringWhenRegisteringThenTheListIsTreatedAsMissingAndRegistrationFailsWithInvalidRedirectUri(): void
    {
        // Arrange
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            $this->createRegistrationRequest(['redirect_uris' => [static::REDIRECT_URI, 1]]),
        );

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @dataProvider requestThatIsNotAJsonObjectDataProvider
     */
    public function testGivenARequestThatIsNotAJsonObjectWhenRegisteringThenRegistrationFailsWithInvalidClientMetadata(string $rawRequest): void
    {
        // Arrange
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient(
            (new AgenticCommerceClientRegistrationRequestTransfer())->setRawRequest($rawRequest),
        );

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @return array<string, list<string>>
     */
    public function requestThatIsNotAJsonObjectDataProvider(): array
    {
        return [
            'list' => ['["a"]'],
            'empty list' => ['[]'],
            'string' => ['"client"'],
            'malformed' => ['{"client_name":'],
            'empty' => [''],
        ];
    }

    public function testGivenRegistrationIsDisabledWhenRegisteringThenNothingIsCreated(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, false);
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($this->createRegistrationRequest());

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    public function testGivenAValidRequestPaddedBeyondTheClientMetadataLimitWhenRegisteringThenRegistrationFailsWithInvalidClientMetadataAndNothingIsCreated(): void
    {
        // Arrange
        $clientCountBefore = $this->tester->countClients();
        $registrationRequestTransfer = $this->createRegistrationRequestOfSize((new ZedAgenticCommerceConfig())->getDynamicClientRegistrationRequestMaxBytes() + 1);

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($registrationRequestTransfer);

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    public function testGivenAValidRequestOfExactlyTheClientMetadataLimitWhenRegisteringThenRegistrationSucceeds(): void
    {
        // Arrange
        $registrationRequestTransfer = $this->createRegistrationRequestOfSize((new ZedAgenticCommerceConfig())->getDynamicClientRegistrationRequestMaxBytes());

        // Act
        $registrationResponseTransfer = $this->tester->getFacade()->registerAgenticCommerceClient($registrationRequestTransfer);

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    /**
     * Pads a valid request with a field the registration ignores until its body is `$sizeInBytes` long.
     */
    protected function createRegistrationRequestOfSize(int $sizeInBytes): AgenticCommerceClientRegistrationRequestTransfer
    {
        $rawRequestWithoutPadding = (string)$this->createRegistrationRequest(['padding' => ''])->getRawRequest();

        return $this->createRegistrationRequest(['padding' => str_repeat('a', $sizeInBytes - strlen($rawRequestWithoutPadding))]);
    }

    protected function createAgenticCommerceClientCollectionRequestTransfer(
        string $clientIdentifier,
        string $status
    ): AgenticCommerceClientCollectionRequestTransfer {
        return (new AgenticCommerceClientCollectionRequestTransfer())
            ->setIsTransactional(true)
            ->addAgenticCommerceClient((new AgenticCommerceClientTransfer())->setClientIdentifier($clientIdentifier)->setStatus($status));
    }
}
