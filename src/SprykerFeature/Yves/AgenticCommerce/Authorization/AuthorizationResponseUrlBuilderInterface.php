<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;

interface AuthorizationResponseUrlBuilderInterface
{
    public function buildCodeResponseUrl(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        string $authorizationCode
    ): string;

    public function buildErrorResponseUrl(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        string $errorCode,
        ?string $errorDescription = null
    ): string;
}
