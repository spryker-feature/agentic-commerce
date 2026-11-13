<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Plugin\Oauth;

use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationResponseTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\OauthExtension\Dependency\Plugin\AccessTokenValidatorPluginInterface;

/**
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface getClient()
 */
class AgenticCommerceTokenRejectingAccessTokenValidatorPlugin extends AbstractPlugin implements AccessTokenValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Keeps storefront applications from accepting delegated access tokens, also when both share one key pair.
     * - Marks the response invalid with an `invalid_token` error for a token that carries the consent uuid claim or names the delegated access resource URI in its audience.
     * - Returns the response unchanged for any other token.
     *
     * @api
     */
    public function validate(
        OauthAccessTokenValidationRequestTransfer $oauthAccessTokenValidationRequestTransfer,
        OauthAccessTokenValidationResponseTransfer $oauthAccessTokenValidationResponseTransfer
    ): OauthAccessTokenValidationResponseTransfer {
        return $this->getClient()->rejectAgenticCommerceToken(
            $oauthAccessTokenValidationRequestTransfer,
            $oauthAccessTokenValidationResponseTransfer,
        );
    }
}
