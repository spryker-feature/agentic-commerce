<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Plugin\CustomerPage;

use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerFeature\Yves\AgenticCommerce\Controller\AuthorizeController;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use SprykerShop\Yves\CustomerPageExtension\Dependency\Plugin\CustomerRedirectStrategyPluginInterface;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AgenticCommerceAuthorizationCustomerRedirectStrategyPlugin extends AbstractPlugin implements CustomerRedirectStrategyPluginInterface
{
    /**
     * {@inheritDoc}
     * - Applies when a delegated access authorization request, validated before the customer was asked to sign in, still waits in the session.
     * - The target comes from the session, never from request parameters, so this is no open redirect.
     *
     * @api
     */
    public function isApplicable(CustomerTransfer $customerTransfer): bool
    {
        return $this->getFactory()->createAuthorizationRequestParker()->findPendingAuthorizationRequestId() !== null;
    }

    /**
     * {@inheritDoc}
     * - Returns the consent page URL of the waiting authorization request and stops waiting for it, so later sign-ins land where they normally do.
     *
     * @api
     */
    public function getRedirectUrl(CustomerTransfer $customerTransfer): string
    {
        $authorizationRequestParker = $this->getFactory()->createAuthorizationRequestParker();
        $authorizationRequestId = (string)$authorizationRequestParker->findPendingAuthorizationRequestId();
        $authorizationRequestParker->clearPending();

        return $this->getFactory()->getRouter()->generate(
            AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CONSENT,
            [AuthorizeController::PARAMETER_AUTHORIZATION_REQUEST => $authorizationRequestId],
        );
    }
}
