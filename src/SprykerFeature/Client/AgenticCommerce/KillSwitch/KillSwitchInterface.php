<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\KillSwitch;

interface KillSwitchInterface
{
    public function isEnabled(string $storeName): bool;

    public function readIsEnabled(string $storeName): bool;

    public function disable(string $storeName): void;

    public function enable(string $storeName): void;
}
