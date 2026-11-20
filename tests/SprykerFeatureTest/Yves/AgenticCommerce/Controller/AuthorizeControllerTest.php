<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Controller\AuthorizeController;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Controller
 * @group AuthorizeControllerTest
 * Add your own group annotations below this line
 */
class AuthorizeControllerTest extends Unit
{
    protected const string CLIENT_ID = 'procure-assist';

    protected const string REDIRECT_URI = 'https://app.example/callback';

    protected const string VALIDATED_REDIRECT_URI = 'https://app.example/validated-callback';

    protected const string STATE = 'state-123';

    protected const string ENCODED_ISSUER = 'https%3A%2F%2Fglue.shop.test';

    protected const string RESOURCE_URI = 'https://glue.shop.test/agentic-commerce';

    protected const string TRUST_LEVEL_UNVERIFIED = 'unverified';

    protected const string TRUST_LEVEL_DOMAIN_VERIFIED = 'domain_verified';

    protected const string TRUST_LEVEL_VERIFIED = 'verified';

    protected const string UPPER_CASE_AUTHORIZATION_ENDPOINT = 'https://SHOP.Test/connect/authorize';

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected CustomerClientInterface&MockObject $customerClientMock;

    protected AgenticCommerceFactory $agenticCommerceFactory;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $this->customerClientMock = $this->createMock(CustomerClientInterface::class);
        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $this->customerClientMock,
        ]);
    }

    public function testGivenAnotherStoreHostWhenAuthorizingThenThePageIsNotFound(): void
    {
        // Assert
        $this->expectException(NotFoundHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceAuthorizationRequest');

        // Act
        $this->createController()->indexAction($this->tester->createRequest($this->createQuery(), [], 'at.shop.test'));
    }

    public function testGivenAnIssuerOnAnotherHostThanTheAuthorizationEndpointWhenAuthorizingOnTheAuthorizationEndpointHostThenThePageIsServed(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, 'https://glue.shop.test');
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery(), [], 'shop.test'));

        // Assert
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_ERROR, $view->getTemplate());
    }

    public function testGivenNoIssuerIsConfiguredWhenAuthorizingThenThePageIsNotFound(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceAuthorizationRequest');

        // Act
        $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));
    }

    public function testGivenNoPublicKeyIsConfiguredWhenAuthorizingThenThePageIsNotFound(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::PUBLIC_KEY, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('validateAgenticCommerceAuthorizationRequest');

        // Act
        $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));
    }

    public function testGivenACompleteAuthorizationQueryWhenAuthorizingThenEveryQueryParameterIsPassedToTheValidation(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceAuthorizationRequest')
            ->with($this->callback(static fn (AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer): bool => $agenticCommerceAuthorizationRequestTransfer->getClientId() === static::CLIENT_ID
                && $agenticCommerceAuthorizationRequestTransfer->getRedirectUri() === static::REDIRECT_URI
                && $agenticCommerceAuthorizationRequestTransfer->getResponseType() === 'code'
                && $agenticCommerceAuthorizationRequestTransfer->getScope() === 'catalog:read cart:read'
                && $agenticCommerceAuthorizationRequestTransfer->getState() === static::STATE
                && $agenticCommerceAuthorizationRequestTransfer->getCodeChallenge() === str_repeat('a', 43)
                && $agenticCommerceAuthorizationRequestTransfer->getCodeChallengeMethod() === 'S256'
                && $agenticCommerceAuthorizationRequestTransfer->getResource() === static::RESOURCE_URI))
            ->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));
    }

    public function testGivenAnAuthorizationDetailsParameterWhenAuthorizingThenTheRawValueIsPassedToTheValidation(): void
    {
        // Arrange
        $authorizationDetails = '[{"type":"https://spryker.com/oauth/authorization-details/order-budget","amount":10000,"currency":"EUR","period":"week"}]';

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceAuthorizationRequest')
            ->with($this->callback(static fn (AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer): bool => $agenticCommerceAuthorizationRequestTransfer->getAuthorizationDetails() === $authorizationDetails))
            ->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['authorization_details' => $authorizationDetails] + $this->createQuery()));
    }

    public function testGivenNoAuthorizationDetailsParameterWhenAuthorizingThenNoAuthorizationDetailsArePassedToTheValidation(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceAuthorizationRequest')
            ->with($this->callback(static fn (AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer): bool => $agenticCommerceAuthorizationRequestTransfer->getAuthorizationDetails() === null))
            ->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));
    }

    public function testGivenInvalidAuthorizationDetailsWhenAuthorizingThenTheClientIsRedirectedWithInvalidAuthorizationDetails(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')
            ->willReturn($this->createErrorResponse('invalid_authorization_details', true));

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['authorization_details' => 'not json'] + $this->createQuery()));

        // Assert
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_RESPONSE, $response->getTemplate());
        $this->assertStringStartsWith(static::REDIRECT_URI . '?error=invalid_authorization_details&', $response->getData()['redirectUrl']);
    }

    public function testGivenAQueryParameterSentAsAnArrayWhenAuthorizingThenTheParameterIsIgnored(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('validateAgenticCommerceAuthorizationRequest')
            ->with($this->callback(static fn (AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer): bool => $agenticCommerceAuthorizationRequestTransfer->getClientId() === null))
            ->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['client_id' => [static::CLIENT_ID]] + $this->createQuery()));
    }

    public function testGivenAnUnknownClientWhenAuthorizingThenTheNotAllowedErrorPageIsShownWithoutARedirect(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['client_id' => 'unknown-client'] + $this->createQuery()));

        // Assert
        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_ERROR, $response->getTemplate());
        $this->assertSame(AuthorizeController::ERROR_NOT_ALLOWED, $response->getData()['authorizationError']);
        $this->assertArrayNotHasKey('redirectUrl', $response->getData());
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findPendingAuthorizationRequestId());
    }

    public function testGivenAnUnregisteredRedirectUriWhenAuthorizingThenTheInvalidLinkErrorPageIsShownWithoutARedirect(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createErrorResponse('invalid_request', false));

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_ERROR, $response->getTemplate());
        $this->assertSame(AuthorizeController::ERROR_INVALID_LINK, $response->getData()['authorizationError']);
        $this->assertArrayNotHasKey('redirectUrl', $response->getData());
    }

    public function testGivenPlainPkceWhenAuthorizingThenTheClientIsRedirectedWithInvalidRequest(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')
            ->with($this->callback(static fn (AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer): bool => $agenticCommerceAuthorizationRequestTransfer->getCodeChallengeMethod() === 'plain'))
            ->willReturn($this->createErrorResponse('invalid_request', true));

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['code_challenge_method' => 'plain'] + $this->createQuery()));

        // Assert
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_RESPONSE, $response->getTemplate());
        $this->assertStringStartsWith(static::REDIRECT_URI . '?error=invalid_request&', $response->getData()['redirectUrl']);
    }

    public function testGivenAMissingResourceWhenAuthorizingThenTheClientIsRedirectedWithInvalidTarget(): void
    {
        // Arrange
        $query = $this->createQuery();
        unset($query['resource']);
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')
            ->with($this->callback(static fn (AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer): bool => $agenticCommerceAuthorizationRequestTransfer->getResource() === null))
            ->willReturn($this->createErrorResponse('invalid_target', true));

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest($query));

        // Assert
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_RESPONSE, $response->getTemplate());
        $this->assertStringStartsWith(static::REDIRECT_URI . '?error=invalid_target&', $response->getData()['redirectUrl']);
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findPendingAuthorizationRequestId());
    }

    public function testGivenARedirectableErrorWhenAuthorizingThenTheErrorReturnsToTheClientWithStateAndIssuer(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createErrorResponse('invalid_scope', true));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertSame(
            sprintf('%s?error=invalid_scope&error_description=Scope%%20not%%20allowed.&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
    }

    public function testGivenAnUnverifiedClientWithARedirectableErrorWhenAuthorizingThenThePageDoesNotForwardByItself(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn(
            $this->createErrorResponse('invalid_scope', true)->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setTrustLevel(static::TRUST_LEVEL_UNVERIFIED)),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertInvalidLinkErrorPageLinksBackToTheClient($view);
    }

    public function testGivenADomainVerifiedClientWithARedirectableErrorWhenAuthorizingThenThePageDoesNotForwardByItself(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn(
            $this->createErrorResponse('invalid_scope', true)->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setTrustLevel(static::TRUST_LEVEL_DOMAIN_VERIFIED)),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertInvalidLinkErrorPageLinksBackToTheClient($view);
    }

    public function testGivenARedirectableErrorWithoutAClientWhenAuthorizingThenThePageDoesNotForwardByItself(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn(
            $this->createErrorResponse('invalid_scope', true)->setAgenticCommerceClient(null),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertInvalidLinkErrorPageLinksBackToTheClient($view);
    }

    public function testGivenAVerifiedClientWithARedirectableErrorWhenAuthorizingThenThePageForwardsToTheClientByItself(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn(
            $this->createErrorResponse('invalid_scope', true)->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setTrustLevel(static::TRUST_LEVEL_VERIFIED)),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertStringStartsWith(static::REDIRECT_URI . '?error=invalid_scope&', $view->getData()['redirectUrl']);
    }

    public function testGivenAnAuthorizationEndpointConfiguredWithAnUpperCaseHostWhenAuthorizingOnTheLowerCaseHostThenThePageIsServed(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::AUTHORIZATION_ENDPOINT, static::UPPER_CASE_AUTHORIZATION_ENDPOINT);
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createErrorResponse('invalid_client', false));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery(), [], 'shop.test'));

        // Assert
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_ERROR, $view->getTemplate());
    }

    public function testGivenARedirectableErrorWhoseValidatedRequestCarriesAnotherRedirectUriWhenAuthorizingThenTheErrorReturnsToTheValidatedRedirectUri(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn(
            $this->createErrorResponse('invalid_scope', true)->setAuthorizationRequest(
                (new AgenticCommerceAuthorizationRequestTransfer())->setRedirectUri(static::VALIDATED_REDIRECT_URI)->setState(static::STATE),
            ),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertStringStartsWith(static::VALIDATED_REDIRECT_URI . '?error=invalid_scope&', $view->getData()['redirectUrl']);
    }

    public function testGivenASignedOutCustomerWhenAuthorizingThenTheCustomerIsSentToLoginWithTheRequestPending(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createValidResponse());
        $this->customerClientMock->method('isLoggedIn')->willReturn(false);

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
        $authorizationRequestParker = $this->agenticCommerceFactory->createAuthorizationRequestParker();
        $authorizationRequestId = (string)$authorizationRequestParker->findPendingAuthorizationRequestId();
        $this->assertSame(static::CLIENT_ID, $authorizationRequestParker->findParkedAuthorization($authorizationRequestId)?->getAuthorizationRequestOrFail()->getClientId());
    }

    public function testGivenASignedInCustomerWhenAuthorizingThenTheValidatedRequestIsParkedAndTheCustomerIsSentToConsent(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('validateAgenticCommerceAuthorizationRequest')->willReturn($this->createValidResponse());
        $this->customerClientMock->method('isLoggedIn')->willReturn(true);

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest($this->createQuery()));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertMatchesRegularExpression('#^/agentic-commerce/consent\?request=[0-9a-f]{64}$#', $response->getTargetUrl());
        $authorizationRequestId = substr($response->getTargetUrl(), -64);
        $authorizationRequestParker = $this->agenticCommerceFactory->createAuthorizationRequestParker();
        $this->assertTrue($authorizationRequestParker->findParkedAuthorization($authorizationRequestId)?->getAuthorizationRequestOrFail()->getIsLoopbackRedirect());
        $this->assertNull($authorizationRequestParker->findPendingAuthorizationRequestId());
    }

    protected function createController(): AuthorizeController
    {
        return $this->tester->createController(AuthorizeController::class, $this->agenticCommerceFactory);
    }

    /**
     * @return array<string, string>
     */
    protected function createQuery(): array
    {
        return [
            'client_id' => static::CLIENT_ID,
            'redirect_uri' => static::REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'catalog:read cart:read',
            'state' => static::STATE,
            'code_challenge' => str_repeat('a', 43),
            'code_challenge_method' => 'S256',
            'resource' => static::RESOURCE_URI,
        ];
    }

    protected function createErrorResponse(string $errorCode, bool $isRedirectable): AgenticCommerceAuthorizationResponseTransfer
    {
        return (new AgenticCommerceAuthorizationResponseTransfer())
            ->setIsValid(false)
            ->setIsRedirectable($isRedirectable)
            ->setErrorCode($errorCode)
            ->setErrorDescription('Scope not allowed.')
            ->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setTrustLevel(static::TRUST_LEVEL_VERIFIED));
    }

    protected function assertInvalidLinkErrorPageLinksBackToTheClient(View $view): void
    {
        $this->assertSame(AuthorizeController::TEMPLATE_AUTHORIZATION_ERROR, $view->getTemplate());
        $this->assertSame(AuthorizeController::ERROR_INVALID_LINK, $view->getData()['authorizationError']);
        $this->assertStringStartsWith(static::REDIRECT_URI . '?error=invalid_scope&', $view->getData()['redirectUrl']);
    }

    protected function createValidResponse(): AgenticCommerceAuthorizationResponseTransfer
    {
        return (new AgenticCommerceAuthorizationResponseTransfer())
            ->setIsValid(true)
            ->setIsRedirectable(true)
            ->setGrantableScopes(['catalog:read'])
            ->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setName('Procure Assist'))
            ->setAuthorizationRequest(
                (new AgenticCommerceAuthorizationRequestTransfer())
                    ->setClientId(static::CLIENT_ID)
                    ->setRedirectUri(static::REDIRECT_URI)
                    ->setIsLoopbackRedirect(true),
            );
    }
}
