<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Plugin\CustomerPage;

use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use SprykerShop\Yves\CustomerPageExtension\Dependency\Plugin\CustomerRedirectStrategyPluginInterface;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AgenticCommerceCartHandoffCustomerRedirectStrategyPlugin extends AbstractPlugin implements CustomerRedirectStrategyPluginInterface
{
    /**
     * {@inheritDoc}
     * - Applies when a cart hand-off link was opened before the customer signed in and its token still waits in the session.
     * - The target comes from the session, never from request parameters, so this is no open redirect.
     *
     * @api
     */
    public function isApplicable(CustomerTransfer $customerTransfer): bool
    {
        return $this->getFactory()->createCartHandoffTokenParker()->findParked() !== null;
    }

    /**
     * {@inheritDoc}
     * - Returns the hand-off URL of the waiting token and stops waiting for it, so later sign-ins land where they normally do.
     *
     * @api
     */
    public function getRedirectUrl(CustomerTransfer $customerTransfer): string
    {
        $cartHandoffTokenParker = $this->getFactory()->createCartHandoffTokenParker();
        $token = (string)$cartHandoffTokenParker->findParked();
        $cartHandoffTokenParker->clear();

        return $this->getFactory()->getRouter()->generate(
            AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CART_HANDOFF,
            [AgenticCommerceRouteProviderPlugin::PARAMETER_TOKEN => $token],
        );
    }
}
