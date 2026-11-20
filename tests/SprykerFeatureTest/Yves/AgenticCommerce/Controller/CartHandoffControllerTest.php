<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemResponseTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\MultiCart\MultiCartClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Controller\CartHandoffController;
use SprykerFeature\Yves\AgenticCommerce\Plugin\CustomerPage\AgenticCommerceCartHandoffCustomerRedirectStrategyPlugin;
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
 * @group CartHandoffControllerTest
 * Add your own group annotations below this line
 */
class CartHandoffControllerTest extends Unit
{
    protected const string TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQ';

    protected const string CUSTOMER_REFERENCE = 'DE--7';

    protected const string COMPANY_USER_UUID = 'company-user-uuid';

    protected const string STORE_NAME = 'DE';

    protected const int ID_QUOTE = 42;

    protected const string SESSION_KEY_PENDING_CART_HANDOFF_TOKEN = 'agentic_commerce.pending_cart_handoff_token';

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected CustomerClientInterface&MockObject $customerClientMock;

    protected MultiCartClientInterface&MockObject $multiCartClientMock;

    protected AgenticCommerceFactory $agenticCommerceFactory;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $this->customerClientMock = $this->createMock(CustomerClientInterface::class);
        $this->multiCartClientMock = $this->createMock(MultiCartClientInterface::class);
        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturn((new StoreTransfer())->setName(static::STORE_NAME));

        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $this->customerClientMock,
            'getMultiCartClient' => $this->multiCartClientMock,
            'getStoreClient' => $storeClientMock,
        ]);
    }

    public function testGivenTheOwnerOfAHandedOffCartWhenTheLinkIsOpenedThenTheCartIsTheDefaultCartAndTheOwnerIsSentToTheCart(): void
    {
        // Arrange
        $this->signIn();
        $quoteTransfer = (new QuoteTransfer())->setIdQuote(static::ID_QUOTE);
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('redeemCartHandoff')
            ->with($this->callback(static fn (AgenticCommerceCartHandoffRedeemRequestTransfer $redeemRequestTransfer): bool => $redeemRequestTransfer->getToken() === static::TOKEN
                && $redeemRequestTransfer->getCustomerReference() === static::CUSTOMER_REFERENCE
                && $redeemRequestTransfer->getCompanyUserUuid() === static::COMPANY_USER_UUID
                && $redeemRequestTransfer->getStoreName() === static::STORE_NAME))
            ->willReturn((new AgenticCommerceCartHandoffRedeemResponseTransfer())->setIsValid(true)->setQuote($quoteTransfer));
        $this->multiCartClientMock->expects($this->once())
            ->method('markQuoteAsDefault')
            ->with($quoteTransfer)
            ->willReturn((new QuoteResponseTransfer())->setIsSuccessful(true)->setQuoteTransfer($quoteTransfer));

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/cart', $response->getTargetUrl());
    }

    public function testGivenAHandoffThatIsNotValidForTheCustomerWhenTheLinkIsOpenedThenTheInvalidPageIsShownAndNoCartIsChanged(): void
    {
        // Arrange
        $this->signIn();
        $this->agenticCommerceClientMock->method('redeemCartHandoff')
            ->willReturn((new AgenticCommerceCartHandoffRedeemResponseTransfer())->setIsValid(false));
        $this->multiCartClientMock->expects($this->never())->method('markQuoteAsDefault');

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);

        // Assert
        $this->assertSame(CartHandoffController::TEMPLATE_INVALID, $view->getTemplate());
        $this->assertSame([], $view->getData());
    }

    public function testGivenARedeemedTokenWhenMarkingTheQuoteAsDefaultFailsThenTheInvalidPageIsShown(): void
    {
        // Arrange
        $this->signIn();
        $quoteTransfer = (new QuoteTransfer())->setIdQuote(static::ID_QUOTE);
        $this->agenticCommerceClientMock->method('redeemCartHandoff')
            ->willReturn((new AgenticCommerceCartHandoffRedeemResponseTransfer())->setIsValid(true)->setQuote($quoteTransfer));
        $this->multiCartClientMock->method('markQuoteAsDefault')->willReturn((new QuoteResponseTransfer())->setIsSuccessful(false));

        // Act
        $view = $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);

        // Assert
        $this->assertSame(CartHandoffController::TEMPLATE_INVALID, $view->getTemplate());
    }

    public function testGivenDelegatedAccessIsNotConfiguredWhenTheLinkIsOpenedThenThePageIsNotFoundAndNoTokenIsParkedOrRedeemed(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');
        $this->customerClientMock->method('isLoggedIn')->willReturn(false);

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('redeemCartHandoff');

        // Act
        $notFoundHttpException = null;
        try {
            $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);
        } catch (NotFoundHttpException $notFoundHttpException) {
        }

        // Assert
        $this->assertInstanceOf(NotFoundHttpException::class, $notFoundHttpException);
        $this->assertNull($this->tester->getSessionValue(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN));
    }

    public function testGivenDifferentReasonsForAnInvalidHandoffWhenTheLinkIsOpenedThenTheViewIsIdentical(): void
    {
        // Arrange
        $this->signIn();
        $quoteTransfer = (new QuoteTransfer())->setIdQuote(static::ID_QUOTE);
        $this->agenticCommerceClientMock->method('redeemCartHandoff')->willReturnOnConsecutiveCalls(
            (new AgenticCommerceCartHandoffRedeemResponseTransfer())->setIsValid(false),
            (new AgenticCommerceCartHandoffRedeemResponseTransfer())->setIsValid(true)->setQuote($quoteTransfer),
        );
        $this->multiCartClientMock->method('markQuoteAsDefault')->willReturn((new QuoteResponseTransfer())->setIsSuccessful(false));
        $controller = $this->createController();

        // Act
        $viewOfRefusedRedeem = $controller->indexAction($this->tester->createRequest(), static::TOKEN);
        $viewOfRefusedDefaultCart = $controller->indexAction($this->tester->createRequest(), static::TOKEN);

        // Assert
        $this->assertSame($viewOfRefusedRedeem->getTemplate(), $viewOfRefusedDefaultCart->getTemplate());
        $this->assertSame($viewOfRefusedRedeem->getData(), $viewOfRefusedDefaultCart->getData());
    }

    public function testGivenAnAnonymousVisitorWhenTheLinkIsOpenedThenTheTokenIsParkedAndTheVisitorIsSentToLoginWithoutRedeeming(): void
    {
        // Arrange
        $this->customerClientMock->method('isLoggedIn')->willReturn(false);
        $this->agenticCommerceClientMock->expects($this->never())->method('redeemCartHandoff');

        // Act
        $response = $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
        $this->assertSame(static::TOKEN, $this->tester->getSessionValue(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN));
    }

    public function testGivenAParkedTokenWhenTheCustomerSignsInThenTheCustomerIsSentBackToTheHandoffOnce(): void
    {
        // Arrange
        $this->customerClientMock->method('isLoggedIn')->willReturn(false);
        $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);
        $customerRedirectStrategyPlugin = $this->tester->createPlugin(AgenticCommerceCartHandoffCustomerRedirectStrategyPlugin::class, $this->agenticCommerceFactory);

        // Act
        $isApplicable = $customerRedirectStrategyPlugin->isApplicable(new CustomerTransfer());
        $redirectUrl = $customerRedirectStrategyPlugin->getRedirectUrl(new CustomerTransfer());

        // Assert
        $this->assertTrue($isApplicable);
        $this->assertSame(sprintf('/agentic-commerce/cart-handoff?token=%s', static::TOKEN), $redirectUrl);
        $this->assertFalse($customerRedirectStrategyPlugin->isApplicable(new CustomerTransfer()));
        $this->assertNull($this->tester->getSessionValue(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN));
    }

    public function testGivenNoParkedTokenWhenTheRedirectStrategyIsCheckedThenItDoesNotApply(): void
    {
        // Arrange
        $customerRedirectStrategyPlugin = $this->tester->createPlugin(AgenticCommerceCartHandoffCustomerRedirectStrategyPlugin::class, $this->agenticCommerceFactory);

        // Act
        $isApplicable = $customerRedirectStrategyPlugin->isApplicable(new CustomerTransfer());

        // Assert
        $this->assertFalse($isApplicable);
    }

    public function testGivenAParkedTokenWhenTheSignedInOwnerOpensTheLinkThenTheParkedTokenIsCleared(): void
    {
        // Arrange
        $this->signIn();
        $this->tester->setSessionValue(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN, static::TOKEN);
        $this->agenticCommerceClientMock->method('redeemCartHandoff')
            ->willReturn((new AgenticCommerceCartHandoffRedeemResponseTransfer())->setIsValid(false));

        // Act
        $this->createController()->indexAction($this->tester->createRequest(), static::TOKEN);

        // Assert
        $this->assertNull($this->tester->getSessionValue(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN));
    }

    protected function signIn(): void
    {
        $this->customerClientMock->method('isLoggedIn')->willReturn(true);
        $this->customerClientMock->method('getCustomer')->willReturn(
            (new CustomerTransfer())
                ->setCustomerReference(static::CUSTOMER_REFERENCE)
                ->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid(static::COMPANY_USER_UUID)),
        );
    }

    protected function createController(): CartHandoffController
    {
        return $this->tester->createController(CartHandoffController::class, $this->agenticCommerceFactory);
    }
}
