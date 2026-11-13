<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Plugin\SecurityBlocker;

use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Client\SecurityBlockerExtension\Dependency\Plugin\SecurityBlockerConfigurationSettingsExpanderPluginInterface;

/**
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface getClient()
 */
class AgenticCommerceSecurityBlockerConfigurationSettingsExpanderPlugin extends AbstractPlugin implements SecurityBlockerConfigurationSettingsExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Adds the settings of the delegated access rate limits: registration, token, failed token grants, authorization and metadata document fetches.
     * - Keeps the settings of every other type.
     *
     * @api
     *
     * @param array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer> $securityBlockerConfigurationSettingsTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer>
     */
    public function expand(array $securityBlockerConfigurationSettingsTransfers): array
    {
        return $this->getClient()->expandAgenticCommerceSecurityBlockerConfigurationSettings($securityBlockerConfigurationSettingsTransfers);
    }
}
