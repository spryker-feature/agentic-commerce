<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Token;

use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationResponseTransfer;

interface AgenticCommerceAccessTokenValidatorInterface
{
    public function validateAgenticCommerceAccessToken(string $accessToken): AgenticCommerceAccessTokenValidationResponseTransfer;

    public function rejectAgenticCommerceToken(
        OauthAccessTokenValidationRequestTransfer $oauthAccessTokenValidationRequestTransfer,
        OauthAccessTokenValidationResponseTransfer $oauthAccessTokenValidationResponseTransfer
    ): OauthAccessTokenValidationResponseTransfer;
}
