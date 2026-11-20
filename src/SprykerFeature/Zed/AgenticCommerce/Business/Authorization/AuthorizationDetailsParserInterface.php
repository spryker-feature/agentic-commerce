<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsParseResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;

interface AuthorizationDetailsParserInterface
{
    /**
     * A missing or empty parameter is valid and requests nothing.
     * Each requested constraint carries the stored constraint type and the value without `type`.
     */
    public function parse(
        ?string $authorizationDetails,
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): AgenticCommerceAuthorizationDetailsParseResponseTransfer;
}
