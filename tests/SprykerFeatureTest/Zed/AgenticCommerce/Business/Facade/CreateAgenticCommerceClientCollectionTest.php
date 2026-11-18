<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
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
 * @group CreateAgenticCommerceClientCollectionTest
 * Add your own group annotations below this line
 */
class CreateAgenticCommerceClientCollectionTest extends Unit
{
    protected const string CLIENT_URI = 'https://client.example/about';

    protected const string LOGO_URI = 'https://client.example/logo.png';

    protected const int URI_MAX_LENGTH = 2048;

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setConfig(AgenticCommerceConstants::SUPPORTED_SCOPES, [AgenticCommerceHelper::SCOPE]);
    }

    public function testGivenAClientWithANameAnHttpsRedirectUriAndASupportedScopeWhenCreatingThenAPublicOauthClientAndItsDelegatedAccessClientArePersisted(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->buildClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
        $this->assertNotNull($responseTransfer->getAgenticCommerceClients()->offsetGet(0)->getIdAgenticCommerceClient());

        $oauthClientEntity = SpyOauthClientQuery::create()->findOneByIdentifier($agenticCommerceClientTransfer->getClientIdentifier());
        $this->assertNotNull($oauthClientEntity);
        $this->assertFalse((bool)$oauthClientEntity->getIsConfidential());

        $agenticCommerceClientEntity = SpyAgenticCommerceClientQuery::create()->findOneByFkOauthClient($agenticCommerceClientTransfer->getClientIdentifier());
        $this->assertNotNull($agenticCommerceClientEntity);
        $this->assertSame(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED, $agenticCommerceClientEntity->getRegistrationType());
        $this->assertSame(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED, $agenticCommerceClientEntity->getTrustLevel());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $agenticCommerceClientEntity->getStatus());
    }

    public function testGivenLoopbackHttpRedirectUriWhenCreatingThenClientIsAccepted(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->buildClient()->setRedirectUris(['http://127.0.0.1:33418/callback', 'http://localhost/callback']);

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
    }

    /**
     * @dataProvider provideInvalidClients
     *
     * @param array<string, mixed> $override
     */
    public function testGivenAClientWithAMissingOrMalformedFieldWhenCreatingThenTheMatchingValidationErrorIsReturnedForItsIndexAndNoClientIsReturned(
        array $override,
        string $expectedError
    ): void {
        // Arrange
        $agenticCommerceClientTransfer = $this->buildClient()->fromArray($override, true);

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $errorMessages = array_map(fn ($errorTransfer): string => $errorTransfer->getMessage(), $responseTransfer->getErrors()->getArrayCopy());
        $this->assertContains($expectedError, $errorMessages);
        $this->assertSame('0', $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertCount(0, $responseTransfer->getAgenticCommerceClients());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidClients(): array
    {
        return [
            'missing client identifier' => [[AgenticCommerceClientTransfer::CLIENT_IDENTIFIER => ''], 'agentic_commerce.client.validation.client_identifier_required'],
            'missing name' => [[AgenticCommerceClientTransfer::NAME => ''], 'agentic_commerce.client.validation.name_required'],
            'no redirect uri' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => []], 'agentic_commerce.client.validation.redirect_uris_required'],
            'plain http to a public host' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['http://client.example/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with fragment' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://client.example/callback#x']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'relative redirect uri' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'custom scheme redirect uri' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['client://callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with user' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://shop.example@client.example/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with user and password' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://shop:secret@client.example/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with uppercase scheme' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['HTTPS://client.example/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with leading whitespace' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => [' https://client.example/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with trailing whitespace' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ["https://client.example/callback\n"]], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri without host' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['https:/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'empty redirect uri' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with a wildcard host' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://*.client.example/callback']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'redirect uri with a wildcard path' => [[AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://client.example/*']], 'agentic_commerce.client.validation.redirect_uri_invalid'],
            'no scope' => [[AgenticCommerceClientTransfer::SCOPE_CEILING => []], 'agentic_commerce.client.validation.scope_ceiling_required'],
            'unsupported scope' => [[AgenticCommerceClientTransfer::SCOPE_CEILING => ['checkout']], 'agentic_commerce.client.validation.scope_unsupported'],
            'script client uri' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'javascript:alert(1)'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'plain http client uri' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'http://client.example'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'client uri with fragment' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'https://client.example/#x'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'client uri too long' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'https://client.example/' . str_repeat('a', static::URI_MAX_LENGTH)], 'agentic_commerce.client.validation.client_uri_invalid'],
            'script logo uri' => [[AgenticCommerceClientTransfer::LOGO_URI => 'javascript:alert(1)'], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'plain http logo uri' => [[AgenticCommerceClientTransfer::LOGO_URI => 'http://client.example/logo.png'], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'client uri with user' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'https://trusted-brand.example@client.example/about'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'client uri with uppercase scheme' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'HTTPS://client.example/about'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'client uri with leading whitespace' => [[AgenticCommerceClientTransfer::CLIENT_URI => ' https://client.example/about'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'client uri with trailing whitespace' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'https://client.example/about '], 'agentic_commerce.client.validation.client_uri_invalid'],
            'client uri without host' => [[AgenticCommerceClientTransfer::CLIENT_URI => 'https:/about'], 'agentic_commerce.client.validation.client_uri_invalid'],
            'empty client uri' => [[AgenticCommerceClientTransfer::CLIENT_URI => ''], 'agentic_commerce.client.validation.client_uri_invalid'],
            'logo uri too long' => [[AgenticCommerceClientTransfer::LOGO_URI => 'https://client.example/' . str_repeat('a', static::URI_MAX_LENGTH)], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'logo uri with user' => [[AgenticCommerceClientTransfer::LOGO_URI => 'https://trusted-brand.example@client.example/logo.png'], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'logo uri with user and password' => [[AgenticCommerceClientTransfer::LOGO_URI => 'https://trusted-brand.example:secret@client.example/logo.png'], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'logo uri with uppercase scheme' => [[AgenticCommerceClientTransfer::LOGO_URI => 'HTTPS://client.example/logo.png'], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'logo uri with leading whitespace' => [[AgenticCommerceClientTransfer::LOGO_URI => "\thttps://client.example/logo.png"], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'logo uri with trailing whitespace' => [[AgenticCommerceClientTransfer::LOGO_URI => 'https://client.example/logo.png '], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'logo uri without host' => [[AgenticCommerceClientTransfer::LOGO_URI => 'https:'], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'empty logo uri' => [[AgenticCommerceClientTransfer::LOGO_URI => ''], 'agentic_commerce.client.validation.logo_uri_invalid'],
            'unknown trust level' => [[AgenticCommerceClientTransfer::TRUST_LEVEL => 'unknown'], 'agentic_commerce.client.validation.trust_level_unsupported'],
        ];
    }

    public function testGivenHttpsClientAndLogoUrisWhenCreatingThenBothArePersisted(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->buildClient()
            ->setClientUri(static::CLIENT_URI)
            ->setLogoUri(static::LOGO_URI);

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $storedClientTransfer = $this->tester->findClient($agenticCommerceClientTransfer->getClientIdentifierOrFail());
        $this->assertSame(static::CLIENT_URI, $storedClientTransfer->getClientUri());
        $this->assertSame(static::LOGO_URI, $storedClientTransfer->getLogoUri());
    }

    public function testGivenAnUnknownTrustLevelWhenCreatingThenATrustLevelUnsupportedErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->buildClient()->setTrustLevel('unknown');

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertSame('agentic_commerce.client.validation.trust_level_unsupported', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertNull(SpyAgenticCommerceClientQuery::create()->findOneByFkOauthClient($agenticCommerceClientTransfer->getClientIdentifierOrFail()));
        $this->assertNull(SpyOauthClientQuery::create()->findOneByIdentifier($agenticCommerceClientTransfer->getClientIdentifierOrFail()));
    }

    public function testGivenTheVerifiedTrustLevelWhenCreatingThenItIsPersisted(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->buildClient()->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED);

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED, $this->tester->findClient($agenticCommerceClientTransfer->getClientIdentifierOrFail())->getTrustLevel());
    }

    public function testGivenExistingClientIdentifierWhenCreatingThenIdentifierIsReportedAsTaken(): void
    {
        // Arrange
        $existingAgenticCommerceClientTransfer = $this->tester->haveAgenticCommerceClient();
        $agenticCommerceClientTransfer = $this->buildClient()->setClientIdentifier($existingAgenticCommerceClientTransfer->getClientIdentifier());

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$agenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertSame('agentic_commerce.client.validation.client_identifier_taken', $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenSameIdentifierTwiceInOneRequestWhenCreatingThenSecondIsReportedAsTaken(): void
    {
        // Arrange
        $firstAgenticCommerceClientTransfer = $this->buildClient();
        $secondAgenticCommerceClientTransfer = $this->buildClient()->setClientIdentifier($firstAgenticCommerceClientTransfer->getClientIdentifier());

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$firstAgenticCommerceClientTransfer, $secondAgenticCommerceClientTransfer], false),
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame('1', $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
    }

    public function testGivenTransactionalRequestWithOneClientWithoutANameWhenCreatingThenNoClientIsPersisted(): void
    {
        // Arrange
        $validAgenticCommerceClientTransfer = $this->buildClient();
        $invalidAgenticCommerceClientTransfer = $this->buildClient()->setName('');

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$validAgenticCommerceClientTransfer, $invalidAgenticCommerceClientTransfer], true),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getAgenticCommerceClients());
        $this->assertNull(SpyAgenticCommerceClientQuery::create()->findOneByFkOauthClient($validAgenticCommerceClientTransfer->getClientIdentifier()));
    }

    public function testGivenNonTransactionalRequestWithOneClientWithoutANameWhenCreatingThenTheNamedClientIsPersisted(): void
    {
        // Arrange
        $validAgenticCommerceClientTransfer = $this->buildClient();
        $invalidAgenticCommerceClientTransfer = $this->buildClient()->setName('');

        // Act
        $responseTransfer = $this->tester->getFacade()->createAgenticCommerceClientCollection(
            $this->buildRequest([$validAgenticCommerceClientTransfer, $invalidAgenticCommerceClientTransfer], false),
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
        $this->assertSame('1', $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertNotNull(SpyAgenticCommerceClientQuery::create()->findOneByFkOauthClient($validAgenticCommerceClientTransfer->getClientIdentifier()));
    }

    protected function buildClient(): AgenticCommerceClientTransfer
    {
        return (new AgenticCommerceClientTransfer())
            ->setClientIdentifier(sprintf('delegated-access-client-%s', uniqid()))
            ->setName(AgenticCommerceHelper::CLIENT_NAME)
            ->setRedirectUris([AgenticCommerceHelper::REDIRECT_URI])
            ->setScopeCeiling([AgenticCommerceHelper::SCOPE]);
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceClientTransfer> $agenticCommerceClientTransfers
     */
    protected function buildRequest(array $agenticCommerceClientTransfers, bool $isTransactional): AgenticCommerceClientCollectionRequestTransfer
    {
        $requestTransfer = (new AgenticCommerceClientCollectionRequestTransfer())->setIsTransactional($isTransactional);

        foreach ($agenticCommerceClientTransfers as $agenticCommerceClientTransfer) {
            $requestTransfer->addAgenticCommerceClient($agenticCommerceClientTransfer);
        }

        return $requestTransfer;
    }
}
