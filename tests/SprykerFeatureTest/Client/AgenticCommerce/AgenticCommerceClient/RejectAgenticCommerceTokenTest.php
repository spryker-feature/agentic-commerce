<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationResponseTransfer;
use Lcobucci\JWT\Token\RegisteredClaims;
use SprykerFeature\Client\AgenticCommerce\Plugin\Oauth\AgenticCommerceTokenRejectingAccessTokenValidatorPlugin;
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
 * @group RejectAgenticCommerceTokenTest
 * Add your own group annotations below this line
 */
class RejectAgenticCommerceTokenTest extends Unit
{
    protected const string TOKEN_TYPE_BEARER = 'Bearer';

    protected const string MALFORMED_TOKEN = 'not-a-json-web-token';

    protected AgenticCommerceClientTester $tester;

    protected function _before(): void
    {
        $this->tester->configureDelegatedAccessTokenValidation();
    }

    public function testGivenATokenCarryingTheConsentClaimAndTheDelegatedResourceAudienceWhenTheStorefrontValidatesItThenItIsRejectedAsInvalidToken(): void
    {
        // Arrange
        $delegatedAccessToken = $this->tester->signTokenWithTheDelegatedKey();
        $validationRequestTransfer = (new OauthAccessTokenValidationRequestTransfer())->setAccessToken($delegatedAccessToken)->setType(static::TOKEN_TYPE_BEARER);

        // Act
        $validationResponseTransfer = (new AgenticCommerceTokenRejectingAccessTokenValidatorPlugin())->validate($validationRequestTransfer, (new OauthAccessTokenValidationResponseTransfer())->setIsValid(true));

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_TOKEN, $validationResponseTransfer->getErrorOrFail()->getErrorType());
    }

    public function testGivenATokenSignedWithTheDelegatedKeyCarryingTheStorefrontAudienceAndNoConsentClaimWhenRejectingThenNoErrorIsSetAndTheStorefrontValidationDecides(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey([
            RegisteredClaims::AUDIENCE => AgenticCommerceClientTokenHelper::STOREFRONT_AUDIENCE,
            AgenticCommerceConfig::CLAIM_CONSENT_UUID => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE,
        ]);

        // Act
        $validationResponseTransfer = $this->rejectOnStorefront($accessToken);

        // Assert
        $this->assertNull($validationResponseTransfer->getError());
    }

    public function testGivenTheDelegatedPublicKeyEqualToTheStorefrontKeyWhenTheStorefrontValidatesAStorefrontTokenWithThePluginThenItIsValid(): void
    {
        // Arrange
        // a misconfiguration the deployment must not make
        $this->tester->setConfig(AgenticCommerceConstants::PUBLIC_KEY, $this->tester->getStorefrontPublicKey());
        $this->tester->configureStorefrontTokenValidation([$this->tester->createAgenticCommerceTokenRejectingAccessTokenValidatorPlugin()]);

        // Act
        $validationResponseTransfer = $this->validateOnStorefront($this->tester->buildStorefrontAccessToken());

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid(), (string)$validationResponseTransfer->getError()?->getMessage());
    }

    public function testGivenAStorefrontTokenWhenTheStorefrontValidatesItWithTheRejectingPluginWiredThenItIsValid(): void
    {
        // Arrange
        $this->tester->configureStorefrontTokenValidation([$this->tester->createAgenticCommerceTokenRejectingAccessTokenValidatorPlugin()]);

        // Act
        $validationResponseTransfer = $this->validateOnStorefront($this->tester->buildStorefrontAccessToken());

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid(), (string)$validationResponseTransfer->getError()?->getMessage());
    }

    public function testGivenADelegatedAccessTokenSignedWithTheStorefrontKeyWhenTheStorefrontValidatesItWithTheRejectingPluginThenItIsRejectedAsInvalidToken(): void
    {
        // Arrange
        $this->tester->configureStorefrontTokenValidation([$this->tester->createAgenticCommerceTokenRejectingAccessTokenValidatorPlugin()]);

        // Act
        $validationResponseTransfer = $this->validateOnStorefront(
            $this->tester->signTokenWithTheDelegatedKey([], $this->tester->getStorefrontPrivateKey()),
        );

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_TOKEN, $validationResponseTransfer->getErrorOrFail()->getErrorType());
    }

    public function testGivenADelegatedAccessTokenSignedWithTheStorefrontKeyWhenTheStorefrontValidatesItWithoutThePluginThenItIsValid(): void
    {
        // Arrange
        $this->tester->configureStorefrontTokenValidation([]);

        // Act
        $validationResponseTransfer = $this->validateOnStorefront(
            $this->tester->signTokenWithTheDelegatedKey([], $this->tester->getStorefrontPrivateKey()),
        );

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid());
    }

    public function testGivenADelegatedAccessTokenSignedWithTheDelegatedKeyWhenTheStorefrontValidatesItWithoutThePluginThenItIsInvalidBecauseTheStorefrontKeyDoesNotVerifyIt(): void
    {
        // Arrange
        $this->tester->configureStorefrontTokenValidation([]);

        // Act
        $validationResponseTransfer = $this->validateOnStorefront($this->tester->signTokenWithTheDelegatedKey());

        // Assert
        $this->assertFalse($validationResponseTransfer->getIsValid());
    }

    public function testGivenATokenNamingTheDelegatedResourceAudienceWithoutTheConsentClaimWhenRejectingThenAnErrorIsSet(): void
    {
        // Arrange
        $accessToken = $this->tester->signTokenWithTheDelegatedKey(
            [AgenticCommerceConfig::CLAIM_CONSENT_UUID => AgenticCommerceClientTokenHelper::OVERRIDE_REMOVE],
            $this->tester->getStorefrontPrivateKey(),
        );

        // Act
        $validationResponseTransfer = $this->rejectOnStorefront($accessToken);

        // Assert
        $this->assertNotNull($validationResponseTransfer->getError());
    }

    public function testGivenAMalformedTokenWhenRejectingThenNoErrorIsSetAndTheStorefrontValidationDecides(): void
    {
        // Act
        $validationResponseTransfer = $this->rejectOnStorefront(static::MALFORMED_TOKEN);

        // Assert
        $this->assertNull($validationResponseTransfer->getError());
    }

    public function testGivenDelegatedAccessIsNotConfiguredWhenTheStorefrontValidatesAStorefrontTokenWithThePluginThenItIsValid(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::PUBLIC_KEY, '');
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');
        $this->tester->configureStorefrontTokenValidation([$this->tester->createAgenticCommerceTokenRejectingAccessTokenValidatorPlugin()]);

        // Act
        $validationResponseTransfer = $this->validateOnStorefront($this->tester->buildStorefrontAccessToken());

        // Assert
        $this->assertTrue($validationResponseTransfer->getIsValid(), (string)$validationResponseTransfer->getError()?->getMessage());
    }

    protected function rejectOnStorefront(string $accessToken): OauthAccessTokenValidationResponseTransfer
    {
        return $this->tester->getClient()->rejectAgenticCommerceToken(
            (new OauthAccessTokenValidationRequestTransfer())->setAccessToken($accessToken)->setType(static::TOKEN_TYPE_BEARER),
            (new OauthAccessTokenValidationResponseTransfer())->setIsValid(false),
        );
    }

    protected function validateOnStorefront(string $accessToken): OauthAccessTokenValidationResponseTransfer
    {
        return $this->tester->getLocator()->oauth()->client()->validateOauthAccessToken(
            (new OauthAccessTokenValidationRequestTransfer())
                ->setAccessToken($accessToken)
                ->setType(static::TOKEN_TYPE_BEARER),
        );
    }
}
