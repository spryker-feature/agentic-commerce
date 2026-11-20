<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\CartHandoff;

interface CartHandoffTokenParkerInterface
{
    public function park(string $token): void;

    public function findParked(): ?string;

    public function clear(): void;
}
