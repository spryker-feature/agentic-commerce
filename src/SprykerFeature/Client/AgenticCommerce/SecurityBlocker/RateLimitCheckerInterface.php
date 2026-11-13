<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\SecurityBlocker;

use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;

interface RateLimitCheckerInterface
{
    public function checkRateLimit(string $type, string $ip, ?string $account): SecurityCheckAuthResponseTransfer;

    public function incrementFailedAttempt(string $ip, ?string $clientId): void;

    public function forgetFailure(): void;
}
