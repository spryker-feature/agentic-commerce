<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Plugin\ZedRequest;

use Generated\Shared\Transfer\AgenticCommerceActingCompanyUserTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ZedRequestExtension\Dependency\Plugin\MetaDataProviderPluginInterface;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * Register it in `\Pyz\Client\ZedRequest\ZedRequestDependencyProvider::getMetaDataProviderPlugins()` under the key `META_DATA_KEY`.
 *
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AgenticCommerceActingCompanyUserZedRequestMetaDataProviderPlugin extends AbstractPlugin implements MetaDataProviderPluginInterface
{
    /**
     * Specification:
     * - The key the meta transfer travels under; Zed reads it with the same key.
     *
     * @api
     */
    public const string META_DATA_KEY = AgenticCommerceConfig::ZED_REQUEST_ACTING_COMPANY_USER_META_DATA_KEY;

    /**
     * {@inheritDoc}
     * - Sends the company user uuid of the customer in the storefront session with every Zed request, so a consent revocation caused by the call records the acting company admin.
     * - Attaches the session company user to every Zed request, not only to company admin actions, so a cascade a company user triggers on their own account is recorded as `company_admin` with their own uuid.
     * - Returns an `AgenticCommerceActingCompanyUserTransfer` without a company user uuid when the session holds no customer or the customer acts as no company user.
     * - Reads the session customer without executing the customer session plugins, since one of them may itself make a Zed request.
     *
     * @api
     */
    public function getRequestMetaData(TransferInterface $transfer): TransferInterface
    {
        return (new AgenticCommerceActingCompanyUserTransfer())
            ->setCompanyUserUuid($this->getFactory()->getCustomerClient()->findCustomerRawData()?->getCompanyUserTransfer()?->getUuid());
    }
}
