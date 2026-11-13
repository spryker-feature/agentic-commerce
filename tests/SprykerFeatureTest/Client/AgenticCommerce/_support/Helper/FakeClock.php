<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\Helper;

class FakeClock
{
    protected const int START_TIMESTAMP = 1_800_000_000;

    protected int $timestamp = self::START_TIMESTAMP;

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function advanceSeconds(int $seconds): void
    {
        $this->timestamp += $seconds;
    }
}
