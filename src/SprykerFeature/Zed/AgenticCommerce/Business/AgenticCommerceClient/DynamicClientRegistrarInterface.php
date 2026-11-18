<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;

interface DynamicClientRegistrarInterface
{
    /**
     * Registers a public client from the raw JSON request of RFC 7591 (OAuth 2.0 Dynamic Client Registration Protocol), when Dynamic Client Registration (DCR) is enabled.
     */
    public function registerClient(
        AgenticCommerceClientRegistrationRequestTransfer $registrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer;
}
