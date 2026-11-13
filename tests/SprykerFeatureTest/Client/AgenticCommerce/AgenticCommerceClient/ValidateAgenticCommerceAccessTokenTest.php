<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Lcobucci\JWT\Token\RegisteredClaims;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;
use SprykerFeatureTest\Client\AgenticCommerce\Helper\AgenticCommerceClientTokenHelper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group ValidateAgenticCommerceAccessTokenTest
 * Add your own group annotations below this line
 */
class ValidateAgenticCommerceAccessTokenTest extends Unit
{
    protected const string OTHER_ISSUER = 'https://attacker.example';

    protected const string OTHER_RESOURCE_URI = 'https://other-shop.example/agentic-commerce';

    protected const string DECLARED_RESOURCE_URI = 'https://glue.example/mcp';

    protected const string TEN_SECONDS_AGO = '-10 seconds';

    protected const string IN_ONE_MINUTE = '+1 minute';

    protected const string IN_TWO_SECONDS = '+2 seconds';

    protected const string IN_TEN_SECONDS = '+10 seconds';

    protected const string TOKEN_IDENTIFIER = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4';

    protected const string MALFORMED_TOKEN = 'not-a-json-web-token';

    protected const string AUTHORIZATION_DETAILS_TYPE_ORDER_BUDGET = 'https://spryker.com/oauth/authorization-details/order-budget';

    protected const string AUTHORIZATION_DETAILS_TYPE_MERCHANT_ALLOWLIST = 'https://spryker.com/oauth/authorization-details/merchant-allowlist';

    protected AgenticCommerceClientTester $tester;

    protected function _before(): void
    {
        $this->tester->configureDelegatedAccessTokenValidation();
        $this->tester->declarePrincipalResource();
    }

    public function testGivenADelegatedAccessTokenSignedWithTheDelegatedKeyCarryingEveryRequiredClaimWhenValidatingItThenItIsValidAndMappedFromItsClaims(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([RegisteredClaims::ID => static::TOKEN_IDENTIFIER]);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertNull($validationResponseTransfer->getErrorCode());
        $accessTokenTransfer = $validationResponseTransfer->getAgenticCommerceAccessTokenOrFail();
        $this->assertSame(AgenticCommerceClientTokenHelper::ISSUER, $accessTokenTransfer->getIssuer());
        $this->assertSame([AgenticCommerceClientTokenHelper::RESOURCE_URI], $accessTokenTransfer->getAudience());
        $this->assertSame(AgenticCommerceClientTokenHelper::CLIENT_ID, $accessTokenTransfer->getClientId());
        $this->assertSame(
            json_encode([AgenticCommerceClientTokenHelper::USER_IDENTIFIER_KEY_CUSTOMER_REFERENCE => AgenticCommerceClientTokenHelper::CUSTOMER_REFERENCE], JSON_THROW_ON_ERROR),
            $accessTokenTransfer->getSubject(),
        );
        $this->assertSame(AgenticCommerceClientTokenHelper::STORE_NAME, $accessTokenTransfer->getStoreName());
        $this->assertSame(AgenticCommerceClientTokenHelper::CONSENT_UUID, $accessTokenTransfer->getConsentUuid());
        $this->assertSame([AgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER, AgenticCommerceClientTokenHelper::SCOPE], $accessTokenTransfer->getScopes());
        $this->assertSame(static::TOKEN_IDENTIFIER, $accessTokenTransfer->getTokenIdentifier());
        $this->assertNotNull($accessTokenTransfer->getExpiresAt());
    }

    public function testGivenADelegatedAccessTokenCarryingAuthorizationDetailsWhenValidatingItThenTheAccessTokenCarriesTheSameList(): void
    {
        // Arrange
        $authorizationDetails = [
            ['type' => static::AUTHORIZATION_DETAILS_TYPE_ORDER_BUDGET, 'amount' => 50000, 'currency' => 'EUR', 'period' => 'month'],
            ['type' => static::AUTHORIZATION_DETAILS_TYPE_MERCHANT_ALLOWLIST, 'merchantReferences' => ['MER-1', 'MER-2']],
        ];
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([AgenticCommerceConfig::CLAIM_AUTHORIZATION_DETAILS => $authorizationDetails]);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame($authorizationDetails, $validationResponseTransfer->getAgenticCommerceAccessTokenOrFail()->getAuthorizationDetails());
    }

    public function testGivenADelegatedAccessTokenWithoutAnAuthorizationDetailsClaimWhenValidatingItThenTheAccessTokenCarriesAnEmptyList(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey();

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame([], $validationResponseTransfer->getAgenticCommerceAccessTokenOrFail()->getAuthorizationDetails());
    }

    public function testGivenADelegatedAccessTokenWhoseAuthorizationDetailsClaimIsNotAListWhenValidatingItThenTheAccessTokenCarriesAnEmptyList(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([AgenticCommerceConfig::CLAIM_AUTHORIZATION_DETAILS => 'order-budget']);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame([], $validationResponseTransfer->getAgenticCommerceAccessTokenOrFail()->getAuthorizationDetails());
    }

    /**
     * @dataProvider provideTokensFailingOneCheck
     *
     * @param array<string, mixed> $claimOverrides
     */
    public function testGivenADelegatedAccessTokenWithOneClaimMissingWrongOrOutsideItsValidityWindowWhenValidatingItThenItIsRejectedAsInvalidTokenWithoutAnAccessToken(
        array $claimOverrides
    ): void {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey($claimOverrides);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_TOKEN, $validationResponseTransfer->getErrorCode());
        $this->assertNull($validationResponseTransfer->getAgenticCommerceAccessToken());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function provideTokensFailingOneCheck(): array
    {
        return [
            'other issuer' => [[RegisteredClaims::ISSUER => static::OTHER_ISSUER]],
            'no issuer' => [[RegisteredClaims::ISSUER => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'other audience' => [[RegisteredClaims::AUDIENCE => static::OTHER_RESOURCE_URI]],
            'storefront audience' => [[RegisteredClaims::AUDIENCE => AgenticCommerceClientTokenHelper::STOREFRONT_AUDIENCE]],
            'expired' => [[RegisteredClaims::EXPIRATION_TIME => new DateTimeImmutable(static::TEN_SECONDS_AGO)]],
            'no expiration time' => [[RegisteredClaims::EXPIRATION_TIME => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'not yet valid' => [[RegisteredClaims::NOT_BEFORE => new DateTimeImmutable(static::IN_ONE_MINUTE)]],
            'no client id' => [[AgenticCommerceConfig::CLAIM_CLIENT_ID => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'no store' => [[AgenticCommerceConfig::CLAIM_STORE => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'no consent' => [[AgenticCommerceConfig::CLAIM_CONSENT_UUID => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'no scopes' => [[AgenticCommerceConfig::CLAIM_SCOPES => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'no token identifier' => [[RegisteredClaims::ID => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
            'no subject' => [[RegisteredClaims::SUBJECT => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE]],
        ];
    }

    public function testGivenATokenForAnotherDeclaredProtectedResourceWhenValidatingItThenItIsValidAndCarriesThatAudience(): void
    {
        // Arrange
        $this->tester->declareProtectedResources([
            ['name' => AgenticCommerceClientTokenHelper::PRINCIPAL_RESOURCE_NAME, 'pathPrefix' => '/agentic-commerce', 'resourceUri' => AgenticCommerceClientTokenHelper::RESOURCE_URI, 'scopes' => []],
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => static::DECLARED_RESOURCE_URI, 'scopes' => []],
        ]);
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([RegisteredClaims::AUDIENCE => static::DECLARED_RESOURCE_URI]);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
        $this->assertSame([static::DECLARED_RESOURCE_URI], $validationResponseTransfer->getAgenticCommerceAccessTokenOrFail()->getAudience());
    }

    public function testGivenANotBeforeTimeWithinTheClockLeewayWhenValidatingThenTheTokenIsValid(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([RegisteredClaims::NOT_BEFORE => new DateTimeImmutable(static::IN_TWO_SECONDS)]);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
    }

    public function testGivenANotBeforeTimeBeyondTheClockLeewayWhenValidatingThenTheTokenIsInvalid(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([RegisteredClaims::NOT_BEFORE => new DateTimeImmutable(static::IN_TEN_SECONDS)]);

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
    }

    public function testGivenADelegatedAccessTokenSignedWithAnotherKeyWhenValidatingItThenItIsInvalid(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([], $this->tester->generatePrivateKey());

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
    }

    public function testGivenAStorefrontAccessTokenWhenTheDelegatedValidatorValidatesItThenItIsRejectedAsInvalidToken(): void
    {
        // Arrange
        $accessToken = $this->tester->buildStorefrontAccessToken();

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_TOKEN, $validationResponseTransfer->getErrorCode());
    }

    public function testGivenAMalformedTokenWhenValidatingItThenItIsRejectedAsInvalidToken(): void
    {
        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken(static::MALFORMED_TOKEN);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_TOKEN, $validationResponseTransfer->getErrorCode());
    }

    /**
     * @dataProvider provideUnconfiguredValues
     */
    public function testGivenTheIssuerTheResourceUriOrThePublicKeyIsNotConfiguredWhenValidatingADelegatedAccessTokenThenItIsRejectedAsInvalidToken(
        string $constant
    ): void {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey();
        $this->tester->setConfig($constant, '');

        // Act
        $validationResponseTransfer = $this->tester->getClient()->validateAgenticCommerceAccessToken($accessToken);

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_TOKEN, $validationResponseTransfer->getErrorCode());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideUnconfiguredValues(): array
    {
        return [
            'issuer' => [AgenticCommerceConstants::ISSUER],
            'public key' => [AgenticCommerceConstants::PUBLIC_KEY],
        ];
    }
}
