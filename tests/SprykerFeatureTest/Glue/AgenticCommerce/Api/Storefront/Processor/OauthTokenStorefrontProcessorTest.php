<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\OauthErrorTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\Client\Oauth\OauthClientInterface;
use Spryker\Shared\ErrorHandler\ErrorLoggerInterface;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Processor\OauthTokenStorefrontProcessor;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request\RequestMediaTypeChecker;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilder;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Api
 * @group Storefront
 * @group Processor
 * @group OauthTokenStorefrontProcessorTest
 * Add your own group annotations below this line
 */
class OauthTokenStorefrontProcessorTest extends Unit
{
    protected const string MEDIA_TYPE_FORM = 'application/x-www-form-urlencoded';

    protected const string MEDIA_TYPE_JSON = 'application/json';

    protected const string REFRESH_GRANT_BODY = 'grant_type=refresh_token&client_id=client&refresh_token=refresh-token';

    public function testGivenParametersInTheQueryStringWhenProcessedThenInvalidRequestIsAnsweredAndZedIsNotCalled(): void
    {
        // Arrange
        $request = $this->createTokenRequest(static::REFRESH_GRANT_BODY, '?grant_type=refresh_token');
        $oauthClientMock = $this->createMock(OauthClientInterface::class);

        // Assert
        $oauthClientMock->expects($this->never())->method('processAccessTokenRequest');

        // Act
        $response = $this->process($oauthClientMock, $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_request', $response);
    }

    public function testGivenAJsonBodyWhenProcessedThenInvalidRequestIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest('{"grant_type":"refresh_token"}', mediaType: static::MEDIA_TYPE_JSON);

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_request', $response);
    }

    public function testGivenARepeatedParameterWhenProcessedThenInvalidRequestIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest('grant_type=refresh_token&grant_type=authorization_code');

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_request', $response);
    }

    public function testGivenParameterNamesThatPhpNormalisesToOneKeyWhenProcessedThenInvalidRequestIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest('grant_type=refresh_token&grant.type=authorization_code');

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_request', $response);
    }

    public function testGivenAuthorizationDetailsWhenProcessedThenInvalidAuthorizationDetailsIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest(static::REFRESH_GRANT_BODY . '&authorization_details=%5B%5D');

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_authorization_details', $response);
    }

    public function testGivenNoGrantTypeWhenProcessedThenInvalidRequestIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest('client_id=client');

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_request', $response);
    }

    public function testGivenAnUnsupportedGrantTypeWhenProcessedThenUnsupportedGrantTypeIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest('grant_type=password&client_id=client');

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'unsupported_grant_type', $response);
    }

    public function testGivenAnArrayGrantTypeWhenProcessedThenInvalidRequestIsAnswered(): void
    {
        // Arrange
        $request = $this->createTokenRequest('grant_type%5B%5D=refresh_token&client_id=client');

        // Act
        $response = $this->process($this->createMock(OauthClientInterface::class), $request);

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_request', $response);
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    public function testGivenZedRefusesTheGrantWhenProcessedThenItsErrorIsAnswered(): void
    {
        // Arrange
        $oauthClientMock = $this->createMock(OauthClientInterface::class);
        $oauthClientMock->method('processAccessTokenRequest')->willReturn(
            (new OauthResponseTransfer())->setIsValid(false)->setError((new OauthErrorTransfer())->setErrorType('invalid_grant')->setMessage('The refresh token is invalid.')),
        );

        // Act
        $response = $this->process($oauthClientMock, $this->createTokenRequest(static::REFRESH_GRANT_BODY));

        // Assert
        $this->assertOauthError(Response::HTTP_BAD_REQUEST, 'invalid_grant', $response);
    }

    public function testGivenAValidRefreshGrantWhenProcessedThenItsParametersArePassedToZed(): void
    {
        // Arrange
        $oauthClientMock = $this->createMock(OauthClientInterface::class);

        // Assert
        $oauthClientMock->expects($this->once())
            ->method('processAccessTokenRequest')
            ->with($this->callback(static fn (OauthRequestTransfer $oauthRequestTransfer): bool => $oauthRequestTransfer->getGrantType() === 'refresh_token'
                && $oauthRequestTransfer->getClientId() === 'client'
                && $oauthRequestTransfer->getRefreshToken() === 'refresh-token'))
            ->willReturn((new OauthResponseTransfer())->setIsValid(true)->setAccessToken('access-token')->setExpiresIn(300));

        // Act
        $response = $this->process($oauthClientMock, $this->createTokenRequest(static::REFRESH_GRANT_BODY));

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGivenTheZedRequestFailsWhenProcessedThenServerErrorIsAnsweredAndTheFailureIsNotLoggedAgain(): void
    {
        // Arrange
        $oauthClientMock = $this->createMock(OauthClientInterface::class);
        $oauthClientMock->method('processAccessTokenRequest')->willThrowException(new RequestException());
        $errorLoggerMock = $this->createMock(ErrorLoggerInterface::class);

        // Assert
        $errorLoggerMock->expects($this->never())->method('log');

        // Act
        $response = $this->process($oauthClientMock, $this->createTokenRequest(static::REFRESH_GRANT_BODY), $errorLoggerMock);

        // Assert
        $this->assertOauthError(Response::HTTP_INTERNAL_SERVER_ERROR, 'server_error', $response);
    }

    protected function createTokenRequest(string $body, string $query = '', string $mediaType = self::MEDIA_TYPE_FORM): Request
    {
        parse_str($body, $parameters);

        return Request::create('/oauth/token' . $query, Request::METHOD_POST, $parameters, [], [], ['CONTENT_TYPE' => $mediaType], $body);
    }

    protected function process(OauthClientInterface $oauthClient, Request $request, ?ErrorLoggerInterface $errorLogger = null): Response
    {
        $processor = new OauthTokenStorefrontProcessor(
            $oauthClient,
            new OauthProtocolResponseBuilder(),
            $this->createConfiguredMock(AgenticCommerceConfig::class, ['isConfigured' => true]),
            new ZedCallFailureLogger($errorLogger ?? $this->createMock(ErrorLoggerInterface::class)),
            new RequestMediaTypeChecker(),
        );

        return $processor->process(null, new Post(), [], ['request' => $request]);
    }

    protected function assertOauthError(int $expectedStatus, string $expectedError, Response $response): void
    {
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame($expectedError, json_decode((string)$response->getContent(), true)['error']);
    }
}
