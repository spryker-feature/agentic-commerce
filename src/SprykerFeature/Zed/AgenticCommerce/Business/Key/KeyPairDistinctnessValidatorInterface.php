<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Key;

interface KeyPairDistinctnessValidatorInterface
{
    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    public function assertKeyPairsDiffer(): void;
}
