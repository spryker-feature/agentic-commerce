<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Plugin\ZedRequest;

use Generated\Shared\Transfer\AgenticCommerceRequestMetaDataTransfer;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\ZedRequestExtension\Dependency\Plugin\MetaDataProviderPluginInterface;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * Register it in `\Pyz\Client\ZedRequest\ZedRequestDependencyProvider::getMetaDataProviderPlugins()` under the key `META_DATA_KEY`.
 *
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface getClient()
 */
class AgenticCommerceZedRequestMetaDataProviderPlugin extends AbstractPlugin implements MetaDataProviderPluginInterface
{
    /**
     * Specification:
     * - The key the meta transfer travels under; Zed reads it with the same key.
     *
     * @api
     */
    public const string META_DATA_KEY = AgenticCommerceConfig::ZED_REQUEST_META_DATA_KEY;

    /**
     * {@inheritDoc}
     * - Sends the consent uuid of the authenticated delegated request with every Zed request, so Zed knows the consent the call runs under.
     * - Returns an `AgenticCommerceRequestMetaDataTransfer` without a consent uuid when the request is not delegated.
     *
     * @api
     */
    public function getRequestMetaData(TransferInterface $transfer): TransferInterface
    {
        return (new AgenticCommerceRequestMetaDataTransfer())
            ->setConsentUuid($this->getClient()->findAuthenticatedConsentUuid());
    }
}
