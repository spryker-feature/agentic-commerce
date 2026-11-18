<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Key;

use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

/**
 * A delegated access refresh token the storefront could decrypt would reach the storefront refresh grant, so the two encryption keys must differ.
 */
class RefreshTokenEncryptionKeyDistinctnessValidator implements RefreshTokenEncryptionKeyDistinctnessValidatorInterface
{
    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    public function assertRefreshTokenEncryptionKeyDiffers(): void
    {
        if (hash_equals($this->agenticCommerceConfig->getStorefrontEncryptionKey(), $this->agenticCommerceConfig->getRefreshTokenEncryptionKey())) {
            throw new AgenticCommerceConfigurationException(
                'The delegated access refresh token encryption key equals the storefront OAuth encryption key; configure a key of its own.',
            );
        }
    }
}
