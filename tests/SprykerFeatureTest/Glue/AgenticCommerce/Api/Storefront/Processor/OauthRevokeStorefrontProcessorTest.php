<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationResponseTransfer;
use Spryker\Shared\ErrorHandler\ErrorLoggerInterface;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Processor\OauthRevokeStorefrontProcessor;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request\RequestMediaTypeChecker;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response\OauthProtocolResponseBuilder;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapper;
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
 * @group OauthRevokeStorefrontProcessorTest
 * Add your own group annotations below this line
 */
class OauthRevokeStorefrontProcessorTest extends Unit
{
    protected const string IP_ADDRESS = '203.0.113.7';

    protected const string MEDIA_TYPE_FORM = 'application/x-www-form-urlencoded';

    protected const int USER_AGENT_MAX_LENGTH = 512;

    public function testGivenARevocationRequestWithAnIpAddressAndALongUserAgentWhenProcessedThenTheClientDataIsPassedToZedWithTheUserAgentTruncated(): void
    {
        // Arrange
        $request = Request::create('/oauth/revoke', Request::METHOD_POST, ['token' => 'refresh-token', 'client_id' => 'client'], [], [], [
            'CONTENT_TYPE' => static::MEDIA_TYPE_FORM,
            'REMOTE_ADDR' => static::IP_ADDRESS,
            'HTTP_USER_AGENT' => str_repeat('a', static::USER_AGENT_MAX_LENGTH + 88),
        ]);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        // Assert
        $clientMock->expects($this->once())
            ->method('revokeAgenticCommerceRefreshToken')
            ->with($this->callback(static function (AgenticCommerceRefreshTokenRevocationRequestTransfer $revocationRequestTransfer): bool {
                $requestClientDataTransfer = $revocationRequestTransfer->getRequestClientDataOrFail();

                return $revocationRequestTransfer->getToken() === 'refresh-token'
                    && $requestClientDataTransfer->getIpAddress() === static::IP_ADDRESS
                    && $requestClientDataTransfer->getUserAgent() === str_repeat('a', static::USER_AGENT_MAX_LENGTH);
            }))
            ->willReturn((new AgenticCommerceRefreshTokenRevocationResponseTransfer())->setIsSuccessful(true));

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGivenAnArrayTokenParameterWhenProcessedThenNothingIsRevokedAndInvalidRequestIsAnswered(): void
    {
        // Arrange
        $request = $this->createRevocationRequest(['token' => ['refresh-token'], 'client_id' => 'client']);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        // Assert
        $clientMock->expects($this->never())->method('revokeAgenticCommerceRefreshToken');

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_request', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenNoTokenWhenProcessedThenInvalidRequestIsAnsweredAndZedIsNotCalled(): void
    {
        // Arrange
        $request = $this->createRevocationRequest(['client_id' => 'client']);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        // Assert
        $clientMock->expects($this->never())->method('revokeAgenticCommerceRefreshToken');

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_request', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenNoClientIdWhenProcessedThenInvalidRequestIsAnsweredAndZedIsNotCalled(): void
    {
        // Arrange
        $request = $this->createRevocationRequest(['token' => 'refresh-token']);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        // Assert
        $clientMock->expects($this->never())->method('revokeAgenticCommerceRefreshToken');

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_request', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenAValidAccessTokenWhenRevokedThenUnsupportedTokenTypeIsAnswered(): void
    {
        // Arrange
        $request = $this->createRevocationRequest(['token' => 'access-token', 'client_id' => 'client']);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('validateAgenticCommerceAccessToken')
            ->willReturn((new AgenticCommerceAccessTokenValidationResponseTransfer())->setIsValid(true));

        // Assert
        $clientMock->expects($this->never())->method('revokeAgenticCommerceRefreshToken');

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('unsupported_token_type', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenABodyThatIsNotFormEncodedWhenProcessedThenInvalidRequestIsAnsweredAndNothingIsRevoked(): void
    {
        // Arrange
        $request = Request::create('/oauth/revoke', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"token":"refresh-token","client_id":"client"}');
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        // Assert
        $clientMock->expects($this->never())->method('revokeAgenticCommerceRefreshToken');

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_request', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenAnUnsuccessfulRevocationOfAnUnknownRefreshTokenWhenProcessedThenTheResponseIs200(): void
    {
        // Arrange
        $request = $this->createRevocationRequest(['token' => 'unknown-token', 'client_id' => 'client']);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('revokeAgenticCommerceRefreshToken')
            ->willReturn((new AgenticCommerceRefreshTokenRevocationResponseTransfer())->setIsSuccessful(false));

        // Act
        $response = $this->createProcessor($clientMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGivenTheZedRequestFailsWhenProcessedThenServerErrorIsAnsweredAndTheFailureIsNotLoggedAgain(): void
    {
        // Arrange
        $request = $this->createRevocationRequest(['token' => 'refresh-token', 'client_id' => 'client']);
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('revokeAgenticCommerceRefreshToken')->willThrowException(new RequestException());
        $errorLoggerMock = $this->createMock(ErrorLoggerInterface::class);

        // Assert
        $errorLoggerMock->expects($this->never())->method('log');

        // Act
        $response = $this->createProcessor($clientMock, $errorLoggerMock)->process(null, new Post(), [], ['request' => $request]);

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('server_error', json_decode((string)$response->getContent(), true)['error']);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createRevocationRequest(array $parameters): Request
    {
        return Request::create('/oauth/revoke', Request::METHOD_POST, $parameters, [], [], ['CONTENT_TYPE' => static::MEDIA_TYPE_FORM]);
    }

    protected function createProcessor(
        AgenticCommerceClientInterface $agenticCommerceClient,
        ?ErrorLoggerInterface $errorLogger = null
    ): OauthRevokeStorefrontProcessor {
        return new OauthRevokeStorefrontProcessor(
            $agenticCommerceClient,
            new OauthProtocolResponseBuilder(),
            $this->createConfiguredMock(AgenticCommerceConfig::class, ['isConfigured' => true]),
            new ZedCallFailureLogger($errorLogger ?? $this->createMock(ErrorLoggerInterface::class)),
            new RequestMediaTypeChecker(),
            new RequestClientDataMapper(),
        );
    }
}
