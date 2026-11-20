<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use GuzzleHttp\Psr7\Response;
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
 * @group ClientMetadataDocumentPolicyTest
 * Add your own group annotations below this line
 */
class ClientMetadataDocumentPolicyTest extends Unit
{
    protected const string CLIENT_ID_URL = 'https://agents.example.com/client.json';

    protected const string OTHER_CLIENT_ID_URL = 'https://other.example.net/client.json';

    protected const string REDIRECT_URI = 'https://agents.example.com/callback';

    protected const string SOFTWARE_ID = 'burst-agent';

    protected const string ERROR_INVALID_CLIENT = 'invalid_client';

    protected const string DYNAMIC_CLIENT_REDIRECT_HOST = 'claude.ai';

    protected const int HTTP_STATUS_OK = 200;

    protected const int HTTP_STATUS_NOT_FOUND = 404;

    protected const int HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;

    protected const int CACHE_LIFETIME_SECONDS = 3600;

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_REDIRECT_HOST_ALLOWLIST, [static::DYNAMIC_CLIENT_REDIRECT_HOST]);
        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([
            AgenticCommerceHelper::SCOPE => 'agentic_commerce.scope.agentic-commerce.principal-read',
        ])]);
        $this->tester->allowAllHostsInTheAddressGuard();
    }

    protected function createDocumentResponse(
        string $clientIdentifier = self::CLIENT_ID_URL,
        string $redirectUri = self::REDIRECT_URI,
        ?string $softwareId = null
    ): Response {
        return new Response(
            static::HTTP_STATUS_OK,
            ['content-type' => 'application/json', 'cache-control' => 'max-age=' . static::CACHE_LIFETIME_SECONDS],
            json_encode(array_filter([
                'client_id' => $clientIdentifier,
                'client_name' => 'Agent',
                'redirect_uris' => [$redirectUri],
                'token_endpoint_auth_method' => 'none',
                'software_id' => $softwareId,
            ], static fn (mixed $value): bool => $value !== null), JSON_THROW_ON_ERROR),
        );
    }

    protected function resolveClientForAuthorization(string $clientIdentifier): AgenticCommerceAuthorizationResponseTransfer
    {
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier($clientIdentifier, static::REDIRECT_URI);

        return $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);
    }

    protected function registerDynamicClient(string $softwareId): AgenticCommerceClientRegistrationResponseTransfer
    {
        return $this->tester->getFacade()->registerAgenticCommerceClient(
            (new AgenticCommerceClientRegistrationRequestTransfer())->setRawRequest(json_encode([
                'client_name' => 'Agent',
                'redirect_uris' => [sprintf('https://%s/api/mcp/auth_callback', static::DYNAMIC_CLIENT_REDIRECT_HOST)],
                'token_endpoint_auth_method' => 'none',
                'scope' => AgenticCommerceHelper::SCOPE,
                'software_id' => $softwareId,
            ], JSON_THROW_ON_ERROR)),
        );
    }

    public function testGivenAFailedMetadataDocumentWhenTheSameClientIdIsResolvedAgainWithinTheTtlThenNothingIsFetched(): void
    {
        // Arrange
        $this->tester->mockClientMetadataDocumentResponse(new Response(static::HTTP_STATUS_NOT_FOUND), $this->createDocumentResponse());
        $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_RECENTLY_FAILED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(1, $this->tester->getHttpRequestCount());
    }

    public function testGivenTheFailureCacheIsSwitchedOffWhenTheSameClientIdIsResolvedAgainThenTheDocumentIsFetchedAgain(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_FAILURE_CACHE_TTL_SECONDS, 0);
        $this->tester->mockClientMetadataDocumentResponse(new Response(static::HTTP_STATUS_NOT_FOUND), $this->createDocumentResponse());
        $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame(2, $this->tester->getHttpRequestCount());
    }

    public function testGivenAFailedMetadataDocumentAndAnUnreachableCacheWhenTheSameClientIdIsResolvedAgainThenTheDocumentIsFetchedAgain(): void
    {
        // Arrange
        $this->tester->useFailingConsentCache();
        $this->tester->mockClientMetadataDocumentResponse(new Response(static::HTTP_STATUS_INTERNAL_SERVER_ERROR), $this->createDocumentResponse());
        $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame(2, $this->tester->getHttpRequestCount());
    }

    public function testGivenAHostAllowlistWhenAClientIdOnAnotherHostIsResolvedThenItIsRefusedWithoutFetching(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse());

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization('https://other.example.net/client.json');

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_NOT_ALLOWED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenAHostAllowlistWhenAClientIdOnAnAllowedHostIsResolvedThenTheDocumentIsFetched(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse());

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame(1, $this->tester->getHttpRequestCount());
    }

    public function testGivenADenylistedHostWhenAClientIdOnThatHostIsResolvedThenItIsRefusedWithoutFetching(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse());

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenADenylistedRedirectHostWithNoBlockedClientOfItsFamilyWhenAMetadataDocumentNamesItThenTheClientIsNotCreated(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse(static::OTHER_CLIENT_ID_URL));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::OTHER_CLIENT_ID_URL);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_REDIRECT_HOST_BLOCKED, $authorizationResponseTransfer->getFailureReason());
        $this->assertNull($this->tester->findClientOrNull(static::OTHER_CLIENT_ID_URL));
    }

    public function testGivenADenylistedHostWhenAClientIdWithATrailingDotOnThatHostIsResolvedThenItIsRefusedWithoutFetching(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse());

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization('https://agents.example.com./client.json');

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenAHostAllowlistWhenAClientIdWithATrailingDotOnAnotherHostIsResolvedThenItIsRefusedWithoutFetching(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse());

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization('https://other.example.net./client.json');

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_NOT_ALLOWED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenADenylistedSoftwareIdWithNoBlockedClientOfItsFamilyWhenAMetadataDocumentNamesItThenItIsRefusedAndNoClientIsCreated(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_SOFTWARE_IDS, [static::SOFTWARE_ID]);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse(softwareId: static::SOFTWARE_ID));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_SOFTWARE_ID_BLOCKED, $authorizationResponseTransfer->getFailureReason());
        $this->assertNull($this->tester->findClientOrNull(static::CLIENT_ID_URL));
    }

    public function testGivenABlockedClientOfASoftwareIdFamilyWhenAMetadataDocumentNamesThatSoftwareIdThenItIsRefusedAndNoClientIsCreated(): void
    {
        // Arrange
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => static::SOFTWARE_ID]);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions((new AgenticCommerceClientConditionsTransfer())->addSoftwareId(static::SOFTWARE_ID)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse(softwareId: static::SOFTWARE_ID));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_SOFTWARE_ID_BLOCKED, $authorizationResponseTransfer->getFailureReason());
        $this->assertNull($this->tester->findClientOrNull(static::CLIENT_ID_URL));
    }

    public function testGivenADenylistedSoftwareIdWithNoBlockedClientOfItsFamilyWhenAClientRegistersThenItIsRefused(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_SOFTWARE_IDS, [static::SOFTWARE_ID]);

        // Act
        $registrationResponseTransfer = $this->registerDynamicClient(static::SOFTWARE_ID);

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
    }

    public function testGivenADenylistedSoftwareIdWhenAClientRegistersWithAnotherSoftwareIdThenItIsAccepted(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_SOFTWARE_IDS, [static::SOFTWARE_ID]);

        // Act
        $registrationResponseTransfer = $this->registerDynamicClient('another-agent');

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    public function testGivenTheFetchBucketOfAHostIsFullWhenAClientIdOnThatHostIsResolvedThenNothingIsFetched(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::RATE_LIMIT_ENABLED, true);
        $this->tester->fillRateLimitBucket(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_METADATA_FETCH);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse());

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization(static::CLIENT_ID_URL);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_RATE_LIMITED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }
}
