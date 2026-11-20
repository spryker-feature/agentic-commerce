<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakeAuthorizationDetailsAwareConstraintPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group ValidateAgenticCommerceAuthorizationRequestTest
 * Add your own group annotations below this line
 */
class ValidateAgenticCommerceAuthorizationRequestTest extends Unit
{
    protected const string ERROR_INVALID_CLIENT = 'invalid_client';

    protected const string ERROR_INVALID_REQUEST = 'invalid_request';

    protected const string ERROR_UNSUPPORTED_RESPONSE_TYPE = 'unsupported_response_type';

    protected const string ERROR_INVALID_TARGET = 'invalid_target';

    protected const string ERROR_INVALID_SCOPE = 'invalid_scope';

    protected const string SCOPE_CATALOG_READ = 'catalog:read';

    protected const string SCOPE_CART_READ = 'cart:read';

    protected const string SCOPE_CART_WRITE = 'cart:write';

    protected const string REGISTERED_REDIRECT_URI = 'https://client.example/callback';

    protected const string CLIENT_URI = 'https://client.example/about';

    protected const string LOGO_URI = 'https://client.example/logo.png';

    protected const string ERROR_DESCRIPTION_TOO_LARGE = 'The authorization_details parameter is too large.';

    protected const string ERROR_DESCRIPTION_NOT_JSON = 'The authorization_details parameter is not valid JSON.';

    protected const string ERROR_DESCRIPTION_NOT_A_LIST = 'The authorization_details parameter must be a JSON array of objects.';

    protected const string ERROR_DESCRIPTION_TOO_MANY_OBJECTS = 'The authorization_details parameter has too many objects.';

    protected const string ERROR_DESCRIPTION_OBJECT_WITHOUT_TYPE = 'Every authorization_details object must be a JSON object with a string "type".';

    protected const string ERROR_DESCRIPTION_UNKNOWN_TYPE = 'An authorization_details object has an unknown type.';

    protected const string ERROR_DESCRIPTION_DUPLICATE_TYPE = 'The authorization_details parameter has two objects of one type.';

    protected const string ERROR_DESCRIPTION_INVALID_VALUE = 'An authorization_details object has an invalid value.';

    protected const string ERROR_LIMIT_INVALID = 'agentic_commerce.consent.validation.limit_invalid';

    protected const string UNKNOWN_AUTHORIZATION_DETAILS_TYPE = 'https://unknown.example/authorization-details/limit';

    protected const string PADDING_KEY = 'padding';

    protected const int REQUESTED_LIMIT = 500;

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setSupportedScopes([AgenticCommerceHelper::SCOPE]);
        $this->tester->declarePrincipalResource();
    }

    public function testGivenARequestOfAnActiveClientForItsRegisteredRedirectUriAndASupportedScopeWhenValidatingThenTheRequestIsValidWithTheClientAndTheGrantableScopes(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope(AgenticCommerceHelper::SCOPE);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getIsRedirectable());
        $this->assertNull($authorizationResponseTransfer->getErrorCode());
        $this->assertSame($clientTransfer->getClientIdentifierOrFail(), $authorizationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifier());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $authorizationResponseTransfer->getGrantableScopes());
        $this->assertFalse($authorizationResponseTransfer->getAuthorizationRequestOrFail()->getIsLoopbackRedirect());
    }

    public function testGivenAClientWithAWebsiteAndALogoWhenValidatingThenTheClientOfTheResponseCarriesBothForTheConsentPage(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::CLIENT_URI => static::CLIENT_URI,
            AgenticCommerceClientTransfer::LOGO_URI => static::LOGO_URI,
        ]);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest(
            $this->tester->createValidAuthorizationRequest($clientTransfer),
        );

        // Assert
        $this->assertSame(static::CLIENT_URI, $authorizationResponseTransfer->getAgenticCommerceClientOrFail()->getClientUri());
        $this->assertSame(static::LOGO_URI, $authorizationResponseTransfer->getAgenticCommerceClientOrFail()->getLogoUri());
    }

    public function testGivenAnUnknownClientWhenValidatingThenInvalidClientIsReturnedWithoutRedirect(): void
    {
        // Arrange
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest(
            (new AgenticCommerceClientTransfer())->setClientIdentifier('unknown-client'),
        );

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertFalse($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
    }

    public function testGivenAClientIdentifierThatDiffersFromAStoredOneOnlyInLetterCaseWhenValidatingThenInvalidClientIsReturnedWithoutRedirect(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)
            ->setClientId(strtoupper($clientTransfer->getClientIdentifierOrFail()));

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertFalse($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertNull($authorizationResponseTransfer->getFailureReason());
    }

    public function testGivenABlockedClientWhenValidatingThenInvalidClientIsReturnedWithoutRedirectAndWithoutTheClient(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_BLOCKED]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertFalse($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertNull($authorizationResponseTransfer->getAgenticCommerceClient());
    }

    public function testGivenAnUnregisteredRedirectUriWhenValidatingThenInvalidRequestIsReturnedWithoutRedirect(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setRedirectUri('https://client.example/callback/other');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertFalse($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_REQUEST, $authorizationResponseTransfer->getErrorCode());
    }

    /**
     * @dataProvider nearlyMatchingRedirectUriDataProvider
     */
    public function testGivenARedirectUriThatOnlyNearlyMatchesTheRegisteredOneWhenValidatingThenInvalidRequestIsReturnedWithoutRedirect(
        string $redirectUri
    ): void {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [static::REGISTERED_REDIRECT_URI]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setRedirectUri($redirectUri);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertFalse($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_REQUEST, $authorizationResponseTransfer->getErrorCode());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function nearlyMatchingRedirectUriDataProvider(): array
    {
        return [
            'trailing slash' => ['https://client.example/callback/'],
            'query string' => ['https://client.example/callback?x=1'],
            'other case in host' => ['https://CLIENT.example/callback'],
            'http instead of https' => ['http://client.example/callback'],
            'fragment' => ['https://client.example/callback#a'],
            'other port on a non-loopback host' => ['https://client.example:8443/callback'],
        ];
    }

    /**
     * @dataProvider loopbackRedirectUriDataProvider
     */
    public function testGivenARegisteredLoopbackRedirectUriWhenTheRequestedRedirectUriDiffersFromItThenTheRequestIsValidAndRedirectableOnlyIfJustThePortDiffers(
        string $redirectUri,
        bool $isExpectedToBeValid
    ): void {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => ['http://localhost/callback', 'http://127.0.0.1/callback', 'http://[::1]:8080/callback']]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setRedirectUri($redirectUri);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertSame($isExpectedToBeValid, $authorizationResponseTransfer->getIsValid());
        $this->assertSame($isExpectedToBeValid, $authorizationResponseTransfer->getIsRedirectable());
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function loopbackRedirectUriDataProvider(): array
    {
        return [
            'localhost with an ephemeral port' => ['http://localhost:53682/callback', true],
            'IPv4 loopback with an ephemeral port' => ['http://127.0.0.1:53682/callback', true],
            'IPv6 loopback with another port than registered' => ['http://[::1]:53682/callback', true],
            'IPv6 loopback without a port' => ['http://[::1]/callback', true],
            'localhost without a port' => ['http://localhost/callback', true],
            'other path' => ['http://localhost:53682/other', false],
            'trailing slash' => ['http://localhost:53682/callback/', false],
            'https instead of http' => ['https://localhost:53682/callback', false],
            'empty query string added' => ['http://localhost:53682/callback?', false],
            'host that only starts like a loopback host' => ['http://127.0.0.1.attacker.example:53682/callback', false],
            'credentials before the loopback host' => ['http://localhost:53682@attacker.example/callback', false],
            'port without digits' => ['http://localhost:/callback', false],
            'port beyond the highest port number' => ['http://localhost:65536/callback', false],
            'query string added' => ['http://localhost:53682/callback?x=1', false],
            'fragment added' => ['http://localhost:53682/callback#a', false],
            'upper-case host' => ['http://LOCALHOST:53682/callback', false],
        ];
    }

    public function testGivenARegisteredLoopbackRedirectUriWhenValidatingThenTheRequestIsMarkedAsALoopbackRedirect(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => ['http://127.0.0.1/callback']]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setRedirectUri('http://127.0.0.1:53682/callback');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getAuthorizationRequestOrFail()->getIsLoopbackRedirect());
        $this->assertSame('http://127.0.0.1:53682/callback', $authorizationResponseTransfer->getAuthorizationRequestOrFail()->getRedirectUri());
    }

    public function testGivenNoScopeParameterWhenValidatingThenTheGrantableScopesAreTheCeilingWithinTheSupportedScopes(): void
    {
        // Arrange
        $this->tester->setSupportedScopes([static::SCOPE_CATALOG_READ, static::SCOPE_CART_READ]);
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CATALOG_READ, static::SCOPE_CART_WRITE]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope(null);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame([static::SCOPE_CATALOG_READ], $authorizationResponseTransfer->getGrantableScopes());
    }

    public function testGivenAnEmptyScopeParameterWhenValidatingThenTheGrantableScopesAreTheCeilingWithinTheSupportedScopesInCeilingOrder(): void
    {
        // Arrange
        $this->tester->setSupportedScopes([static::SCOPE_CATALOG_READ, static::SCOPE_CART_READ, static::SCOPE_CART_WRITE]);
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CART_READ, static::SCOPE_CATALOG_READ]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope(' ');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame([static::SCOPE_CART_READ, static::SCOPE_CATALOG_READ], $authorizationResponseTransfer->getGrantableScopes());
    }

    public function testGivenAScopeOutsideTheCeilingWhenValidatingThenItIsDroppedAndTheRequestStaysValid(): void
    {
        // Arrange
        $this->tester->setSupportedScopes([static::SCOPE_CATALOG_READ, static::SCOPE_CART_WRITE]);
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CATALOG_READ]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope('catalog:read cart:write unknown:scope catalog:read');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame([static::SCOPE_CATALOG_READ], $authorizationResponseTransfer->getGrantableScopes());
    }

    public function testGivenAScopeInTheCeilingThatIsNoLongerSupportedWhenValidatingThenItIsDropped(): void
    {
        // Arrange
        $this->tester->setSupportedScopes([static::SCOPE_CART_READ]);
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CATALOG_READ, static::SCOPE_CART_READ]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope('catalog:read cart:read');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame([static::SCOPE_CART_READ], $authorizationResponseTransfer->getGrantableScopes());
    }

    public function testGivenOnlyScopesOutsideTheCeilingWhenValidatingThenTheRequestIsRedirectedWithInvalidScope(): void
    {
        // Arrange
        $this->tester->setSupportedScopes([static::SCOPE_CATALOG_READ, static::SCOPE_CART_WRITE]);
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CATALOG_READ]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope(static::SCOPE_CART_WRITE);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_SCOPE, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame([], $authorizationResponseTransfer->getGrantableScopes());
    }

    /**
     * @dataProvider requestedScopeDataProvider
     */
    public function testGivenAClientWithAnEmptyScopeCeilingWhenValidatingThenTheRequestIsValidWithoutGrantableScopes(?string $requestedScope): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => []]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setScope($requestedScope);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getIsRedirectable());
        $this->assertNull($authorizationResponseTransfer->getErrorCode());
        $this->assertSame([], $authorizationResponseTransfer->getGrantableScopes());
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function requestedScopeDataProvider(): array
    {
        return [
            'no scope requested' => [null],
            'a supported scope requested' => [AgenticCommerceHelper::SCOPE],
        ];
    }

    public function testGivenANonEmptyCeilingWithoutASupportedScopeWhenValidatingWithoutScopeThenTheRequestIsRedirectedWithInvalidScope(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CATALOG_READ]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_INVALID_SCOPE, $authorizationResponseTransfer->getErrorCode());
    }

    public function testGivenTwoDeclaredResourcesWhenTheRequestNamesTheSecondThenTheRequestIsValid(): void
    {
        // Arrange
        $this->tester->declareProtectedResources([
            ['name' => 'principal', 'pathPrefix' => $this->tester->getPrincipalResourcePath(), 'resourceUri' => $this->tester->getModuleConfig()->getResourceUri(), 'scopes' => ['agentic-commerce:principal-read']],
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => 'https://glue.example/mcp', 'scopes' => ['agentic-commerce:principal-read']],
        ]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($this->tester->haveAgenticCommerceClient())->setResource('https://glue.example/mcp');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
    }

    public function testGivenAResourceThatIsNotDeclaredWhenAuthorizingThenTheRequestIsInvalidTarget(): void
    {
        // Arrange
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($this->tester->haveAgenticCommerceClient())->setResource('https://glue.example/undeclared');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_TARGET, $authorizationResponseTransfer->getErrorCode());
    }

    /**
     * @dataProvider clientRegistrationDataProvider
     */
    public function testGivenAClientOfARegistrationTypeAndTrustLevelWhenTheResponseTypeIsUnsupportedThenTheErrorIsRedirectableOnlyForAPreRegisteredOrVerifiedClient(
        string $registrationType,
        string $trustLevel,
        bool $isExpectedToBeRedirectable
    ): void {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REGISTRATION_TYPE => $registrationType,
            AgenticCommerceClientTransfer::TRUST_LEVEL => $trustLevel,
        ]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)->setResponseType('foo');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame($isExpectedToBeRedirectable, $authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(static::ERROR_UNSUPPORTED_RESPONSE_TYPE, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame($clientTransfer->getClientIdentifierOrFail(), $authorizationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifier());
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function clientRegistrationDataProvider(): array
    {
        return [
            'dynamically registered and unverified' => [AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC, AgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED, false],
            'client ID metadata document and domain verified' => [AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT, AgenticCommerceConfig::CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED, false],
            'pre-registered and unverified' => [AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED, AgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED, true],
            'dynamically registered and verified' => [AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC, AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED, true],
        ];
    }

    /**
     * @dataProvider redirectableInvalidRequestDataProvider
     *
     * @param array<string, string|null> $override
     */
    public function testGivenAMissingOrInvalidResponseTypeCodeChallengeResourceOrScopeAfterATrustedRedirectUriWhenValidatingThenTheMatchingErrorCodeIsReturnedWithRedirect(
        array $override,
        string $expectedErrorCode
    ): void {
        // Arrange
        $this->tester->setSupportedScopes([static::SCOPE_CATALOG_READ, static::SCOPE_CART_READ, static::SCOPE_CART_WRITE]);
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [static::SCOPE_CATALOG_READ, static::SCOPE_CART_READ]]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($clientTransfer)
            ->setScope(static::SCOPE_CATALOG_READ)
            ->fromArray($override);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame($expectedErrorCode, $authorizationResponseTransfer->getErrorCode());
    }

    /**
     * @return array<string, array{array<string, string|null>, string}>
     */
    public static function redirectableInvalidRequestDataProvider(): array
    {
        return [
            'implicit flow' => [[AgenticCommerceAuthorizationRequestTransfer::RESPONSE_TYPE => 'token'], static::ERROR_UNSUPPORTED_RESPONSE_TYPE],
            'missing response type' => [[AgenticCommerceAuthorizationRequestTransfer::RESPONSE_TYPE => null], static::ERROR_UNSUPPORTED_RESPONSE_TYPE],
            'plain challenge method' => [[AgenticCommerceAuthorizationRequestTransfer::CODE_CHALLENGE_METHOD => 'plain'], static::ERROR_INVALID_REQUEST],
            'missing challenge method' => [[AgenticCommerceAuthorizationRequestTransfer::CODE_CHALLENGE_METHOD => null], static::ERROR_INVALID_REQUEST],
            'missing challenge' => [[AgenticCommerceAuthorizationRequestTransfer::CODE_CHALLENGE => null], static::ERROR_INVALID_REQUEST],
            'short challenge' => [[AgenticCommerceAuthorizationRequestTransfer::CODE_CHALLENGE => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw'], static::ERROR_INVALID_REQUEST],
            'challenge outside base64url' => [[AgenticCommerceAuthorizationRequestTransfer::CODE_CHALLENGE => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw+cM'], static::ERROR_INVALID_REQUEST],
            'missing resource' => [[AgenticCommerceAuthorizationRequestTransfer::RESOURCE => null], static::ERROR_INVALID_TARGET],
            'foreign resource' => [[AgenticCommerceAuthorizationRequestTransfer::RESOURCE => 'https://other.shop.example/api'], static::ERROR_INVALID_TARGET],
            'unsupported scope' => [[AgenticCommerceAuthorizationRequestTransfer::SCOPE => 'checkout:write'], static::ERROR_INVALID_SCOPE],
            'scope outside the client ceiling' => [[AgenticCommerceAuthorizationRequestTransfer::SCOPE => static::SCOPE_CART_WRITE], static::ERROR_INVALID_SCOPE],
        ];
    }

    /**
     * @dataProvider invalidAuthorizationDetailsDataProvider
     */
    public function testGivenAnInvalidAuthorizationDetailsParameterWhenValidatingThenInvalidAuthorizationDetailsIsReturnedWithTheMatchingDescription(
        string $authorizationDetails,
        bool $isPluginRejectingValues,
        string $expectedErrorDescription
    ): void {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin(
            validationErrorTransfers: $isPluginRejectingValues ? [(new ErrorTransfer())->setMessage(static::ERROR_LIMIT_INVALID)] : [],
        )]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($this->tester->haveAgenticCommerceClient())
            ->setAuthorizationDetails($authorizationDetails);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertTrue($authorizationResponseTransfer->getIsRedirectable());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_AUTHORIZATION_DETAILS, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame($expectedErrorDescription, $authorizationResponseTransfer->getErrorDescription());
    }

    /**
     * @return array<string, array{string, bool, string}>
     */
    public static function invalidAuthorizationDetailsDataProvider(): array
    {
        $limitObject = [
            'type' => FakeAuthorizationDetailsAwareConstraintPlugin::AUTHORIZATION_DETAILS_TYPE,
            FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::REQUESTED_LIMIT,
        ];

        return [
            'more than the maximum bytes' => [
                static::encode([$limitObject + [static::PADDING_KEY => str_repeat('a', AgenticCommerceConfig::AUTHORIZATION_DETAILS_MAX_BYTES)]]),
                false,
                static::ERROR_DESCRIPTION_TOO_LARGE,
            ],
            'more than the maximum objects' => [
                static::encode(array_fill(0, AgenticCommerceConfig::AUTHORIZATION_DETAILS_MAX_OBJECTS + 1, $limitObject)),
                false,
                static::ERROR_DESCRIPTION_TOO_MANY_OBJECTS,
            ],
            'not JSON' => ['[{"type":', false, static::ERROR_DESCRIPTION_NOT_JSON],
            'a JSON object instead of an array' => [static::encode($limitObject), false, static::ERROR_DESCRIPTION_NOT_A_LIST],
            'a JSON object with numeric keys' => [sprintf('{"0":%s}', static::encode($limitObject)), false, static::ERROR_DESCRIPTION_NOT_A_LIST],
            'an object without a type' => [static::encode([[FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::REQUESTED_LIMIT]]), false, static::ERROR_DESCRIPTION_OBJECT_WITHOUT_TYPE],
            'an object with a type that is not a string' => [static::encode([['type' => static::REQUESTED_LIMIT]]), false, static::ERROR_DESCRIPTION_OBJECT_WITHOUT_TYPE],
            'an unknown type' => [static::encode([['type' => static::UNKNOWN_AUTHORIZATION_DETAILS_TYPE]]), false, static::ERROR_DESCRIPTION_UNKNOWN_TYPE],
            'two objects of one type' => [static::encode([$limitObject, $limitObject]), false, static::ERROR_DESCRIPTION_DUPLICATE_TYPE],
            'a value the plugin rejects' => [static::encode([$limitObject]), true, static::ERROR_DESCRIPTION_INVALID_VALUE],
        ];
    }

    public function testGivenAValidAuthorizationDetailsObjectWhenValidatingThenItIsReturnedAsARequestedConstraintOfTheStoredTypeWithoutItsType(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin()]);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequest($this->tester->haveAgenticCommerceClient())
            ->setAuthorizationDetails(static::encode([[
                'type' => FakeAuthorizationDetailsAwareConstraintPlugin::AUTHORIZATION_DETAILS_TYPE,
                FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::REQUESTED_LIMIT,
            ]]));

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertCount(1, $authorizationResponseTransfer->getRequestedConstraints());
        $requestedConstraintTransfer = $authorizationResponseTransfer->getRequestedConstraints()->offsetGet(0);
        $this->assertSame(FakeAuthorizationDetailsAwareConstraintPlugin::TYPE, $requestedConstraintTransfer->getType());
        $this->assertSame([FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::REQUESTED_LIMIT], $requestedConstraintTransfer->getValue());
    }

    /**
     * @param array<mixed> $value
     */
    protected static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
