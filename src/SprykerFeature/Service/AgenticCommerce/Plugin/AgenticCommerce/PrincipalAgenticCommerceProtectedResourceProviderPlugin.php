<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Plugin\AgenticCommerce;

use Spryker\Service\Kernel\AbstractPlugin;
use SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface;

/**
 * @method \SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceFactory getFactory()
 */
class PrincipalAgenticCommerceProtectedResourceProviderPlugin extends AbstractPlugin implements AgenticCommerceProtectedResourceProviderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Declares the principal resource, served at the path of the configured resource URI.
     * - Declares nothing while no resource URI is configured.
     * - A resource URI whose path is `/` or ends with `/` fails the declaration checks of `AgenticCommerceServiceInterface::getProtectedResources()`: authorization and token requests throw, and the Glue storefront logs the error and treats no resource as declared.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResources(): array
    {
        return $this->getFactory()->createProtectedResourceDeclarationBuilder()->buildPrincipalProtectedResources();
    }
}
