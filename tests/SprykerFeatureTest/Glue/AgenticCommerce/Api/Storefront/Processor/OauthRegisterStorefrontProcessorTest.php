<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Psr\Http\Client\NetworkExceptionInterface;
use Spryker\Shared\ErrorHandler\ErrorLoggerInterface;
use Spryker\Shared\ZedRequest\Client\Exception\RequestException;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Processor\OauthRegisterStorefrontProcessor;
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
 * @group OauthRegisterStorefrontProcessorTest
 * Add your own group annotations below this line
 */
class OauthRegisterStorefrontProcessorTest extends Unit
{
    protected const int CLIENT_ID_ISSUED_AT = 1760000000;

    protected const string CLIENT_IDENTIFIER = '0b8e3c1e-4f3a-4c1b-9a51-2f0c8d7e6a10';

    protected const string REDIRECT_URI = 'https://claude.ai/api/mcp/auth_callback';

    protected const string SCOPE = 'agentic-commerce:principal-read';

    protected const string REGISTRATION_BODY = '{"client_name":"Claude","redirect_uris":["https://claude.ai/api/mcp/auth_callback"],"token_endpoint_auth_method":"none"}';

    protected const string MEDIA_TYPE_JSON = 'application/json';

    protected function createProcessor(
        AgenticCommerceClientInterface $agenticCommerceClient,
        bool $isDynamicClientRegistrationEnabled = true,
        ?ErrorLoggerInterface $errorLogger = null
    ): OauthRegisterStorefrontProcessor {
        $agenticCommerceConfigMock = $this->createConfiguredMock(AgenticCommerceConfig::class, [
            'isConfigured' => true,
            'isDynamicClientRegistrationEnabled' => $isDynamicClientRegistrationEnabled,
            'getDynamicClientRegistrationRequestMaxBytes' => (new AgenticCommerceConfig())->getDynamicClientRegistrationRequestMaxBytes(),
        ]);

        return new OauthRegisterStorefrontProcessor(
            $agenticCommerceClient,
            new OauthProtocolResponseBuilder(),
            $agenticCommerceConfigMock,
            new ZedCallFailureLogger($errorLogger ?? $this->createMock(ErrorLoggerInterface::class)),
            new RequestMediaTypeChecker(),
        );
    }

    protected function processRegistration(
        OauthRegisterStorefrontProcessor $processor,
        string $body,
        string $contentType = self::MEDIA_TYPE_JSON
    ): Response {
        $request = Request::create('/oauth/register', Request::METHOD_POST, [], [], [], ['CONTENT_TYPE' => $contentType], $body);

        return $processor->process(null, new Post(), [], ['request' => $request]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeResponse(Response $response): array
    {
        return json_decode((string)$response->getContent(), true);
    }

    protected function createSuccessfulRegistrationResponse(): AgenticCommerceClientRegistrationResponseTransfer
    {
        return (new AgenticCommerceClientRegistrationResponseTransfer())
            ->setIsSuccessful(true)
            ->setClientIdIssuedAt(static::CLIENT_ID_ISSUED_AT)
            ->setAcceptedScopes([static::SCOPE])
            ->setGrantTypes(['authorization_code', 'refresh_token'])
            ->setAgenticCommerceClient((new AgenticCommerceClientTransfer())
                ->setClientIdentifier(static::CLIENT_IDENTIFIER)
                ->setName('Claude')
                ->setRedirectUris([static::REDIRECT_URI])
                ->setRegistrationMetadata(static::REGISTRATION_BODY));
    }

    public function testGivenRegistrationIsDisabledWhenPostingThenTheResponseIs404AndZedIsNotCalled(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('registerAgenticCommerceClient');

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock, false), '{}');

        // Assert
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    /**
     * @dataProvider bodyThatIsNotAJsonObjectDataProvider
     */
    public function testGivenABodyThatIsNotAJsonObjectWhenPostingThenTheResponseIs400InvalidClientMetadataAndZedIsNotCalled(string $body): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('registerAgenticCommerceClient');

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock), $body);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_client_metadata', $this->decodeResponse($response)['error']);
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    /**
     * @return array<string, list<string>>
     */
    public static function bodyThatIsNotAJsonObjectDataProvider(): array
    {
        return [
            'list' => ['["a"]'],
            'empty list' => ['[]'],
            'string' => ['"client"'],
            'malformed' => ['{"client_name":'],
            'empty' => [''],
        ];
    }

    /**
     * @dataProvider contentTypeThatIsNotJsonDataProvider
     */
    public function testGivenABodyThatIsNotSentAsJsonWhenPostingThenTheResponseIs400InvalidClientMetadataAndZedIsNotCalled(string $contentType): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('registerAgenticCommerceClient');

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock), static::REGISTRATION_BODY, $contentType);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_client_metadata', $this->decodeResponse($response)['error']);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function contentTypeThatIsNotJsonDataProvider(): array
    {
        return [
            'form' => ['application/x-www-form-urlencoded'],
            'JSON:API' => ['application/vnd.api+json'],
            'text' => ['text/plain'],
        ];
    }

    public function testGivenAJsonMediaTypeWithParametersAndUpperCaseWhenPostingThenTheRegistrationReachesZed(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->once())->method('registerAgenticCommerceClient')->willReturn($this->createSuccessfulRegistrationResponse());

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock), static::REGISTRATION_BODY, 'Application/JSON; charset=UTF-8');

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testGivenABodyLargerThanTheClientMetadataLimitWhenPostingThenTheResponseIs400InvalidClientMetadataAndZedIsNotCalled(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->never())->method('registerAgenticCommerceClient');
        $maxBytes = (new AgenticCommerceConfig())->getDynamicClientRegistrationRequestMaxBytes();
        $body = json_encode(['client_name' => 'Claude', 'padding' => str_repeat('a', $maxBytes)], JSON_THROW_ON_ERROR);

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock), $body);

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('invalid_client_metadata', $this->decodeResponse($response)['error']);
    }

    public function testGivenAJsonObjectWhenPostingThenZedReceivesExactlyTheRawBody(): void
    {
        // Arrange
        $sentRegistrationRequests = [];
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('registerAgenticCommerceClient')->willReturnCallback(
            function (AgenticCommerceClientRegistrationRequestTransfer $registrationRequestTransfer) use (&$sentRegistrationRequests): AgenticCommerceClientRegistrationResponseTransfer {
                $sentRegistrationRequests[] = $registrationRequestTransfer;

                return $this->createSuccessfulRegistrationResponse();
            },
        );

        // Act
        $this->processRegistration($this->createProcessor($clientMock), static::REGISTRATION_BODY);

        // Assert
        $this->assertCount(1, $sentRegistrationRequests);
        $this->assertSame(['rawRequest' => static::REGISTRATION_BODY], $sentRegistrationRequests[0]->toArray(true, true));
    }

    public function testGivenASuccessfulRegistrationWhenPostingThenTheResponseIs201WithTheClientInformationAndNoSecret(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('registerAgenticCommerceClient')->willReturn($this->createSuccessfulRegistrationResponse());

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock), static::REGISTRATION_BODY);

        // Assert
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame([
            'client_id' => static::CLIENT_IDENTIFIER,
            'client_id_issued_at' => static::CLIENT_ID_ISSUED_AT,
            'client_name' => 'Claude',
            'redirect_uris' => [static::REDIRECT_URI],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
            'scope' => static::SCOPE,
        ], $this->decodeResponse($response));
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    public function testGivenARegisteredClientWithOptionalMetadataAndNoAcceptedScopeWhenPostingThenTheOptionalFieldsAreEchoedAndScopeIsLeftOut(): void
    {
        // Arrange
        $registrationResponseTransfer = $this->createSuccessfulRegistrationResponse()->setAcceptedScopes([]);
        $registrationResponseTransfer->getAgenticCommerceClientOrFail()
            ->setClientUri('https://claude.ai')
            ->setLogoUri('https://claude.ai/logo.png')
            ->setSoftwareId('claude-connector')
            ->setSoftwareVersion('1.0');
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('registerAgenticCommerceClient')->willReturn($registrationResponseTransfer);

        // Act
        $content = $this->decodeResponse($this->processRegistration($this->createProcessor($clientMock), static::REGISTRATION_BODY));

        // Assert
        $this->assertArrayNotHasKey('scope', $content);
        $this->assertSame('https://claude.ai', $content['client_uri']);
        $this->assertSame('https://claude.ai/logo.png', $content['logo_uri']);
        $this->assertSame('claude-connector', $content['software_id']);
        $this->assertSame('1.0', $content['software_version']);
    }

    public function testGivenARefusedRegistrationWhenPostingThenTheResponseIs400WithTheErrorZedNamed(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('registerAgenticCommerceClient')->willReturn((new AgenticCommerceClientRegistrationResponseTransfer())
            ->setIsSuccessful(false)
            ->setErrorCode('invalid_redirect_uri')
            ->setErrorDescription('Between one and five redirect_uris are required.'));

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock), '{"redirect_uris":[1]}');

        // Assert
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(
            ['error' => 'invalid_redirect_uri', 'error_description' => 'Between one and five redirect_uris are required.'],
            $this->decodeResponse($response),
        );
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
    }

    public function testGivenTheZedCallFailsWhenPostingThenTheResponseIs500ServerErrorAndTheFailureIsLogged(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('registerAgenticCommerceClient')->willThrowException($this->createMock(NetworkExceptionInterface::class));
        $errorLoggerMock = $this->createMock(ErrorLoggerInterface::class);
        $errorLoggerMock->expects($this->once())->method('log');

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock, true, $errorLoggerMock), static::REGISTRATION_BODY);

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('server_error', $this->decodeResponse($response)['error']);
    }

    public function testGivenTheZedRequestFailsWhenPostingThenTheResponseIs500ServerErrorAndTheFailureIsNotLoggedAgain(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->method('registerAgenticCommerceClient')->willThrowException(new RequestException());
        $errorLoggerMock = $this->createMock(ErrorLoggerInterface::class);

        // Assert
        $errorLoggerMock->expects($this->never())->method('log');

        // Act
        $response = $this->processRegistration($this->createProcessor($clientMock, true, $errorLoggerMock), static::REGISTRATION_BODY);

        // Assert
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('server_error', $this->decodeResponse($response)['error']);
    }
}
