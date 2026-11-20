<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationEntryPoint;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticator;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceBearerTokenExtractor;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommercePathMatcher;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceProtectedResourceResolver;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceScopeRoleMapper;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Glue\AgenticCommerce\AgenticCommerceGlueTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceAuthenticatorTest
 * Add your own group annotations below this line
 */
class AgenticCommerceAuthenticatorTest extends Unit
{
    protected const string ISSUER = 'https://glue-storefront.shop.test';

    protected const string PROTECTED_RESOURCE_METADATA_URL = self::ISSUER . '/.well-known/oauth-protected-resource/agentic-commerce';

    protected const string PROTECTED_PATH = '/agentic-commerce/principal';

    protected const string PRINCIPAL_RESOURCE_URI = 'https://glue-storefront.shop.test/agentic-commerce';

    protected const string MCP_RESOURCE_URI = 'https://glue.example/mcp';

    protected const string PRINCIPAL_CHALLENGE_SCOPE = 'scope="agentic-commerce:principal-read"';

    protected const string STORE_NAME = 'DE';

    protected const string OTHER_STORE_NAME = 'AT';

    protected const string CLIENT_ID = 'procurement-assistant';

    protected const string CUSTOMER_REFERENCE = 'DE--7';

    protected const string COMPANY_USER_REFERENCE = 'company-user-uuid';

    protected const string CONSENT_UUID = 'f3a1d2c4-0000-4000-8000-000000000001';

    protected const string TOKEN_IDENTIFIER = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4';

    protected const string ACCESS_TOKEN = 'delegated-access-token';

    protected const string SCOPE = 'agentic-commerce:principal-read';

    protected const string SUBJECT_KEY_CUSTOMER_REFERENCE = 'customer_reference';

    protected AgenticCommerceGlueTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected AgenticCommerceServiceInterface $agenticCommerceService;

    protected function _before(): void
    {
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, static::ISSUER);
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $this->agenticCommerceService = $this->tester->createAgenticCommerceService([
            ['name' => 'principal', 'pathPrefix' => '/agentic-commerce', 'resourceUri' => static::PRINCIPAL_RESOURCE_URI, 'scopes' => [static::SCOPE]],
            ['name' => 'mcp', 'pathPrefix' => '/mcp', 'resourceUri' => static::MCP_RESOURCE_URI, 'scopes' => ['catalog:read', 'cart:read']],
        ]);
    }

    public function testGivenNoAuthorizationHeaderOnAProtectedPathWhenTheFirewallAsksThenTheAuthenticatorSupportsTheRequest(): void
    {
        // Arrange
        $request = Request::create('/mcp', Request::METHOD_POST);

        // Act
        $isSupported = $this->createAuthenticator()->supports($request);

        // Assert
        $this->assertTrue($isSupported);
    }

    public function testGivenNoAuthorizationHeaderWhenAuthenticationFailsThenTheChallengeCarriesMetadataUrlAndScopes(): void
    {
        // Arrange
        $request = Request::create('/mcp', Request::METHOD_POST);
        $authenticator = $this->createAuthenticator();

        // Act
        $response = $authenticator->onAuthenticationFailure($request, new AuthenticationException());

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('Bearer resource_metadata="https://glue.example/.well-known/oauth-protected-resource/mcp", scope="catalog:read cart:read"', $response->headers->get('WWW-Authenticate'));
    }

    public function testGivenTheTokenEndpointWhenTheFirewallAsksThenTheAuthenticatorDoesNotSupportIt(): void
    {
        // Arrange
        $request = Request::create('/oauth/token', Request::METHOD_POST);

        // Act
        $isSupported = $this->createAuthenticator()->supports($request);

        // Assert
        $this->assertFalse($isSupported);
    }

    public function testGivenATokenForTheResourceOfTheCalledPathAndTheCurrentStoreWhenAuthenticatingThenTheUserCarriesTheResourceName(): void
    {
        // Arrange
        $request = $this->createRequest('/mcp/tools');
        $this->mockValidToken(audience: static::MCP_RESOURCE_URI);
        $this->mockConsentUsage(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);

        // Act
        $agenticCommerceApiUser = $this->createAuthenticator()->authenticate($request)->getUser();

        // Assert
        $this->assertInstanceOf(AgenticCommerceApiUser::class, $agenticCommerceApiUser);
        $this->assertSame('mcp', $agenticCommerceApiUser->getResourceName());
    }

    public function testGivenATokenForAnotherDeclaredResourceWhenAuthenticatingThenAuthenticationFailsBeforeTheConsentIsChecked(): void
    {
        // Arrange
        $request = $this->createRequest('/mcp');
        $this->mockValidToken(audience: static::PRINCIPAL_RESOURCE_URI);
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($request);
    }

    /**
     * @dataProvider provideRequestPaths
     */
    public function testGivenAProtectedResourcePathOrAPublicOauthProtocolPathWhenAskingWhetherTheAuthenticatorSupportsItThenOnlyTheProtectedResourcePathIsSupported(
        string $path,
        bool $hasAuthorizationHeader,
        bool $expectedIsSupported
    ): void {
        // Arrange
        $request = Request::create($path);

        if ($hasAuthorizationHeader) {
            $request->headers->set('Authorization', 'Bearer ' . static::ACCESS_TOKEN);
        }

        // Act
        $isSupported = $this->createAuthenticator()->supports($request);

        // Assert
        $this->assertSame($expectedIsSupported, $isSupported);
    }

    /**
     * @return array<string, array{string, bool, bool}>
     */
    public static function provideRequestPaths(): array
    {
        return [
            'protected resource with token' => [static::PROTECTED_PATH, true, true],
            'protected resource without token' => [static::PROTECTED_PATH, false, true],
            'token endpoint' => ['/oauth/token', true, false],
            'revocation endpoint' => ['/oauth/revoke', true, false],
            'registration endpoint' => ['/oauth/register', true, false],
            'authorization server metadata' => ['/.well-known/oauth-authorization-server', true, false],
            'protected resource metadata' => ['/.well-known/oauth-protected-resource/agentic-commerce', true, false],
            'percent-encoded token endpoint' => ['/oauth%2Ftoken', true, false],
            'percent-encoded authorization server metadata' => ['/.well-known/oauth%2Dauthorization-server', true, false],
            'percent-encoded protected resource' => ['/agentic%2Dcommerce/principal', true, true],
        ];
    }

    public function testGivenAValidTokenOfACompanyUserConsentWhenAuthenticatingThenTheUserCarriesTheRolesOfItsScopesAndPrincipalType(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->mockConsentUsage(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER);

        // Act
        $agenticCommerceApiUser = $this->createAuthenticator()->authenticate($this->createRequest())->getUser();

        // Assert
        $this->assertEqualsCanonicalizing(
            ['ROLE_AGENTIC_COMMERCE', 'ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ', 'ROLE_CUSTOMER', 'ROLE_COMPANY_USER', 'ROLE_USER'],
            $agenticCommerceApiUser->getRoles(),
        );
    }

    public function testGivenATokenIssuedBeforeItsConsentWasNarrowedWhenAuthenticatingThenTheUserCarriesNoRoleOfTheDroppedScope(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->agenticCommerceClientMock->method('validateAgenticCommerceConsentUsage')
            ->willReturn($this->createConsentUsageResponse(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, []));

        // Act
        $agenticCommerceApiUser = $this->createAuthenticator()->authenticate($this->createRequest())->getUser();

        // Assert
        $this->assertEqualsCanonicalizing(['ROLE_AGENTIC_COMMERCE', 'ROLE_CUSTOMER', 'ROLE_USER'], $agenticCommerceApiUser->getRoles());
    }

    public function testGivenAValidTokenCarryingAConsentClientCustomerAndStoreWhenAuthenticatingThenTheConsentUsageIsValidatedForExactlyThoseValues(): void
    {
        // Arrange
        $this->mockValidToken();

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceConsentUsage')
            ->with($this->callback(static fn (AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer): bool => $agenticCommerceConsentUsageRequestTransfer->getConsentUuid() === static::CONSENT_UUID
                && $agenticCommerceConsentUsageRequestTransfer->getClientIdentifier() === static::CLIENT_ID
                && $agenticCommerceConsentUsageRequestTransfer->getCustomerReference() === static::CUSTOMER_REFERENCE
                && $agenticCommerceConsentUsageRequestTransfer->getStoreName() === static::STORE_NAME))
            ->willReturn($this->createConsentUsageResponse(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER));

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest())->getUser();
    }

    public function testGivenTheBearerSchemeInLowerCaseWhenAuthenticatingThenTheTokenIsAccepted(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->mockConsentUsage(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);
        $request = Request::create(static::PROTECTED_PATH);
        $request->headers->set('Authorization', 'bearer ' . static::ACCESS_TOKEN);

        // Act
        $agenticCommerceApiUser = $this->createAuthenticator()->authenticate($request)->getUser();

        // Assert
        $this->assertInstanceOf(AgenticCommerceApiUser::class, $agenticCommerceApiUser);
    }

    public function testGivenATokenTheAccessTokenValidationRejectsWhenAuthenticatingThenAuthenticationFails(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAccessToken')
            ->willReturn((new AgenticCommerceAccessTokenValidationResponseTransfer())->setIsValid(false));

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenATokenOfAnotherStoreWhenAuthenticatingThenAuthenticationFailsBeforeTheConsentIsChecked(): void
    {
        // Arrange
        $this->mockValidToken(static::OTHER_STORE_NAME);
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenATokenWhoseConsentIsNoLongerUsableWhenAuthenticatingThenAuthenticationFails(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->mockConsentUsage(false, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenATokenWhoseSubjectNamesNoCustomerWhenAuthenticatingThenAuthenticationFails(): void
    {
        // Arrange
        $this->mockValidToken(static::STORE_NAME, '{}');
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenNoAuthorizationHeaderWhenAuthenticatingThenAuthenticationFailsWithoutAValidation(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceAccessToken');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate(Request::create(static::PROTECTED_PATH));
    }

    public function testGivenABasicAuthorizationHeaderWhenAuthenticatingThenAuthenticationFails(): void
    {
        // Arrange
        $request = Request::create(static::PROTECTED_PATH);
        $request->headers->set('Authorization', 'Basic dXNlcjpwYXNz');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($request);
    }

    public function testGivenACallCarryingABearerTokenWhenAnsweringTheFailedAuthenticationThenTheResponseIs401WithAnInvalidTokenChallengePointingToTheMetadata(): void
    {
        // Act
        $response = $this->createAuthenticator()->onAuthenticationFailure($this->createRequest(), new AuthenticationException());

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response?->getStatusCode());
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", error="invalid_token", %s', static::PROTECTED_RESOURCE_METADATA_URL, static::PRINCIPAL_CHALLENGE_SCOPE),
            $response->headers->get('WWW-Authenticate'),
        );
        $this->assertSame('invalid_token', json_decode((string)$response->getContent(), true)['error']);
    }

    public function testGivenACallWithoutATokenWhenAnsweringTheFailedAuthenticationThenTheChallengeCarriesNoErrorCode(): void
    {
        // Act
        $response = $this->createAuthenticator()->onAuthenticationFailure(Request::create(static::PROTECTED_PATH), new AuthenticationException());

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response?->getStatusCode());
        $this->assertSame(
            sprintf('Bearer resource_metadata="%s", %s', static::PROTECTED_RESOURCE_METADATA_URL, static::PRINCIPAL_CHALLENGE_SCOPE),
            $response->headers->get('WWW-Authenticate'),
        );
    }

    public function testGivenABasicAuthorizationHeaderWhenAuthenticationFailsThenTheChallengeCarriesNoErrorCode(): void
    {
        // Arrange
        $request = Request::create(static::PROTECTED_PATH);
        $request->headers->set('Authorization', 'Basic dXNlcjpwYXNz');

        // Act
        $response = $this->createAuthenticator()->onAuthenticationFailure($request, new AuthenticationException());

        // Assert
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response?->getStatusCode());
        $this->assertStringNotContainsString('error=', (string)$response->headers->get('WWW-Authenticate'));
        $this->assertArrayNotHasKey('error', json_decode((string)$response->getContent(), true));
    }

    public function testGivenATokenWithAnAudienceOfNoDeclaredResourceWhenAuthenticatingThenAuthenticationFailsBeforeTheConsentIsChecked(): void
    {
        // Arrange
        $this->mockValidToken(audience: 'https://other.example/resource');
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenATokenWhoseSubjectIsNotJsonWhenAuthenticatingThenAuthenticationFails(): void
    {
        // Arrange
        $this->mockValidToken(subject: 'not-json');
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenATokenWhoseSubjectCarriesAnEmptyCustomerReferenceWhenAuthenticatingThenAuthenticationFails(): void
    {
        // Arrange
        $this->mockValidToken(subject: json_encode([static::SUBJECT_KEY_CUSTOMER_REFERENCE => ''], JSON_THROW_ON_ERROR));
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');

        // Assert
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenAConsentGrantingMoreScopesThanTheTokenCarriesWhenAuthenticatingThenTheUserCarriesOnlyTheRolesOfTheTokenScopes(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->agenticCommerceClientMock->method('validateAgenticCommerceConsentUsage')
            ->willReturn($this->createConsentUsageResponse(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [static::SCOPE, 'cart:read']));

        // Act
        $agenticCommerceApiUser = $this->createAuthenticator()->authenticate($this->createRequest())->getUser();

        // Assert
        $this->assertContains('ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ', $agenticCommerceApiUser->getRoles());
        $this->assertNotContains('ROLE_SCOPE_CART_READ', $agenticCommerceApiUser->getRoles());
    }

    public function testGivenAnAuthenticatedDelegatedUserWhenAuthenticationSucceedsThenTheClientHoldsTheConsentUuid(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->mockConsentUsage(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);
        $authenticator = $this->createAuthenticator();
        $agenticCommerceApiUser = $authenticator->authenticate($this->createRequest())->getUser();
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn($agenticCommerceApiUser);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())->method('setAuthenticatedConsentUuid')->with(static::CONSENT_UUID);

        // Act
        $response = $authenticator->onAuthenticationSuccess($this->createRequest(), $tokenMock, 'agentic_commerce');

        // Assert
        $this->assertNull($response);
    }

    public function testGivenAUserOfAnotherKindWhenAuthenticationSucceedsThenTheClientHoldsNoConsentUuid(): void
    {
        // Arrange
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->method('getUser')->willReturn(null);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('setAuthenticatedConsentUuid');

        // Act
        $this->createAuthenticator()->onAuthenticationSuccess($this->createRequest(), $tokenMock, 'agentic_commerce');
    }

    public function testGivenACachedValidUsageOfTheTokensClientAndCustomerWhenAuthenticatingThenZedIsNotAsked(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->agenticCommerceClientMock->method('findAgenticCommerceConsentUsageInCache')
            ->with(static::STORE_NAME, static::CONSENT_UUID)
            ->willReturn($this->createCachedConsentUsageResponse());
        $this->agenticCommerceClientMock->method('shouldTouchAgenticCommerceConsentLastUsedAt')->willReturn(false);
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceConsentUsage');
        $this->agenticCommerceClientMock->expects($this->never())->method('saveAgenticCommerceConsentUsageToCache');

        // Act
        $agenticCommerceApiUser = $this->createAuthenticator()->authenticate($this->createRequest())->getUser();

        // Assert
        $this->assertContains('ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ', $agenticCommerceApiUser->getRoles());
    }

    public function testGivenNoCachedUsageWhenZedFindsTheConsentUsableThenTheUsageIsCachedForTheTokensStore(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->agenticCommerceClientMock->method('findAgenticCommerceConsentUsageInCache')->willReturn(null);
        $agenticCommerceConsentUsageResponseTransfer = $this->createCachedConsentUsageResponse();
        $this->agenticCommerceClientMock->expects($this->once())->method('validateAgenticCommerceConsentUsage')->willReturn($agenticCommerceConsentUsageResponseTransfer);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('saveAgenticCommerceConsentUsageToCache')
            ->with(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $agenticCommerceConsentUsageResponseTransfer);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenACachedValidUsageWhoseLastUsedAtWriteIsDueWhenAuthenticatingThenZedIsAsked(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->agenticCommerceClientMock->method('findAgenticCommerceConsentUsageInCache')->willReturn($this->createCachedConsentUsageResponse());
        $this->agenticCommerceClientMock->method('shouldTouchAgenticCommerceConsentLastUsedAt')->willReturn(true);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceConsentUsage')
            ->willReturn($this->createCachedConsentUsageResponse());

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenACachedUsageOfAnotherClientWhenAuthenticatingThenZedIsAsked(): void
    {
        // Arrange
        $this->mockValidToken();
        $cachedConsentUsageResponseTransfer = $this->createCachedConsentUsageResponse();
        $cachedConsentUsageResponseTransfer->getAgenticCommerceConsentOrFail()->setClientIdentifier('another-client');
        $this->agenticCommerceClientMock->method('findAgenticCommerceConsentUsageInCache')->willReturn($cachedConsentUsageResponseTransfer);
        $this->agenticCommerceClientMock->method('shouldTouchAgenticCommerceConsentLastUsedAt')->willReturn(false);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceConsentUsage')
            ->willReturn($this->createConsentUsageResponse(false, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER));
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenZedRefusesTheConsentWhenAuthenticatingThenNothingIsCached(): void
    {
        // Arrange
        $this->mockValidToken();
        $this->mockConsentUsage(false, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('saveAgenticCommerceConsentUsageToCache');
        $this->expectException(AuthenticationException::class);

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    public function testGivenTheConsentCacheIsSwitchedOffWhenAuthenticatingThenTheCacheIsNeitherReadNorFilled(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CONSENT_CACHE_TTL_SECONDS, 0);
        $this->mockValidToken();
        $this->mockConsentUsage(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('findAgenticCommerceConsentUsageInCache');
        $this->agenticCommerceClientMock->expects($this->never())->method('saveAgenticCommerceConsentUsageToCache');

        // Act
        $this->createAuthenticator()->authenticate($this->createRequest());
    }

    protected function mockValidToken(string $storeName = self::STORE_NAME, ?string $subject = null, string $audience = self::PRINCIPAL_RESOURCE_URI): void
    {
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAccessToken')
            ->with(static::ACCESS_TOKEN)
            ->willReturn(
                (new AgenticCommerceAccessTokenValidationResponseTransfer())
                    ->setIsValid(true)
                    ->setAgenticCommerceAccessToken(
                        (new AgenticCommerceAccessTokenTransfer())
                            ->setAudience([$audience])
                            ->setClientId(static::CLIENT_ID)
                            ->setSubject($subject ?? json_encode([static::SUBJECT_KEY_CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE], JSON_THROW_ON_ERROR))
                            ->setStoreName($storeName)
                            ->setConsentUuid(static::CONSENT_UUID)
                            ->setTokenIdentifier(static::TOKEN_IDENTIFIER)
                            ->setScopes([SharedAgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER, static::SCOPE]),
                    ),
            );
    }

    protected function mockConsentUsage(bool $isUsable, string $principalType): void
    {
        $this->agenticCommerceClientMock->method('validateAgenticCommerceConsentUsage')
            ->willReturn($this->createConsentUsageResponse($isUsable, $principalType));
    }

    /**
     * @param list<string> $grantedScopes
     */
    protected function createConsentUsageResponse(
        bool $isUsable,
        string $principalType,
        array $grantedScopes = [self::SCOPE]
    ): AgenticCommerceConsentUsageResponseTransfer {
        $agenticCommerceConsentUsageResponseTransfer = (new AgenticCommerceConsentUsageResponseTransfer())->setIsValid($isUsable);

        if (!$isUsable) {
            return $agenticCommerceConsentUsageResponseTransfer;
        }

        return $agenticCommerceConsentUsageResponseTransfer->setAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())
                ->setUuid(static::CONSENT_UUID)
                ->setPrincipalType($principalType)
                ->setPrincipalReference(static::COMPANY_USER_REFERENCE)
                ->setStoreName(static::STORE_NAME)
                ->setGrantedScopes($grantedScopes),
        );
    }

    protected function createCachedConsentUsageResponse(): AgenticCommerceConsentUsageResponseTransfer
    {
        $agenticCommerceConsentUsageResponseTransfer = $this->createConsentUsageResponse(true, SharedAgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER);
        $agenticCommerceConsentUsageResponseTransfer->getAgenticCommerceConsentOrFail()
            ->setClientIdentifier(static::CLIENT_ID)
            ->setCustomerReference(static::CUSTOMER_REFERENCE);

        return $agenticCommerceConsentUsageResponseTransfer;
    }

    protected function createRequest(string $path = self::PROTECTED_PATH): Request
    {
        $request = Request::create($path);
        $request->headers->set('Authorization', 'Bearer ' . static::ACCESS_TOKEN);

        return $request;
    }

    protected function createAuthenticator(string $currentStoreName = self::STORE_NAME): AgenticCommerceAuthenticator
    {
        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturn((new StoreTransfer())->setName($currentStoreName));

        $agenticCommerceAuthenticationChallengeBuilder = new AgenticCommerceAuthenticationChallengeBuilder(new AgenticCommerceConfig(), $this->agenticCommerceService);

        return new AgenticCommerceAuthenticator(
            $this->agenticCommerceClientMock,
            $storeClientMock,
            new AgenticCommerceProtectedResourceResolver($this->agenticCommerceService, new NullLogger(), new AgenticCommerceConfig()),
            $agenticCommerceAuthenticationChallengeBuilder,
            new AgenticCommerceScopeRoleMapper($this->agenticCommerceService),
            new AgenticCommerceBearerTokenExtractor(),
            new AgenticCommerceAuthenticationEntryPoint($agenticCommerceAuthenticationChallengeBuilder),
            new AgenticCommercePathMatcher($this->agenticCommerceService),
            new AgenticCommerceConfig(),
        );
    }
}
