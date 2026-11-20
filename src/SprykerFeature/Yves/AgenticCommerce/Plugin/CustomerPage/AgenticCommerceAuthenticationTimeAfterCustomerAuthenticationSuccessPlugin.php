<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Plugin\CustomerPage;

use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerShop\Yves\CustomerPageExtension\Dependency\Plugin\AfterCustomerAuthenticationSuccessPluginInterface;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AgenticCommerceAuthenticationTimeAfterCustomerAuthenticationSuccessPlugin extends AbstractPlugin implements AfterCustomerAuthenticationSuccessPluginInterface
{
    /**
     * {@inheritDoc}
     * - Records in the session when the customer completed a full sign-in, after multi-factor authentication where it applies.
     * - Binds the record to the reference of the customer the sign-in put into the session; another customer in the same session never counts as freshly signed in.
     * - Records nothing when the session holds no customer.
     * - Connecting an app requires this sign-in to be recent; a remember-me login never records it.
     * - A customer SSO sign-in runs no after-authentication plugins, so it records nothing: connecting an app needs a password sign-in.
     *
     * @api
     */
    public function execute(): void
    {
        $this->getFactory()->createFreshAuthenticationChecker()->recordAuthentication($this->getFactory()->getCustomerClient()->getCustomer());
    }
}
