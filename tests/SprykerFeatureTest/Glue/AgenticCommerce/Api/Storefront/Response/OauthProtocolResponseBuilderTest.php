<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Response;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Api
 * @group Storefront
 * @group Response
 * @group OauthProtocolResponseBuilderTest
 * Add your own group annotations below this line
 */
class OauthProtocolResponseBuilderTest extends Unit
{
    protected const string ACCESS_TOKEN = 'delegated-access-token';

    protected const int EXPIRES_IN = 600;

    protected const string CLIENT_IDENTIFIER = 'agent-client';

    protected const int CLIENT_ID_ISSUED_AT = 1700000000;

    protected const string CONTENT_TYPE_PLAIN_TEXT = 'text/plain; charset=UTF-8';

    /**
     * @dataProvider errorStatusDataProvider
     */
    public function testGivenAnOauthErrorCodeWhenBuildingTheErrorResponseThenTheStatusMatchesTheErrorCodeAndTheBodyNamesItWithNoStore(
        string $error,
        int $expectedStatus
    ): void {
        // Act
        $response = (new OauthProtocolResponseBuilder())->createErrorResponse($error);

        // Assert
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame($error, json_decode((string)$response->getContent(), true)['error']);
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function errorStatusDataProvider(): array
    {
        return [
            'invalid client answers 400, never 401: clients never authenticate with an Authorization header' => ['invalid_client', Response::HTTP_BAD_REQUEST],
            'invalid grant' => ['invalid_grant', Response::HTTP_BAD_REQUEST],
            'server error' => ['server_error', Response::HTTP_INTERNAL_SERVER_ERROR],
            'temporarily unavailable' => ['temporarily_unavailable', Response::HTTP_SERVICE_UNAVAILABLE],
        ];
    }

    public function testGivenATokenResponseWithoutAScopeWhenBuildingTheTokenResponseThenTheScopeIsLeftOut(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setAccessToken(static::ACCESS_TOKEN)
            ->setExpiresIn(static::EXPIRES_IN)
            ->setScope('');

        // Act
        $response = (new OauthProtocolResponseBuilder())->createTokenResponse($oauthResponseTransfer);

        // Assert
        $this->assertArrayNotHasKey('scope', json_decode((string)$response->getContent(), true));
    }

    public function testGivenATokenResponseWithAuthorizationDetailsWhenBuildingTheTokenResponseThenTheBodyCarriesTheSameList(): void
    {
        // Arrange
        $authorizationDetails = [['type' => 'https://spryker.com/oauth/authorization-details/order-budget', 'amount' => 50000, 'currency' => 'EUR', 'period' => 'month']];
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setAccessToken(static::ACCESS_TOKEN)
            ->setExpiresIn(static::EXPIRES_IN)
            ->setAuthorizationDetails($authorizationDetails);

        // Act
        $response = (new OauthProtocolResponseBuilder())->createTokenResponse($oauthResponseTransfer);

        // Assert
        $this->assertSame($authorizationDetails, json_decode((string)$response->getContent(), true)['authorization_details']);
    }

    public function testGivenATokenResponseWithoutAuthorizationDetailsWhenBuildingTheTokenResponseThenTheKeyIsLeftOut(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setAccessToken(static::ACCESS_TOKEN)
            ->setExpiresIn(static::EXPIRES_IN);

        // Act
        $response = (new OauthProtocolResponseBuilder())->createTokenResponse($oauthResponseTransfer);

        // Assert
        $this->assertArrayNotHasKey('authorization_details', json_decode((string)$response->getContent(), true));
    }

    public function testGivenNothingToServeWhenBuildingTheNotFoundResponseThenItMustNotBeCached(): void
    {
        // Act
        $response = (new OauthProtocolResponseBuilder())->createNotFoundResponse();

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame(static::CONTENT_TYPE_PLAIN_TEXT, $response->headers->get('Content-Type'));
    }

    public function testGivenAnIssuedTokenWhenBuildingTheTokenResponseThenItMustNotBeCached(): void
    {
        // Arrange
        $oauthResponseTransfer = (new OauthResponseTransfer())
            ->setAccessToken(static::ACCESS_TOKEN)
            ->setExpiresIn(static::EXPIRES_IN);

        // Act
        $response = (new OauthProtocolResponseBuilder())->createTokenResponse($oauthResponseTransfer);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }

    public function testGivenARegisteredClientWhenBuildingTheClientInformationResponseThenItIsCreatedAndMustNotBeCached(): void
    {
        // Arrange
        $registrationResponseTransfer = (new AgenticCommerceClientRegistrationResponseTransfer())
            ->setClientIdIssuedAt(static::CLIENT_ID_ISSUED_AT)
            ->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setClientIdentifier(static::CLIENT_IDENTIFIER));

        // Act
        $response = (new OauthProtocolResponseBuilder())->createClientInformationResponse($registrationResponseTransfer);

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }

    public function testGivenARevokedTokenWhenBuildingTheRevocationResponseThenTheEmptyBodyIsPlainTextAndMustNotBeCached(): void
    {
        // Act
        $response = (new OauthProtocolResponseBuilder())->createRevocationResponse();

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertSame(static::CONTENT_TYPE_PLAIN_TEXT, $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }

    public function testGivenAFailedBackendCallWhenBuildingTheServerErrorResponseThenItIsAServerErrorThatMustNotBeCached(): void
    {
        // Act
        $response = (new OauthProtocolResponseBuilder())->createServerErrorResponse();

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('server_error', json_decode((string)$response->getContent(), true)['error']);
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }
}
