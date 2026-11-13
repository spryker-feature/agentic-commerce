<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Expander;

use Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;

class SecurityBlockerConfigurationSettingsExpander implements SecurityBlockerConfigurationSettingsExpanderInterface
{
    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer> $securityBlockerConfigurationSettingsTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\SecurityBlockerConfigurationSettingsTransfer>
     */
    public function expand(array $securityBlockerConfigurationSettingsTransfers): array
    {
        foreach ($this->agenticCommerceConfig->getRateLimits() as $type => $rateLimit) {
            $securityBlockerConfigurationSettingsTransfers[$type] = (new SecurityBlockerConfigurationSettingsTransfer())
                ->setNumberOfAttempts($rateLimit[AgenticCommerceConfig::RATE_LIMIT_NUMBER_OF_ATTEMPTS])
                ->setTtl($rateLimit[AgenticCommerceConfig::RATE_LIMIT_TTL_IN_SECONDS])
                ->setBlockFor($rateLimit[AgenticCommerceConfig::RATE_LIMIT_BLOCK_FOR_IN_SECONDS]);
        }

        return $securityBlockerConfigurationSettingsTransfers;
    }
}
