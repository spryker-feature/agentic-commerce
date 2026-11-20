<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;

interface AuthorizationRequestParkerInterface
{
    public function park(AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer): string;

    public function findParkedAuthorization(string $authorizationRequestId): ?AgenticCommerceAuthorizationResponseTransfer;

    public function remove(string $authorizationRequestId): void;

    public function markPending(string $authorizationRequestId): void;

    public function findPendingAuthorizationRequestId(): ?string;

    public function clearPending(): void;
}
