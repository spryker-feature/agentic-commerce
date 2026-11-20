<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Authentication;

use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Client\Session\SessionClientInterface;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;

class FreshAuthenticationChecker implements FreshAuthenticationCheckerInterface
{
    protected const string SESSION_KEY_AUTHENTICATED_AT = 'agentic_commerce.authenticated_at';

    protected const string KEY_CUSTOMER_REFERENCE = 'customerReference';

    protected const string KEY_AUTHENTICATED_AT = 'authenticatedAt';

    public function __construct(
        protected SessionClientInterface $sessionClient,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function recordAuthentication(?CustomerTransfer $customerTransfer): void
    {
        if ($customerTransfer === null) {
            return;
        }

        $this->sessionClient->set(static::SESSION_KEY_AUTHENTICATED_AT, [
            static::KEY_CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
            static::KEY_AUTHENTICATED_AT => time(),
        ]);
    }

    public function isAuthenticationFresh(CustomerTransfer $customerTransfer): bool
    {
        $authentication = $this->sessionClient->get(static::SESSION_KEY_AUTHENTICATED_AT);

        if (
            !is_array($authentication)
            || !is_int($authentication[static::KEY_AUTHENTICATED_AT] ?? null)
            || $customerTransfer->getCustomerReference() === null
            || ($authentication[static::KEY_CUSTOMER_REFERENCE] ?? null) !== $customerTransfer->getCustomerReference()
        ) {
            return false;
        }

        return time() - $authentication[static::KEY_AUTHENTICATED_AT] <= $this->agenticCommerceConfig->getFreshAuthenticationWindowInSeconds();
    }
}
