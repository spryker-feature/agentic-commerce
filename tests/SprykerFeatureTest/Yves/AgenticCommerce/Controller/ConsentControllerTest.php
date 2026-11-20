<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Controller;

use ArrayObject;
use Codeception\Test\Unit;
use DOMDocument;
use DOMXPath;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Controller\ConsentController;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Controller
 * @group ConsentControllerTest
 * Add your own group annotations below this line
 */
class ConsentControllerTest extends Unit
{
    protected const string FORM_NAME = 'agenticCommerceConsentForm';

    protected const string CUSTOMER_REFERENCE = 'DE--7';

    protected const string REDIRECT_URI = 'https://app.example/callback';

    protected const string STATE = 'state-123';

    protected const string ENCODED_ISSUER = 'https%3A%2F%2Fglue.shop.test';

    protected const string SCOPE_CATALOG_READ = 'catalog:read';

    protected const string SCOPE_CART_READ = 'cart:read';

    protected const string PRINCIPAL_TYPE_CUSTOMER = 'customer';

    protected const string PRINCIPAL_TYPE_COMPANY_USER = 'company_user';

    protected const string COMPANY_USER_UUID = 'company-user-uuid';

    protected const string OTHER_COMPANY_USER_UUID = 'other-company-user-uuid';

    protected const string GLOSSARY_KEY_PROVIDED_CATALOG_READ = 'catalog.consent.scope.read';

    protected const string TEMPLATE_LOOPBACK_REDIRECT_WARNING = '@AgenticCommerce/views/consent/loopback-redirect-warning.twig';

    protected const string GLOSSARY_KEY_LOOPBACK_REDIRECT_WARNING = 'agentic_commerce.consent.loopback_redirect_warning';

    protected const string OTHER_CUSTOMER_REFERENCE = 'DE--1';

    protected const string TEMPLATE_CONSTRAINT_FIELDS = '@AgenticCommerce/views/consent/constraint-fields.twig';

    protected const string TEMPLATE_CLIENT_TRUST_LEVEL = '@AgenticCommerce/views/consent/client-trust-level.twig';

    protected const string GLOSSARY_KEY_TRUST_DOMAIN_VERIFIED = 'agentic_commerce.consent.trust.domain_verified';

    protected const string GLOSSARY_KEY_TRUST_UNVERIFIED = 'agentic_commerce.consent.trust.unverified';

    protected const string TEMPLATE_CLIENT_IDENTITY = '@AgenticCommerce/views/consent/client-identity.twig';

    protected const string GLOSSARY_KEY_CLIENT_URI = 'agentic_commerce.consent.client_uri';

    protected const string CLIENT_NAME = 'Procure Assist';

    protected const string CLIENT_URI = 'https://www.procure-assist.example/about';

    protected const string LOGO_URI = 'https://cdn.procure-assist.example/logo.png';

    protected const string SCOPE_ORDER_PLACE = 'order:place';

    protected const string CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT = 'order_total_limit';

    protected const string GLOSSARY_KEY_ORDER_TOTAL_LIMIT = 'agentic_commerce.constraint.order_total_limit';

    protected const string GLOSSARY_KEY_ORDER_TOTAL_LIMIT_HINT = 'agentic_commerce.constraint.order_total_limit.hint';

    protected const string GLOSSARY_KEY_CONSTRAINT_REQUIRED = 'agentic_commerce.consent.validation.constraint_required';

    protected const string GLOSSARY_KEY_AMOUNT_POSITIVE = 'agentic_commerce.consent.validation.amount_positive';

    protected const string HINT_PARAMETER_LIMIT = '%limit%';

    protected const string INPUT_KIND_MONEY = 'money';

    protected const string INPUT_KIND_MONEY_PERIOD = 'money_period';

    protected const string INPUT_KIND_MULTI_SELECT = 'multi_select';

    protected const string INPUT_KIND_CATEGORY_TREE = 'category_tree';

    protected const string CONSTRAINT_TYPE_ORDER_BUDGET = 'order_budget';

    protected const string CONSTRAINT_TYPE_MERCHANT_ALLOWLIST = 'merchant_allowlist';

    protected const string CONSTRAINT_TYPE_UNSUPPORTED = 'unsupported_constraint';

    protected const string INPUT_KIND_UNSUPPORTED = 'unknown';

    protected const string CONSTRAINT_TYPE_SUPPORTED = 'supported_constraint';

    protected const string VALUE_KEY_SUPPORTED = 'supportedValues';

    protected const string CONSTRAINT_TYPE_CATEGORY_ALLOWLIST = 'category_allowlist';

    protected const string GLOSSARY_KEY_ORDER_BUDGET = 'agentic_commerce.constraint.order_budget';

    protected const string GLOSSARY_KEY_PERIOD_DAY = 'agentic_commerce.constraint.order_budget.period.day';

    protected const string GLOSSARY_KEY_PERIOD_WEEK = 'agentic_commerce.constraint.order_budget.period.week';

    protected const string GLOSSARY_KEY_PERIOD_MONTH = 'agentic_commerce.constraint.order_budget.period.month';

    protected const string GLOSSARY_KEY_REQUESTED_REMOVED = 'agentic_commerce.consent.constraint.requested_removed';

    protected const string GLOSSARY_KEY_WIDER_THAN_REQUESTED = 'agentic_commerce.consent.constraint.wider_than_requested';

    protected const string GLOSSARY_KEY_PERIOD_REQUIRED = 'agentic_commerce.consent.validation.period_required';

    protected const string GLOSSARY_KEY_PERIOD_PLACEHOLDER = 'agentic_commerce.consent.period.placeholder';

    protected const string MERCHANT_REFERENCE_ONE = 'MER000001';

    protected const string MERCHANT_REFERENCE_TWO = 'MER000002';

    protected const string CATEGORY_KEY_COMPUTER = 'computer';

    protected const string CATEGORY_KEY_NOTEBOOKS = 'notebooks';

    protected const string CATEGORY_KEY_TABLETS = 'tablets';

    protected const string CATEGORY_KEY_CAMERAS = 'cameras';

    protected const int BUDGET_IN_CENTS = 10000;

    protected const string BUDGET_AS_SUBMITTED = '100.00';

    protected const string NARROWER_BUDGET_AS_SUBMITTED = '60.00';

    protected const int NARROWER_BUDGET_IN_CENTS = 6000;

    protected const string CURRENCY_CODE = 'EUR';

    protected const string STORE_NAME = 'DE';

    protected const string OTHER_STORE_NAME = 'AT';

    protected const array FRACTION_DIGITS_BY_CURRENCY_CODE = [
        self::CURRENCY_CODE => 2,
        'JPY' => 0,
        'KWD' => 3,
    ];

    protected const int LIMIT_IN_CENTS = 5000;

    protected const string LIMIT_AS_SUBMITTED = '50.00';

    protected const string ZERO_LIMIT_AS_SUBMITTED = '0.00';

    protected const string NEGATIVE_LIMIT_AS_SUBMITTED = '-10.00';

    protected const string NON_NUMERIC_LIMIT_AS_SUBMITTED = 'abc';

    protected const string CURRENCY_CODE_WITHOUT_FRACTION_DIGITS = 'JPY';

    protected const string LIMIT_IN_YEN_AS_SUBMITTED = '5000';

    protected const int LIMIT_IN_YEN = 5000;

    protected const string CURRENCY_CODE_WITH_THREE_FRACTION_DIGITS = 'KWD';

    protected const string LIMIT_IN_DINAR_AS_SUBMITTED = '1.250';

    protected const int LIMIT_IN_FILS = 1250;

    protected const string UNCONFIGURED_DURATION_IN_DAYS = '5';

    protected const int ROLE_LIMIT_IN_CENTS = 30000;

    protected const int DURATION_IN_DAYS = 7;

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected CustomerClientInterface&MockObject $customerClientMock;

    protected AgenticCommerceFactory $agenticCommerceFactory;

    protected string $storeCurrencyCode = self::CURRENCY_CODE;

    protected CustomerTransfer $customerTransfer;

    protected AgenticCommercePrincipalCollectionTransfer $principalCandidates;

    /**
     * @var array<string, list<string>> Principal value to the scopes the filter stack lets through; a principal without an entry keeps every requested scope.
     */
    protected array $filteredScopesByPrincipalValue = [];

    /**
     * @var list<int>
     */
    protected array $consentDurationsInDays = [30, 7, 1];

    /**
     * @var list<\Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer>
     */
    protected array $constraintFieldRequestTransfers = [];

    protected ?int $roleLimitInCents = null;

    /**
     * @var list<\Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer>
     */
    protected array $additionalConstraintFieldTransfers = [];

    /**
     * @var list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    protected array $requestedConstraintTransfers = [];

    /**
     * @var array<string, string>
     */
    protected array $glossaryKeysByScope = [
        self::SCOPE_CATALOG_READ => 'agentic_commerce.scope.catalog.read',
        self::SCOPE_CART_READ => 'agentic_commerce.scope.cart.read',
        self::SCOPE_ORDER_PLACE => 'agentic_commerce.scope.order.place',
    ];

    protected function _before(): void
    {
        $this->customerTransfer = (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE)->setEmail('customer@shop.test');
        $this->principalCandidates = $this->tester->createPrincipalCollection([[static::PRINCIPAL_TYPE_CUSTOMER, static::CUSTOMER_REFERENCE]]);

        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $this->agenticCommerceClientMock->method('getAgenticCommercePrincipalCandidates')
            ->willReturnCallback(fn (CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer => $customerTransfer->getCustomerReference() === static::CUSTOMER_REFERENCE
                ? $this->principalCandidates
                : new AgenticCommercePrincipalCollectionTransfer());
        $this->agenticCommerceClientMock->method('getAgenticCommerceGrantableScopes')
            ->willReturnCallback(fn (AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer): AgenticCommerceGrantableScopesResponseTransfer => $this->filterScopes($agenticCommerceGrantableScopesRequestTransfer));

        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentDurationsInDays')
            ->willReturnCallback(fn (): array => $this->consentDurationsInDays);
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentConstraintFields')
            ->willReturnCallback(fn (AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer): AgenticCommerceConstraintFieldCollectionTransfer => $this->getConstraintFields($agenticCommerceConstraintFieldRequestTransfer));

        $this->customerClientMock = $this->createMock(CustomerClientInterface::class);
        $this->customerClientMock->method('getCustomer')->willReturnCallback(fn (): CustomerTransfer => $this->customerTransfer);

        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturnCallback(fn (): StoreTransfer => (new StoreTransfer())->setName(static::STORE_NAME)->setDefaultCurrencyIsoCode($this->storeCurrencyCode));
        $currencyClientMock = $this->createMock(CurrencyClientInterface::class);
        $currencyClientMock->method('fromIsoCode')
            ->willReturnCallback(fn (string $isoCode): CurrencyTransfer => (new CurrencyTransfer())->setCode($isoCode)->setFractionDigits(static::FRACTION_DIGITS_BY_CURRENCY_CODE[$isoCode]));

        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $this->customerClientMock,
            'getStoreClient' => $storeClientMock,
            'getCurrencyClient' => $currencyClientMock,
            'getLoginForm' => Forms::createFormFactory()->create(),
        ]);
    }

    public function testGivenAnotherStoreHostWhenTheConsentPageIsShownThenThePageIsNotFound(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();

        // Assert
        $this->expectException(NotFoundHttpException::class);

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [], 'at.shop.test'));
    }

    public function testGivenNoIssuerIsConfiguredWhenTheConsentPageIsShownThenThePageIsNotFound(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));
    }

    public function testGivenNoIssuerIsConfiguredWhenTheReloginPageIsShownThenThePageIsNotFound(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);

        // Act
        $this->createController()->reloginAction($this->tester->createRequest(['request' => $authorizationRequestId]));
    }

    public function testGivenAnUnknownAuthorizationRequestWhenTheConsentPageIsShownThenTheExpiredPageIsShown(): void
    {
        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => 'unknown']));

        // Assert
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_ERROR, $view->getTemplate());
        $this->assertSame(ConsentController::ERROR_EXPIRED, $view->getData()['authorizationError']);
    }

    public function testGivenASignedOutCustomerWhenTheConsentPageIsShownThenTheCustomerIsSentToLoginWithTheRequestPending(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn(null);
        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $customerClientMock,
        ]);

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
        $this->assertSame($authorizationRequestId, $this->agenticCommerceFactory->createAuthorizationRequestParker()->findPendingAuthorizationRequestId());
    }

    public function testGivenASignInOutsideTheFreshnessWindowWhenTheConsentPageIsShownThenTheCustomerIsSentToReloginWithTheRequestPending(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(sprintf('/agentic-commerce/relogin?request=%s', $authorizationRequestId), $response->getTargetUrl());
        $this->assertSame($authorizationRequestId, $this->agenticCommerceFactory->createAuthorizationRequestParker()->findPendingAuthorizationRequestId());
    }

    public function testGivenASignInOutsideTheFreshnessWindowWhenTheReloginPageIsShownThenTheLoginFormIsRenderedWithTheRequestPending(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();

        // Act
        $view = $this->createController()->reloginAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(ConsentController::TEMPLATE_RELOGIN, $view->getTemplate());
        $this->assertSame($authorizationRequestId, $this->agenticCommerceFactory->createAuthorizationRequestParker()->findPendingAuthorizationRequestId());
    }

    public function testGivenAFreshSignInWhenTheReloginPageIsShownThenTheCustomerIsSentToConsent(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $response = $this->createController()->reloginAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(sprintf('/agentic-commerce/consent?request=%s', $authorizationRequestId), $response->getTargetUrl());
    }

    public function testGivenAnUnknownAuthorizationRequestWhenTheReloginPageIsShownThenTheExpiredPageIsShown(): void
    {
        // Act
        $view = $this->createController()->reloginAction($this->tester->createRequest(['request' => 'unknown']));

        // Assert
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(ConsentController::ERROR_EXPIRED, $view->getData()['authorizationError']);
    }

    public function testGivenAFilterStackNarrowingTheScopesOfThePreselectedPrincipalWhenTheConsentPageIsShownThenOnlyTheNarrowedScopesAreOffered(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $this->filteredScopesByPrincipalValue[$this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_CUSTOMER, static::CUSTOMER_REFERENCE)] = [static::SCOPE_CATALOG_READ];

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $formView = $view->getData()['consentForm'];
        $this->assertSame([static::SCOPE_CATALOG_READ], array_map(static fn ($choiceView) => $choiceView->vars['value'], array_values($formView['scopes']->children)));
        $this->assertSame('agentic_commerce.scope.catalog.read', $formView['scopes']->children[0]->vars['label']);
        $this->assertSame('customer:' . static::CUSTOMER_REFERENCE, $formView['principal']->vars['value']);
        $this->assertSame('agentic_commerce.consent.principal.label', $formView['principal']->vars['label']);
        $this->assertSame('app.example', $view->getData()['redirectHost']);
        $this->assertTrue($view->getData()['isVerifiedClient']);
    }

    public function testGivenAScopeWhoseProviderNamesItsOwnGlossaryKeyWhenTheConsentPageIsShownThenTheScopeIsLabelledWithThatKey(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $this->glossaryKeysByScope[static::SCOPE_CATALOG_READ] = static::GLOSSARY_KEY_PROVIDED_CATALOG_READ;

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $formView = $view->getData()['consentForm'];
        $this->assertSame(
            [static::GLOSSARY_KEY_PROVIDED_CATALOG_READ, 'agentic_commerce.scope.cart.read'],
            array_map(static fn ($choiceView) => $choiceView->vars['label'], array_values($formView['scopes']->children)),
        );
    }

    public function testGivenALoopbackRedirectUriWhenTheConsentPageIsShownThenTheLoopbackWarningIsRendered(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization('http://localhost:53682/callback', true);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertTrue($view->getData()['isLoopbackRedirect']);
        $this->assertSame('localhost', $view->getData()['redirectHost']);
        $this->assertStringContainsString(
            static::GLOSSARY_KEY_LOOPBACK_REDIRECT_WARNING . ' localhost',
            $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_LOOPBACK_REDIRECT_WARNING, $view->getData()),
        );
    }

    public function testGivenALoopbackHostTheValidationDidNotFlagWhenTheConsentPageIsShownThenNoLoopbackWarningIsRendered(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization('http://127.0.0.1:33418/callback', false);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertFalse($view->getData()['isLoopbackRedirect']);
        $this->assertStringNotContainsString(
            static::GLOSSARY_KEY_LOOPBACK_REDIRECT_WARNING,
            $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_LOOPBACK_REDIRECT_WARNING, $view->getData()),
        );
    }

    public function testGivenAClientWithALogoAndAWebsiteWhenTheConsentPageIsShownThenTheLogoAndTheWebsiteHostAreRenderedWithoutALink(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization(static::REDIRECT_URI, false, $this->createClient()->setClientUri(static::CLIENT_URI)->setLogoUri(static::LOGO_URI));
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertSame('www.procure-assist.example', $view->getData()['clientHost']);
        $renderedClientIdentity = $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_IDENTITY, $view->getData());
        $this->assertStringContainsString(sprintf('src="%s"', static::LOGO_URI), $renderedClientIdentity);
        $this->assertStringContainsString(sprintf('alt="%s"', static::CLIENT_NAME), $renderedClientIdentity);
        $this->assertStringContainsString('referrerpolicy="no-referrer"', $renderedClientIdentity);
        $this->assertStringContainsString(static::GLOSSARY_KEY_CLIENT_URI . ' www.procure-assist.example', $renderedClientIdentity);
        $this->assertStringNotContainsString(static::CLIENT_URI, $renderedClientIdentity);
        $this->assertStringNotContainsString('<a ', $renderedClientIdentity);
    }

    public function testGivenAClientWithoutALogoOrAWebsiteWhenTheConsentPageIsShownThenNeitherIsRendered(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertNull($view->getData()['clientHost']);
        $renderedClientIdentity = $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_IDENTITY, $view->getData());
        $this->assertStringNotContainsString('<img', $renderedClientIdentity);
        $this->assertStringNotContainsString(static::GLOSSARY_KEY_CLIENT_URI, $renderedClientIdentity);
    }

    public function testGivenADomainVerifiedClientWhenTheConsentPageIsShownThenTheClientIdentifierHostIsNamed(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->createClient()
            ->setClientIdentifier('https://vendor.example/oauth/client.json')
            ->setTrustLevel(SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED)
            ->setLogoUri(static::LOGO_URI);
        $authorizationRequestId = $this->parkAuthorization(static::REDIRECT_URI, false, $agenticCommerceClientTransfer);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $viewData = $view->getData();
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED, $viewData['trustLevel']);
        $this->assertSame('vendor.example', $viewData['clientIdHost']);
        $this->assertTrue($viewData['showLogo']);
        $this->assertStringContainsString(
            static::GLOSSARY_KEY_TRUST_DOMAIN_VERIFIED . ' vendor.example',
            $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_TRUST_LEVEL, $viewData),
        );
        $this->assertStringContainsString('<img', $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_IDENTITY, $viewData));
    }

    public function testGivenAnUnverifiedClientWithALogoWhenTheConsentPageIsShownThenTheLogoIsHiddenAndTheRedirectHostIsToBeTrusted(): void
    {
        // Arrange
        $agenticCommerceClientTransfer = $this->createClient()
            ->setTrustLevel(SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED)
            ->setLogoUri(static::LOGO_URI);
        $authorizationRequestId = $this->parkAuthorization('https://claude.ai/api/mcp/auth_callback', false, $agenticCommerceClientTransfer);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $viewData = $view->getData();
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED, $viewData['trustLevel']);
        $this->assertNull($viewData['clientIdHost']);
        $this->assertFalse($viewData['showLogo']);
        $this->assertSame('claude.ai', $viewData['redirectHost']);
        $this->assertStringNotContainsString('<img', $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_IDENTITY, $viewData));
        $this->assertStringContainsString(
            static::GLOSSARY_KEY_TRUST_UNVERIFIED . ' claude.ai',
            $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_TRUST_LEVEL, $viewData),
        );
    }

    public function testGivenAVerifiedClientWhenTheConsentPageIsShownThenNoTrustLineIsRendered(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $viewData = $view->getData();
        $this->assertTrue($viewData['isVerifiedClient']);
        $this->assertTrue($viewData['showLogo']);
        $this->assertNull($viewData['clientIdHost']);
        $this->assertSame('', trim($this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CLIENT_TRUST_LEVEL, $viewData)));
    }

    public function testGivenNoGrantableScopesWhenTheConsentPageIsShownThenItSaysThereAreNoPermissionsAndOffersNoAllowButton(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization(static::REDIRECT_URI, false, null, []);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertFalse($view->getData()['hasGrantableScopes']);
    }

    public function testGivenGrantableScopesWhenTheConsentPageIsShownThenTheScopesAreOffered(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertTrue($view->getData()['hasGrantableScopes']);
    }

    public function testGivenNoGrantableScopesWhenDenyIsSubmittedThenAccessDeniedReturnsToTheClient(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization(static::REDIRECT_URI, false, null, []);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        unset($formData['allow'], $formData['scopes']);
        $formData['deny'] = '';

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertStringStartsWith(sprintf('%s?error=access_denied', static::REDIRECT_URI), $view->getData()['redirectUrl']);
    }

    public function testGivenNoGrantableScopesWhenAllowIsSubmittedThenNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization(static::REDIRECT_URI, false, null, []);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        unset($formData['scopes']);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
    }

    public function testGivenNoPrincipalCandidatesWhenTheConsentPageIsShownThenTheNoPermissionPageIsShown(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $this->principalCandidates = new AgenticCommercePrincipalCollectionTransfer();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_ERROR, $view->getTemplate());
        $this->assertSame('no_permission', $view->getData()['authorizationError']);
        $this->assertSame(
            sprintf('%s?error=access_denied&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenNoCsrfTokenWhenTheConsentIsSubmittedThenNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        unset($formData['_token']);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
    }

    public function testGivenAFreshSignInAndAnAllowedSubmissionOfGrantableScopesWhenTheConsentIsSubmittedThenTheConsentIsCreatedForTheSessionCustomerAndTheCodeReturnsWithStateAndIssuer(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('createAgenticCommerceConsentCollection')
            ->with($this->callback(function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer): bool {
                $agenticCommerceConsentTransfer = $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);

                return $agenticCommerceConsentCollectionRequestTransfer->getIsTransactional()
                    && $agenticCommerceConsentCollectionRequestTransfer->getAuthorizationRequestOrFail()->getClientId() === 'procure-assist'
                    && $agenticCommerceConsentTransfer->getCustomerReference() === static::CUSTOMER_REFERENCE
                    && $agenticCommerceConsentTransfer->getPrincipal()?->getPrincipalType() === static::PRINCIPAL_TYPE_CUSTOMER
                    && $agenticCommerceConsentTransfer->getPrincipal()?->getPrincipalReference() === static::CUSTOMER_REFERENCE
                    && $agenticCommerceConsentTransfer->getStoreName() === static::STORE_NAME
                    && $agenticCommerceConsentTransfer->getGrantedScopes() === [static::SCOPE_CART_READ]
                    && $agenticCommerceConsentTransfer->getDurationInDays() === 7;
            }))
            ->willReturn($this->createConsentResponse('code-123'));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $this->createFormData()]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertSame(
            sprintf('%s?code=code-123&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenARequestWithIpAddressAndALongUserAgentWhenTheConsentIsSubmittedThenTheClientDataIsPassedWithTheUserAgentTruncatedTo512Characters(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $request = $this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $this->createFormData()]);
        $request->server->set('REMOTE_ADDR', '203.0.113.7');
        $request->headers->set('User-Agent', str_repeat('a', 600));

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('createAgenticCommerceConsentCollection')
            ->with($this->callback(static function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer): bool {
                $requestClientDataTransfer = $agenticCommerceConsentCollectionRequestTransfer->getRequestClientDataOrFail();

                return $requestClientDataTransfer->getIpAddress() === '203.0.113.7'
                    && $requestClientDataTransfer->getUserAgent() === str_repeat('a', 512);
            }))
            ->willReturn($this->createConsentResponse('code-123'));

        // Act
        $this->createController()->indexAction($request);
    }

    public function testGivenALoopbackRedirectUriWithAPortWhenTheConsentIsSubmittedThenTheCodeReturnsToTheParkedRedirectUriWithItsPort(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization('http://127.0.0.1:53682/callback', true);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturn($this->createConsentResponse('code-123'));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $this->createFormData()]));

        // Assert
        $this->assertStringStartsWith('http://127.0.0.1:53682/callback?code=code-123&', $view->getData()['redirectUrl']);
    }

    public function testGivenDenyWhenTheConsentIsSubmittedThenAccessDeniedReturnsToTheClientAndNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        unset($formData['allow'], $formData['scopes']);
        $formData['deny'] = '';

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(
            sprintf('%s?error=access_denied&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenAScopeTheClientDidNotRequestWhenTheConsentIsSubmittedThenNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        $formData['scopes'] = ['cart:write'];

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
    }

    public function testGivenAScopeTheFilterStackRemovesForTheChosenPrincipalWhenTheConsentIsSubmittedThenNoConsentIsCreated(): void
    {
        // Arrange
        $this->principalCandidates = $this->tester->createPrincipalCollection([
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID],
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID],
        ]);
        $this->filteredScopesByPrincipalValue[$this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID)] = [static::SCOPE_CATALOG_READ];
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        $formData['principal'] = $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
    }

    public function testGivenARefusedConsentWhenTheConsentIsSubmittedThenServerErrorReturnsToTheClient(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError((new ErrorTransfer())->setMessage('agentic_commerce.consent.validation.store_not_found')),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $this->createFormData()]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertSame(
            sprintf('%s?error=server_error&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
    }

    public function testGivenAStoreNamePostedForAnotherStoreWhenTheConsentIsSubmittedThenTheStoreNameIsIgnoredAndTheConsentIsForTheCurrentStore(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        $formData['storeName'] = static::OTHER_STORE_NAME;
        $createdConsent = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(static::STORE_NAME, $createdConsent()->getStoreName());
    }

    public function testGivenADurationThatIsNotConfiguredWhenTheConsentIsSubmittedThenTheFormShowsAnErrorAndNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        $formData['durationInDays'] = static::UNCONFIGURED_DURATION_IN_DAYS;

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertGreaterThan(0, count($view->getData()['consentForm']['durationInDays']->vars['errors']));
    }

    public function testGivenDenyWithoutACsrfTokenWhenTheConsentIsSubmittedThenNoAccessDeniedIsReturnedAndTheRequestStaysParked(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        unset($formData['allow'], $formData['_token']);
        $formData['deny'] = '';

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertArrayNotHasKey('redirectUrl', $view->getData());
        $this->assertNotNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenDenyWithANonNumericAmountWhenTheConsentIsSubmittedThenAccessDeniedReturnsToTheClient(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::NON_NUMERIC_LIMIT_AS_SUBMITTED);
        unset($formData['allow']);
        $formData['deny'] = '';

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertSame(
            sprintf('%s?error=access_denied&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
    }

    public function testGivenDenyWithAScopeTheSubmittedPrincipalDoesNotOfferWhenTheConsentIsSubmittedThenAccessDeniedReturnsToTheClient(): void
    {
        // Arrange
        $this->principalCandidates = $this->tester->createPrincipalCollection([
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID],
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID],
        ]);
        $this->filteredScopesByPrincipalValue[$this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID)] = [static::SCOPE_CATALOG_READ];
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        unset($formData['allow']);
        $formData['principal'] = $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID);
        $formData['deny'] = '';

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertSame(
            sprintf('%s?error=access_denied&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenAPrincipalValueNotAmongTheCandidatesWhenTheConsentIsSubmittedThenNoConsentIsCreatedAndTheFormShowsAnError(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $this->principalCandidates = $this->tester->createPrincipalCollection([[static::PRINCIPAL_TYPE_CUSTOMER, 'DE--1']]);
        $formData = $this->createFormData();
        $formData['principal'] = 'company_user:foreign-uuid';

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertGreaterThan(0, count($view->getData()['consentForm']['principal']->vars['errors']));
    }

    public function testGivenSeveralCompanyUserCandidatesWhenTheConsentPageIsShownThenTheCurrentCompanyUserIsPreselected(): void
    {
        // Arrange
        $this->principalCandidates = $this->tester->createPrincipalCollection([
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID],
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID],
        ]);
        $this->customerTransfer->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID));
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $principalFormView = $view->getData()['consentForm']['principal'];
        $this->assertSame($this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID), $principalFormView->vars['value']);
        $this->assertCount(2, $principalFormView->vars['choices']);
    }

    public function testGivenACompanyUserCandidateWhenTheConsentIsSubmittedThenTheConsentActsAsTheChosenCompanyUser(): void
    {
        // Arrange
        $this->principalCandidates = $this->tester->createPrincipalCollection([
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::OTHER_COMPANY_USER_UUID],
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID],
        ]);
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);
        $formData = $this->createFormData();
        $formData['principal'] = $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('createAgenticCommerceConsentCollection')
            ->with($this->callback(static function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer): bool {
                $agenticCommercePrincipalTransfer = $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->getPrincipal();

                return $agenticCommercePrincipalTransfer?->getPrincipalType() === static::PRINCIPAL_TYPE_COMPANY_USER
                    && $agenticCommercePrincipalTransfer->getPrincipalReference() === static::COMPANY_USER_UUID;
            }))
            ->willReturn($this->createConsentResponse('code-456'));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
    }

    public function testGivenAFreshSignInRecordedForAnotherCustomerWhenTheConsentIsSubmittedThenNoConsentIsCreatedAndTheCustomerIsSentToRelogin(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication(
            (new CustomerTransfer())->setCustomerReference(static::OTHER_CUSTOMER_REFERENCE),
        );

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $this->createFormData()]));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(sprintf('/agentic-commerce/relogin?request=%s', $authorizationRequestId), $response->getTargetUrl());
    }

    public function testGivenASignInOutsideTheFreshnessWindowWhenTheConsentIsSubmittedThenNoConsentIsCreatedAndTheCustomerIsSentToRelogin(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $this->createFormData()]));

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(sprintf('/agentic-commerce/relogin?request=%s', $authorizationRequestId), $response->getTargetUrl());
        $this->assertNotNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenConfiguredConsentDurationsWhenTheConsentPageIsShownThenExactlyThoseDurationsAreOfferedWithTheFirstPreselected(): void
    {
        // Arrange
        $this->consentDurationsInDays = [14, 3];
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $durationFormView = $view->getData()['consentForm']['durationInDays'];
        $this->assertSame(['14', '3'], array_map(static fn ($choiceView): string => (string)$choiceView->vars['value'], array_values($durationFormView->children)));
        $this->assertSame('14', (string)$durationFormView->vars['value']);
    }

    public function testGivenOrderPlaceIsRequestedWhenTheConsentPageIsShownThenTheLimitFieldIsOfferedInTheStoreCurrencyWithoutTheHtmlRequiredAttribute(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $limitFormView = $view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT];
        $this->assertSame('agenticCommerceConsentForm[constraints][order_total_limit]', $limitFormView->vars['full_name']);
        $this->assertSame(static::GLOSSARY_KEY_ORDER_TOTAL_LIMIT, $limitFormView->vars['label']);
        $this->assertFalse($limitFormView->vars['required']);
        $this->assertStringContainsString('€', $limitFormView->vars['money_pattern']);
        $this->assertSame(
            [
                static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => static::SCOPE_ORDER_PLACE,
                static::CONSTRAINT_TYPE_ORDER_BUDGET => static::SCOPE_ORDER_PLACE,
                static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST => static::SCOPE_ORDER_PLACE,
                static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST => static::SCOPE_ORDER_PLACE,
            ],
            $view->getData()['constraintScopes'],
        );
    }

    public function testGivenOrderPlaceIsRequestedWhenTheConsentPageIsShownThenTheFieldsAreAskedForThePreselectedPrincipalAllRequestedScopesAndTheCurrentStore(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertCount(1, $this->constraintFieldRequestTransfers);
        $constraintFieldRequestTransfer = $this->constraintFieldRequestTransfers[0];
        $this->assertSame(static::CUSTOMER_REFERENCE, $constraintFieldRequestTransfer->getPrincipalOrFail()->getPrincipalReference());
        $this->assertSame([static::SCOPE_CART_READ, static::SCOPE_ORDER_PLACE], $constraintFieldRequestTransfer->getScopes());
        $this->assertSame('DE', $constraintFieldRequestTransfer->getStoreName());
        $this->assertSame(static::CURRENCY_CODE, $constraintFieldRequestTransfer->getCurrencyCode());
    }

    public function testGivenARoleLimitWhenTheConsentPageIsShownThenTheLimitFieldCarriesTheRoleLimitHint(): void
    {
        // Arrange
        $this->roleLimitInCents = static::ROLE_LIMIT_IN_CENTS;
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $constraintFieldTransfer = $view->getData()['constraintFields'][static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT];
        $this->assertSame(static::GLOSSARY_KEY_ORDER_TOTAL_LIMIT_HINT, $constraintFieldTransfer->getHintGlossaryKey());
        $this->assertSame([static::HINT_PARAMETER_LIMIT => static::ROLE_LIMIT_IN_CENTS], $constraintFieldTransfer->getHintParameters());
    }

    public function testGivenOrderPlaceIsRequestedWhenTheConsentPageIsShownThenItDoesNotSayTheAppCannotPlaceOrders(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertTrue($view->getData()['isOrderPlacementOffered']);
    }

    public function testGivenOrderPlaceIsNotRequestedWhenTheConsentPageIsShownThenItSaysTheAppCannotPlaceOrdersAndOffersNoLimitField(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkAuthorization();
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertFalse($view->getData()['isOrderPlacementOffered']);
        $this->assertSame([], $view->getData()['consentForm']['constraints']->children);
    }

    public function testGivenOrderPlaceAndARequiredLimitWhenTheConsentIsSubmittedWithoutTheLimitThenTheFormShowsAnErrorAndNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData('')],
        ));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertSame([static::GLOSSARY_KEY_CONSTRAINT_REQUIRED], $this->getLimitErrorMessages($view));
    }

    public function testGivenOrderPlaceWhenTheConsentIsSubmittedWithAZeroLimitThenTheFormShowsAnErrorAndNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData(static::ZERO_LIMIT_AS_SUBMITTED)],
        ));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertSame([static::GLOSSARY_KEY_AMOUNT_POSITIVE], $this->getLimitErrorMessages($view));
    }

    public function testGivenOrderPlaceWhenTheConsentIsSubmittedWithANegativeLimitThenTheFormShowsAnErrorAndNoConsentIsCreated(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData(static::NEGATIVE_LIMIT_AS_SUBMITTED)],
        ));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertSame([static::GLOSSARY_KEY_AMOUNT_POSITIVE], $this->getLimitErrorMessages($view));
    }

    public function testGivenAStoreInACurrencyWithoutFractionDigitsWhenTheConsentIsSubmittedThenTheLimitIsTakenAsWholeUnits(): void
    {
        // Arrange
        $this->storeCurrencyCode = static::CURRENCY_CODE_WITHOUT_FRACTION_DIGITS;
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $createdConsent = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData(static::LIMIT_IN_YEN_AS_SUBMITTED)],
        ));

        // Assert
        $this->assertSame(
            ['amount' => static::LIMIT_IN_YEN, 'currency' => static::CURRENCY_CODE_WITHOUT_FRACTION_DIGITS],
            $createdConsent()->getConstraints()->offsetGet(0)->getValue(),
        );
    }

    public function testGivenAStoreInACurrencyWithThreeFractionDigitsWhenTheConsentIsSubmittedThenTheLimitIsTakenInThousandths(): void
    {
        // Arrange
        $this->storeCurrencyCode = static::CURRENCY_CODE_WITH_THREE_FRACTION_DIGITS;
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $createdConsent = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData(static::LIMIT_IN_DINAR_AS_SUBMITTED)],
        ));

        // Assert
        $this->assertSame(
            ['amount' => static::LIMIT_IN_FILS, 'currency' => static::CURRENCY_CODE_WITH_THREE_FRACTION_DIGITS],
            $createdConsent()->getConstraints()->offsetGet(0)->getValue(),
        );
    }

    public function testGivenALimitOfFiftyEuroWhenTheConsentIsSubmittedThenTheConsentCarriesFiveThousandCents(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $createdConsentTransfer = null;
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')
            ->willReturnCallback(function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer) use (&$createdConsentTransfer): AgenticCommerceConsentCollectionResponseTransfer {
                $createdConsentTransfer = $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);

                return $this->createConsentResponse('code-123');
            });

        // Act
        $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED)],
        ));

        // Assert
        $this->assertCount(1, $createdConsentTransfer->getConstraints());
        $this->assertSame(static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT, $createdConsentTransfer->getConstraints()->offsetGet(0)->getType());
        $this->assertSame(
            ['amount' => static::LIMIT_IN_CENTS, 'currency' => static::CURRENCY_CODE],
            $createdConsentTransfer->getConstraints()->offsetGet(0)->getValue(),
        );
    }

    public function testGivenOrderPlaceIsUntickedWhenTheConsentIsSubmittedWithoutTheRequiredLimitThenTheConsentIsCreatedWithoutConstraints(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData('');
        $formData['scopes'] = [static::SCOPE_CART_READ];

        // Assert
        $this->expectConsentCreatedWithoutConstraints();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
    }

    public function testGivenOrderPlaceIsUntickedWhenTheConsentIsSubmittedWithALimitThenTheLimitIsNotPassedOn(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['scopes'] = [static::SCOPE_CART_READ];

        // Assert
        $this->expectConsentCreatedWithoutConstraints();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));
    }

    public function testGivenACompanyUserWithAnOptionalLimitWhenTheConsentIsSubmittedWithoutTheLimitThenTheConsentIsCreatedWithoutConstraints(): void
    {
        // Arrange
        $this->principalCandidates = $this->tester->createPrincipalCollection([[static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID]]);
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData('');
        $formData['principal'] = $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID);

        // Assert
        $this->expectConsentCreatedWithoutConstraints();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));
    }

    public function testGivenACompanyUserIsPreselectedWhenTheConsentIsSubmittedForTheCustomerWithoutTheLimitThenTheCustomersRequiredLimitShowsAnError(): void
    {
        // Arrange
        $this->principalCandidates = $this->tester->createPrincipalCollection([
            [static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID],
            [static::PRINCIPAL_TYPE_CUSTOMER, static::CUSTOMER_REFERENCE],
        ]);
        $this->customerTransfer->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID));
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData('')],
        ));

        // Assert
        $this->assertSame([static::GLOSSARY_KEY_CONSTRAINT_REQUIRED], $this->getLimitErrorMessages($view));
    }

    public function testGivenARequestedBudgetWhenTheConsentPageIsShownThenTheBudgetFieldIsPrefilledWithTheRequestedAmountAndPeriod(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $this->requestedConstraintTransfers = [$this->createRequestedBudget(static::BUDGET_IN_CENTS, SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK)];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $budgetFormView = $view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET];
        $this->assertSame(static::BUDGET_AS_SUBMITTED, $budgetFormView['amount']->vars['value']);
        $this->assertSame(SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK, $budgetFormView['period']->vars['value']);
    }

    public function testGivenABudgetFieldWhenTheConsentPageIsShownThenThePeriodsAreOfferedWithTheirGlossaryKeysAsLabelsBehindALabelledPlaceholderAndNoPeriodIsPreselectedWithoutARequest(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $periodFormView = $view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET]['period'];
        $this->assertSame(
            [
                static::GLOSSARY_KEY_PERIOD_DAY => SharedAgenticCommerceConfig::BUDGET_PERIOD_DAY,
                static::GLOSSARY_KEY_PERIOD_WEEK => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK,
                static::GLOSSARY_KEY_PERIOD_MONTH => SharedAgenticCommerceConfig::BUDGET_PERIOD_MONTH,
            ],
            array_column(array_map(static fn ($choiceView): array => [$choiceView->label, $choiceView->value], $periodFormView->vars['choices']), 1, 0),
        );
        $this->assertSame('', $periodFormView->vars['value']);
        $this->assertSame(static::GLOSSARY_KEY_PERIOD_PLACEHOLDER, $periodFormView->vars['placeholder']);
        $this->assertFalse($view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET]['amount']->vars['required']);
    }

    public function testGivenARequestedMerchantAllowlistWhenTheConsentPageIsShownThenOnlyTheRequestedMerchantsAreOfferedTickedAndTheNamesAreNotTranslated(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createMerchantField()];
        $this->requestedConstraintTransfers = [$this->createRequestedList(static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST, SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_MERCHANT_REFERENCES, [static::MERCHANT_REFERENCE_TWO])];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $merchantFormView = $view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST];
        $this->assertSame([static::MERCHANT_REFERENCE_TWO], $this->getCheckedValues($merchantFormView));
        $this->assertSame(['Merchant Two'], array_map(static fn ($choiceView): string => $choiceView->vars['label'], array_values($merchantFormView->children)));
        $this->assertFalse($merchantFormView->vars['choice_translation_domain']);
    }

    public function testGivenConstraintFieldWithUnsupportedInputKindWhenConsentPageRendersThenFieldIsLeftOut(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [
            $this->createMerchantField(),
            (new AgenticCommerceConstraintFieldTransfer())
                ->setType(static::CONSTRAINT_TYPE_UNSUPPORTED)
                ->setLabelGlossaryKey('agentic_commerce.constraint.unsupported')
                ->setInputKind(static::INPUT_KIND_UNSUPPORTED)
                ->setIsRequired(true),
        ];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $constraintsFormView = $view->getData()['consentForm']['constraints'];
        $this->assertArrayHasKey(static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST, $constraintsFormView->children);
        $this->assertArrayNotHasKey(static::CONSTRAINT_TYPE_UNSUPPORTED, $constraintsFormView->children);
    }

    /**
     * @dataProvider supportedInputKindDataProvider
     */
    public function testGivenARequestedConstraintFieldOfEverySupportedInputKindWhenTheConsentPageIsShownThenTheFieldIsOffered(string $inputKind): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [
            (new AgenticCommerceConstraintFieldTransfer())
                ->setType(static::CONSTRAINT_TYPE_SUPPORTED)
                ->setLabelGlossaryKey('agentic_commerce.constraint.supported')
                ->setInputKind($inputKind)
                ->setIsRequired(false)
                ->setValueKey(static::VALUE_KEY_SUPPORTED),
        ];
        $this->requestedConstraintTransfers = [$this->createRequestedList(static::CONSTRAINT_TYPE_SUPPORTED, static::VALUE_KEY_SUPPORTED, [])];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $this->assertArrayHasKey(static::CONSTRAINT_TYPE_SUPPORTED, $view->getData()['consentForm']['constraints']->children);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function supportedInputKindDataProvider(): array
    {
        $dataSets = [];

        foreach (SharedAgenticCommerceConfig::SUPPORTED_CONSTRAINT_INPUT_KINDS as $inputKind) {
            $dataSets[$inputKind] = [$inputKind];
        }

        return $dataSets;
    }

    public function testGivenARequestedCategoryAllowlistWhenTheConsentPageIsShownThenOnlyTheRequestedCategoriesAreOfferedTicked(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createCategoryField()];
        $this->requestedConstraintTransfers = [$this->createRequestedList(static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST, SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CATEGORY_KEYS, [static::CATEGORY_KEY_NOTEBOOKS, static::CATEGORY_KEY_CAMERAS])];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $categoryFormView = $view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST];
        $this->assertSame([static::CATEGORY_KEY_NOTEBOOKS, static::CATEGORY_KEY_CAMERAS], $this->getCheckedValues($categoryFormView));
        $this->assertSame(
            [static::CATEGORY_KEY_NOTEBOOKS => 'Notebooks', static::CATEGORY_KEY_CAMERAS => 'Cameras'],
            $view->getData()['constraintFields'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST]->getOptions(),
        );
        $this->assertSame(
            [static::CATEGORY_KEY_NOTEBOOKS => static::CATEGORY_KEY_COMPUTER],
            $view->getData()['constraintFields'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST]->getOptionParents(),
        );
    }

    public function testGivenARequestedParentCategoryWhenTheConsentPageIsShownThenTheParentAndItsDescendantsAreOfferedAndNoOtherCategory(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createCategoryField()];
        $this->requestedConstraintTransfers = [$this->createRequestedList(static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST, SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CATEGORY_KEYS, [static::CATEGORY_KEY_COMPUTER])];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $constraintFieldTransfer = $view->getData()['constraintFields'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST];
        $this->assertSame(
            [static::CATEGORY_KEY_COMPUTER, static::CATEGORY_KEY_NOTEBOOKS, static::CATEGORY_KEY_TABLETS],
            array_keys($constraintFieldTransfer->getOptions()),
        );
        $this->assertSame([static::CATEGORY_KEY_COMPUTER], $this->getCheckedValues($view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST]));
    }

    public function testGivenARequestedBudgetWhenTheConsentPageIsShownForAnotherConstraintTypeThenNothingIsPrefilledForIt(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField(), $this->createMerchantField()];
        $this->requestedConstraintTransfers = [$this->createRequestedBudget(static::BUDGET_IN_CENTS, SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK)];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Assert
        $constraintsFormView = $view->getData()['consentForm']['constraints'];
        $this->assertSame([], $this->getCheckedValues($constraintsFormView[static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST]));
        $this->assertSame('', $constraintsFormView[static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT]->vars['value']);
    }

    public function testGivenABudgetWhenTheConsentIsSubmittedThenTheConsentCarriesTheAmountInCentsTheCurrencyAndThePeriod(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::BUDGET_AS_SUBMITTED, 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];
        $createdConsentTransfer = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(
            ['amount' => static::BUDGET_IN_CENTS, 'currency' => static::CURRENCY_CODE, 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK],
            $this->getConstraintValuesByType($createdConsentTransfer())[static::CONSTRAINT_TYPE_ORDER_BUDGET],
        );
    }

    public function testGivenAMerchantAllowlistWhenTheConsentIsSubmittedThenTheConsentCarriesTheTickedMerchantReferences(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createMerchantField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST] = [static::MERCHANT_REFERENCE_ONE, static::MERCHANT_REFERENCE_TWO];
        $createdConsentTransfer = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(
            [SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_MERCHANT_REFERENCES => [static::MERCHANT_REFERENCE_ONE, static::MERCHANT_REFERENCE_TWO]],
            $this->getConstraintValuesByType($createdConsentTransfer())[static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST],
        );
    }

    public function testGivenACategoryAllowlistWhenTheConsentIsSubmittedThenTheConsentCarriesTheTickedCategoryKeys(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createCategoryField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST] = [static::CATEGORY_KEY_NOTEBOOKS];
        $createdConsentTransfer = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(
            [SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CATEGORY_KEYS => [static::CATEGORY_KEY_NOTEBOOKS]],
            $this->getConstraintValuesByType($createdConsentTransfer())[static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST],
        );
    }

    public function testGivenEmptyBudgetMerchantAndCategoryFieldsWhenTheConsentIsSubmittedThenTheConsentCarriesNoConstraintForThem(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField(), $this->createMerchantField(), $this->createCategoryField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => '', 'period' => ''];
        $createdConsentTransfer = $this->captureCreatedConsent();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame([static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT], array_keys($this->getConstraintValuesByType($createdConsentTransfer())));
    }

    public function testGivenABudgetAmountWithoutAPeriodWhenTheConsentIsSubmittedThenTheFormShowsAnErrorAndNoConsentIsCreated(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::BUDGET_AS_SUBMITTED, 'period' => ''];

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertSame([static::GLOSSARY_KEY_PERIOD_REQUIRED], $this->getConstraintErrorMessages($view, static::CONSTRAINT_TYPE_ORDER_BUDGET));
    }

    public function testGivenAPeriodOutsideTheOfferedOnesWhenTheConsentIsSubmittedThenTheFormShowsAnErrorAndNoConsentIsCreated(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::BUDGET_AS_SUBMITTED, 'period' => 'year'];

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
    }

    public function testGivenAMerchantTheFieldDoesNotOfferWhenTheConsentIsSubmittedThenNoConsentIsCreated(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createMerchantField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST] = ['MER999999'];

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('createAgenticCommerceConsentCollection');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
    }

    public function testGivenOrderPlaceIsUntickedWhenTheConsentIsSubmittedWithABudgetAndListsThenNoneOfThemIsPassedOn(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField(), $this->createMerchantField(), $this->createCategoryField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['scopes'] = [static::SCOPE_CART_READ];
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::BUDGET_AS_SUBMITTED, 'period' => 'nonsense'];
        $formData['constraints'][static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST] = ['MER999999'];
        $formData['constraints'][static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST] = [static::CATEGORY_KEY_NOTEBOOKS];

        // Assert
        $this->expectConsentCreatedWithoutConstraints();

        // Act
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));
    }

    /**
     * @dataProvider constraintRefusalMessageProvider
     */
    public function testGivenZedRefusesAConstraintAgainstTheRequestWhenTheConsentIsSubmittedThenTheConsentPageShowsTheErrorOnThatFieldAndTheRequestStaysParked(
        string $refusalMessage
    ): void {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $this->requestedConstraintTransfers = [$this->createRequestedBudget(static::BUDGET_IN_CENTS, SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK)];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => '250.00', 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError(
                (new ErrorTransfer())->setMessage($refusalMessage)->setEntityIdentifier(static::CONSTRAINT_TYPE_ORDER_BUDGET),
            ),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame(ConsentController::TEMPLATE_CONSENT, $view->getTemplate());
        $this->assertSame([$refusalMessage], $this->getConstraintErrorMessages($view, static::CONSTRAINT_TYPE_ORDER_BUDGET));
        $this->assertSame([], $this->getConstraintErrorMessages($view, static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT));
        $this->assertNotNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
        $this->assertSame('250.00', $view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET]['amount']->vars['value']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function constraintRefusalMessageProvider(): array
    {
        return [
            'wider than requested' => [static::GLOSSARY_KEY_WIDER_THAN_REQUESTED],
            'requested removed' => [static::GLOSSARY_KEY_REQUESTED_REMOVED],
        ];
    }

    public function testGivenAConstraintRefusalWhenTheCustomerSubmitsAgainWithANarrowerBudgetThenTheConsentIsCreatedAndTheRequestIsRemoved(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $this->requestedConstraintTransfers = [$this->createRequestedBudget(static::BUDGET_IN_CENTS, SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK)];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => '250.00', 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturnOnConsecutiveCalls(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError(
                (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_WIDER_THAN_REQUESTED)->setEntityIdentifier(static::CONSTRAINT_TYPE_ORDER_BUDGET),
            ),
            $this->createConsentResponse('code-123'),
        );
        $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET]['amount'] = static::NARROWER_BUDGET_AS_SUBMITTED;

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertSame(
            sprintf('%s?code=code-123&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenANarrowerBudgetThanRequestedWhenTheConsentIsSubmittedThenTheConsentIsCreatedAndTheCodeReturnsToTheClient(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $this->requestedConstraintTransfers = [$this->createRequestedBudget(static::BUDGET_IN_CENTS, SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK)];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::NARROWER_BUDGET_AS_SUBMITTED, 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];
        $createdConsentTransfer = $this->captureCreatedConsent();

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertSame(
            sprintf('%s?code=code-123&state=%s&iss=%s', static::REDIRECT_URI, static::STATE, static::ENCODED_ISSUER),
            $view->getData()['redirectUrl'],
        );
        $this->assertSame(static::NARROWER_BUDGET_IN_CENTS, $this->getConstraintValuesByType($createdConsentTransfer())[static::CONSTRAINT_TYPE_ORDER_BUDGET]['amount']);
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenARefusalOfAnotherConstraintTypeThanOnTheFormWhenTheConsentIsSubmittedThenServerErrorReturnsToTheClientAndTheRequestIsRemoved(): void
    {
        // Arrange
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError(
                (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_WIDER_THAN_REQUESTED)->setEntityIdentifier(static::CONSTRAINT_TYPE_ORDER_BUDGET),
            ),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(
            ['request' => $authorizationRequestId],
            [static::FORM_NAME => $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED)],
        ));

        // Assert
        $this->assertSame(ConsentController::TEMPLATE_AUTHORIZATION_RESPONSE, $view->getTemplate());
        $this->assertStringContainsString('error=server_error', $view->getData()['redirectUrl']);
        $this->assertNull($this->agenticCommerceFactory->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenAConstraintRefusalAmongOtherErrorsWhenTheConsentIsSubmittedThenServerErrorReturnsToTheClient(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::BUDGET_AS_SUBMITTED, 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_WIDER_THAN_REQUESTED)->setEntityIdentifier(static::CONSTRAINT_TYPE_ORDER_BUDGET))
                ->addError((new ErrorTransfer())->setMessage('agentic_commerce.consent.validation.store_not_found')->setEntityIdentifier('0')),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertStringContainsString('error=server_error', $view->getData()['redirectUrl']);
    }

    public function testGivenAConstraintRefusalWhenTheConsentIsSubmittedThenTheConsentIsRequestedOnceAndNoCodeIsReturned(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => static::BUDGET_AS_SUBMITTED, 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())->method('createAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError(
                (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_REQUESTED_REMOVED)->setEntityIdentifier(static::CONSTRAINT_TYPE_ORDER_BUDGET),
            ),
        );

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Assert
        $this->assertArrayNotHasKey('redirectUrl', $view->getData());
    }

    protected function createBudgetField(): AgenticCommerceConstraintFieldTransfer
    {
        return (new AgenticCommerceConstraintFieldTransfer())
            ->setType(static::CONSTRAINT_TYPE_ORDER_BUDGET)
            ->setLabelGlossaryKey(static::GLOSSARY_KEY_ORDER_BUDGET)
            ->setInputKind(static::INPUT_KIND_MONEY_PERIOD)
            ->setCurrencyCode(static::CURRENCY_CODE)
            ->setIsRequired(false)
            ->setOptions([
                SharedAgenticCommerceConfig::BUDGET_PERIOD_DAY => static::GLOSSARY_KEY_PERIOD_DAY,
                SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK => static::GLOSSARY_KEY_PERIOD_WEEK,
                SharedAgenticCommerceConfig::BUDGET_PERIOD_MONTH => static::GLOSSARY_KEY_PERIOD_MONTH,
            ]);
    }

    protected function createMerchantField(): AgenticCommerceConstraintFieldTransfer
    {
        return (new AgenticCommerceConstraintFieldTransfer())
            ->setType(static::CONSTRAINT_TYPE_MERCHANT_ALLOWLIST)
            ->setLabelGlossaryKey('agentic_commerce.constraint.merchant_allowlist')
            ->setInputKind(static::INPUT_KIND_MULTI_SELECT)
            ->setIsRequired(false)
            ->setValueKey(SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_MERCHANT_REFERENCES)
            ->setOptions([static::MERCHANT_REFERENCE_ONE => 'Merchant One', static::MERCHANT_REFERENCE_TWO => 'Merchant Two']);
    }

    protected function createCategoryField(): AgenticCommerceConstraintFieldTransfer
    {
        return (new AgenticCommerceConstraintFieldTransfer())
            ->setType(static::CONSTRAINT_TYPE_CATEGORY_ALLOWLIST)
            ->setLabelGlossaryKey('agentic_commerce.constraint.category_allowlist')
            ->setInputKind(static::INPUT_KIND_CATEGORY_TREE)
            ->setIsRequired(false)
            ->setValueKey(SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CATEGORY_KEYS)
            ->setOptions([
                static::CATEGORY_KEY_COMPUTER => 'Computer',
                static::CATEGORY_KEY_NOTEBOOKS => 'Notebooks',
                static::CATEGORY_KEY_TABLETS => 'Tablets',
                static::CATEGORY_KEY_CAMERAS => 'Cameras',
            ])
            ->setOptionParents([static::CATEGORY_KEY_NOTEBOOKS => static::CATEGORY_KEY_COMPUTER, static::CATEGORY_KEY_TABLETS => static::CATEGORY_KEY_COMPUTER]);
    }

    protected function createRequestedBudget(int $amountInCents, string $period): AgenticCommerceConsentConstraintTransfer
    {
        return (new AgenticCommerceConsentConstraintTransfer())
            ->setType(static::CONSTRAINT_TYPE_ORDER_BUDGET)
            ->setValue([
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_AMOUNT => $amountInCents,
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CURRENCY => static::CURRENCY_CODE,
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_PERIOD => $period,
            ]);
    }

    /**
     * @param list<string> $values
     */
    protected function createRequestedList(string $constraintType, string $valueKey, array $values): AgenticCommerceConsentConstraintTransfer
    {
        return (new AgenticCommerceConsentConstraintTransfer())->setType($constraintType)->setValue([$valueKey => $values]);
    }

    /**
     * @return list<string>
     */
    protected function getCheckedValues(FormView $formView): array
    {
        $checkedValues = [];

        foreach ($formView->children as $choiceView) {
            if ($choiceView->vars['checked']) {
                $checkedValues[] = $choiceView->vars['value'];
            }
        }

        return $checkedValues;
    }

    /**
     * @return callable(): \Generated\Shared\Transfer\AgenticCommerceConsentTransfer Returns the consent the client was asked to create, once the controller ran.
     */
    protected function captureCreatedConsent(): callable
    {
        $createdConsentTransfer = null;
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')
            ->willReturnCallback(function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer) use (&$createdConsentTransfer): AgenticCommerceConsentCollectionResponseTransfer {
                $createdConsentTransfer = $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);

                return $this->createConsentResponse('code-123');
            });

        return static function () use (&$createdConsentTransfer): AgenticCommerceConsentTransfer {
            return $createdConsentTransfer;
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getConstraintValuesByType(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): array
    {
        $constraintValuesByType = [];

        foreach ($agenticCommerceConsentTransfer->getConstraints() as $agenticCommerceConsentConstraintTransfer) {
            $constraintValuesByType[$agenticCommerceConsentConstraintTransfer->getTypeOrFail()] = $agenticCommerceConsentConstraintTransfer->getValue();
        }

        return $constraintValuesByType;
    }

    /**
     * @return list<string>
     */
    protected function getConstraintErrorMessages(View $view, string $constraintType): array
    {
        $errorMessages = [];

        foreach ($view->getData()['consentForm']['constraints'][$constraintType]->vars['errors'] as $formError) {
            $errorMessages[] = $formError->getMessage();
        }

        return $errorMessages;
    }

    public function testGivenACategoryAllowlistFieldWhenTheConstraintFieldsAreRenderedThenTheCategoriesAreNestedByTheirParents(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createCategoryField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Act
        $xpath = $this->renderConstraintFields($view);

        // Assert
        $treeQuery = '//div[@data-qa="agentic-commerce-constraint-category_allowlist"]/ul';
        $this->assertSame([static::CATEGORY_KEY_COMPUTER, static::CATEGORY_KEY_CAMERAS], $this->getInputValues($xpath, $treeQuery . '/li/input'));
        $this->assertSame([static::CATEGORY_KEY_NOTEBOOKS, static::CATEGORY_KEY_TABLETS], $this->getInputValues($xpath, $treeQuery . '/li/ul/li/input'));
        $this->assertSame(0, $xpath->query($treeQuery . '//input[@checked]')->length);
        $this->assertSame(0, $xpath->query($treeQuery . '/li/ul/li/ul')->length);
        $this->assertSame(0, $xpath->query($treeQuery . '/li[2]/ul')->length);
    }

    public function testGivenABudgetAndAMerchantFieldWhenTheConstraintFieldsAreRenderedThenEachWidgetSitsInItsDataQaWrapper(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField(), $this->createMerchantField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId]));

        // Act
        $xpath = $this->renderConstraintFields($view);

        // Assert
        $budgetQuery = '//div[@data-qa="agentic-commerce-constraint-order_budget"]';
        $this->assertSame(1, $xpath->query($budgetQuery . '/input[@type="text"]')->length);
        $this->assertSame(3, $xpath->query($budgetQuery . '/select/option[@value!=""]')->length);
        $this->assertSame(
            [static::MERCHANT_REFERENCE_ONE, static::MERCHANT_REFERENCE_TWO],
            $this->getInputValues($xpath, '//div[@data-qa="agentic-commerce-constraint-merchant_allowlist"]/input[@type="checkbox"]'),
        );
        $this->assertSame(0, $xpath->query('//*[@required]')->length);
    }

    public function testGivenAConstraintRefusalWhenTheConstraintFieldsAreRenderedThenTheRefusalIsShownInTheErrorElementOfThatField(): void
    {
        // Arrange
        $this->additionalConstraintFieldTransfers = [$this->createBudgetField()];
        $authorizationRequestId = $this->parkOrderPlacementAuthorization();
        $formData = $this->createOrderPlacementFormData(static::LIMIT_AS_SUBMITTED);
        $formData['constraints'][static::CONSTRAINT_TYPE_ORDER_BUDGET] = ['amount' => '250.00', 'period' => SharedAgenticCommerceConfig::BUDGET_PERIOD_WEEK];
        $this->agenticCommerceClientMock->method('createAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError(
                (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_WIDER_THAN_REQUESTED)->setEntityIdentifier(static::CONSTRAINT_TYPE_ORDER_BUDGET),
            ),
        );
        $view = $this->createController()->indexAction($this->tester->createRequest(['request' => $authorizationRequestId], [static::FORM_NAME => $formData]));

        // Act
        $xpath = $this->renderConstraintFields($view);

        // Assert
        $errorElements = $xpath->query('//div[@data-qa="agentic-commerce-constraint-error"]');
        $this->assertSame(1, $errorElements->length);
        $this->assertStringContainsString(static::GLOSSARY_KEY_WIDER_THAN_REQUESTED, $errorElements->item(0)->textContent);
    }

    protected function renderConstraintFields(View $view): DOMXPath
    {
        $renderedHtml = $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CONSTRAINT_FIELDS, [
            'form' => $view->getData()['consentForm']['constraints'],
            'constraintFields' => $view->getData()['constraintFields'],
            'constraintScopes' => $view->getData()['constraintScopes'],
        ]);
        $domDocument = new DOMDocument();
        $domDocument->loadHTML($renderedHtml, LIBXML_NOERROR);

        return new DOMXPath($domDocument);
    }

    /**
     * @return list<string>
     */
    protected function getInputValues(DOMXPath $xpath, string $query): array
    {
        $values = [];

        foreach ($xpath->query($query) as $inputElement) {
            $values[] = $inputElement->getAttribute('value');
        }

        return $values;
    }

    protected function createController(): ConsentController
    {
        return $this->tester->createController(ConsentController::class, $this->agenticCommerceFactory);
    }

    protected function getConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer {
        $this->constraintFieldRequestTransfers[] = $agenticCommerceConstraintFieldRequestTransfer;
        $agenticCommerceConstraintFieldCollectionTransfer = new AgenticCommerceConstraintFieldCollectionTransfer();

        if (!in_array(static::SCOPE_ORDER_PLACE, $agenticCommerceConstraintFieldRequestTransfer->getScopes(), true)) {
            return $agenticCommerceConstraintFieldCollectionTransfer;
        }

        $agenticCommerceConstraintFieldTransfer = (new AgenticCommerceConstraintFieldTransfer())
            ->setType(static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT)
            ->setLabelGlossaryKey(static::GLOSSARY_KEY_ORDER_TOTAL_LIMIT)
            ->setInputKind(static::INPUT_KIND_MONEY)
            ->setCurrencyCode($agenticCommerceConstraintFieldRequestTransfer->getCurrencyCode())
            ->setIsRequired($agenticCommerceConstraintFieldRequestTransfer->getPrincipalOrFail()->getPrincipalType() === static::PRINCIPAL_TYPE_CUSTOMER);

        if ($this->roleLimitInCents !== null) {
            $agenticCommerceConstraintFieldTransfer
                ->setHintGlossaryKey(static::GLOSSARY_KEY_ORDER_TOTAL_LIMIT_HINT)
                ->setHintParameters([static::HINT_PARAMETER_LIMIT => $this->roleLimitInCents]);
        }

        $agenticCommerceConstraintFieldCollectionTransfer->addField($agenticCommerceConstraintFieldTransfer);

        foreach ($this->additionalConstraintFieldTransfers as $additionalConstraintFieldTransfer) {
            $agenticCommerceConstraintFieldCollectionTransfer->addField($additionalConstraintFieldTransfer);
        }

        return $agenticCommerceConstraintFieldCollectionTransfer;
    }

    protected function parkOrderPlacementAuthorization(): string
    {
        $authorizationRequestId = $this->parkAuthorization(static::REDIRECT_URI, false, null, [static::SCOPE_CART_READ, static::SCOPE_ORDER_PLACE]);
        $this->agenticCommerceFactory->createFreshAuthenticationChecker()->recordAuthentication($this->customerTransfer);

        return $authorizationRequestId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function createOrderPlacementFormData(string $limit): array
    {
        $formData = $this->createFormData();
        $formData['scopes'] = [static::SCOPE_CART_READ, static::SCOPE_ORDER_PLACE];
        $formData['durationInDays'] = (string)static::DURATION_IN_DAYS;
        $formData['constraints'] = [static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => $limit];

        return $formData;
    }

    /**
     * @return list<string>
     */
    protected function getLimitErrorMessages(View $view): array
    {
        $errorMessages = [];

        foreach ($view->getData()['consentForm']['constraints'][static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT]->vars['errors'] as $formError) {
            $errorMessages[] = $formError->getMessage();
        }

        return $errorMessages;
    }

    protected function expectConsentCreatedWithoutConstraints(): void
    {
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('createAgenticCommerceConsentCollection')
            ->with($this->callback(static fn (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer): bool => $agenticCommerceConsentCollectionRequestTransfer
                ->getAgenticCommerceConsents()
                ->offsetGet(0)
                ->getConstraints()
                ->count() === 0))
            ->willReturn($this->createConsentResponse('code-123'));
    }

    protected function filterScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer {
        $agenticCommercePrincipalTransfer = $agenticCommerceGrantableScopesRequestTransfer->getPrincipalOrFail();
        $principalValue = $this->tester->createPrincipalValue(
            $agenticCommercePrincipalTransfer->getPrincipalTypeOrFail(),
            $agenticCommercePrincipalTransfer->getPrincipalReferenceOrFail(),
        );
        $requestedScopes = array_values($agenticCommerceGrantableScopesRequestTransfer->getRequestedScopes());
        $grantableScopes = isset($this->filteredScopesByPrincipalValue[$principalValue])
            ? array_values(array_intersect($requestedScopes, $this->filteredScopesByPrincipalValue[$principalValue]))
            : $requestedScopes;

        $agenticCommerceGrantableScopesResponseTransfer = (new AgenticCommerceGrantableScopesResponseTransfer())->setGrantableScopes($grantableScopes);

        foreach ($grantableScopes as $grantableScope) {
            $agenticCommerceGrantableScopesResponseTransfer->addScope(
                (new AgenticCommerceScopeTransfer())->setName($grantableScope)->setGlossaryKey($this->glossaryKeysByScope[$grantableScope]),
            );
        }

        return $agenticCommerceGrantableScopesResponseTransfer;
    }

    /**
     * @param list<string> $grantableScopes
     */
    protected function parkAuthorization(
        string $redirectUri = self::REDIRECT_URI,
        bool $isLoopbackRedirect = false,
        ?AgenticCommerceClientTransfer $agenticCommerceClientTransfer = null,
        array $grantableScopes = [self::SCOPE_CATALOG_READ, self::SCOPE_CART_READ]
    ): string {
        return $this->agenticCommerceFactory->createAuthorizationRequestParker()->park(
            (new AgenticCommerceAuthorizationResponseTransfer())
                ->setIsValid(true)
                ->setIsRedirectable(true)
                ->setGrantableScopes($grantableScopes)
                ->setRequestedConstraints(new ArrayObject($this->requestedConstraintTransfers))
                ->setAgenticCommerceClient($agenticCommerceClientTransfer ?? $this->createClient())
                ->setAuthorizationRequest(
                    (new AgenticCommerceAuthorizationRequestTransfer())
                        ->setClientId('procure-assist')
                        ->setRedirectUri($redirectUri)
                        ->setResponseType('code')
                        ->setState(static::STATE)
                        ->setCodeChallenge(str_repeat('a', 43))
                        ->setCodeChallengeMethod('S256')
                        ->setResource('https://glue.shop.test/agentic-commerce')
                        ->setIsLoopbackRedirect($isLoopbackRedirect),
                ),
        );
    }

    protected function createClient(): AgenticCommerceClientTransfer
    {
        return (new AgenticCommerceClientTransfer())->setClientIdentifier('procure-assist')->setName(static::CLIENT_NAME)->setTrustLevel('verified');
    }

    /**
     * @return array<string, mixed>
     */
    protected function createFormData(): array
    {
        return [
            'principal' => $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_CUSTOMER, static::CUSTOMER_REFERENCE),
            'scopes' => [static::SCOPE_CART_READ],
            'durationInDays' => '7',
            'allow' => '',
            '_token' => $this->tester->getCsrfToken(static::FORM_NAME),
        ];
    }

    protected function createConsentResponse(string $authorizationCode): AgenticCommerceConsentCollectionResponseTransfer
    {
        return (new AgenticCommerceConsentCollectionResponseTransfer())->addAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())->setUuid('consent-uuid')->setAuthorizationCode($authorizationCode),
        );
    }
}
