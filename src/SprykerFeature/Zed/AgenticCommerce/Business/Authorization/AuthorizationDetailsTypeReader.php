<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface;

class AuthorizationDetailsTypeReader implements AuthorizationDetailsTypeReaderInterface
{
    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $agenticCommerceConsentConstraintPlugins
     */
    public function __construct(protected array $agenticCommerceConsentConstraintPlugins)
    {
    }

    public function getAuthorizationDetailsTypeCollection(): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer
    {
        $agenticCommerceAuthorizationDetailsTypeCollectionTransfer = new AgenticCommerceAuthorizationDetailsTypeCollectionTransfer();

        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if ($agenticCommerceConsentConstraintPlugin instanceof AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface) {
                $agenticCommerceAuthorizationDetailsTypeCollectionTransfer->addAuthorizationDetailsType($agenticCommerceConsentConstraintPlugin->getAuthorizationDetailsType());
            }
        }

        return $agenticCommerceAuthorizationDetailsTypeCollectionTransfer;
    }
}
