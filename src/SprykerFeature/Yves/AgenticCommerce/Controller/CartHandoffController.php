<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Controller;

use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemRequestTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Yves\Kernel\View\View;
use SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin;
use SprykerShop\Yves\ShopApplication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class CartHandoffController extends AbstractController
{
    /**
     * @uses \SprykerShop\Yves\CartPage\Plugin\Router\CartPageRouteProviderPlugin::ROUTE_NAME_CART
     */
    protected const string ROUTE_NAME_CART = 'cart';

    public const string TEMPLATE_INVALID = '@AgenticCommerce/views/cart-handoff/invalid.twig';

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function indexAction(Request $request, string $token): View|RedirectResponse
    {
        if (!$this->getFactory()->getConfig()->isConfigured()) {
            throw new NotFoundHttpException();
        }

        $cartHandoffTokenParker = $this->getFactory()->createCartHandoffTokenParker();

        if (!$this->getFactory()->getCustomerClient()->isLoggedIn()) {
            $cartHandoffTokenParker->park($token);

            return $this->redirectResponseInternal(CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN);
        }

        $cartHandoffTokenParker->clear();
        $quoteTransfer = $this->redeemToken($token);

        if ($quoteTransfer === null || !$this->getFactory()->getMultiCartClient()->markQuoteAsDefault($quoteTransfer)->getIsSuccessful()) {
            return $this->view([], [], static::TEMPLATE_INVALID);
        }

        return $this->redirectResponseInternal(static::ROUTE_NAME_CART);
    }

    protected function redeemToken(string $token): ?QuoteTransfer
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();
        $agenticCommerceCartHandoffRedeemResponseTransfer = $this->getFactory()->getAgenticCommerceClient()->redeemCartHandoff(
            (new AgenticCommerceCartHandoffRedeemRequestTransfer())
                ->setToken($token)
                ->setCustomerReference($customerTransfer?->getCustomerReference())
                ->setCompanyUserUuid($customerTransfer?->getCompanyUserTransfer()?->getUuid())
                ->setStoreName($this->getFactory()->getStoreClient()->getCurrentStore()->getName()),
        );

        return $agenticCommerceCartHandoffRedeemResponseTransfer->getIsValid() ? $agenticCommerceCartHandoffRedeemResponseTransfer->getQuote() : null;
    }
}
