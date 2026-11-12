<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;

interface AgenticCommerceEntityManagerInterface
{
    public function createAgenticCommerceClient(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer
    ): AgenticCommerceClientTransfer;

    /**
     * Returns true only when a client with the identifier existed and its status changed.
     */
    public function updateAgenticCommerceClientStatus(string $clientIdentifier, string $status): bool;

    public function createAgenticCommerceConsent(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): AgenticCommerceConsentTransfer;

    /**
     * Moves an `active` consent to `revoked` and clears its refresh token identifier.
     * Returns false when the consent was not `active`, so of two concurrent revocations only one returns true.
     */
    public function revokeAgenticCommerceConsent(int $idAgenticCommerceConsent, string $revokedBy): bool;

    /**
     * Writes nothing when the stored `last_used_at` is less than `$writeIntervalInSeconds` old.
     */
    public function updateAgenticCommerceConsentLastUsedAt(int $idAgenticCommerceConsent, int $writeIntervalInSeconds): void;

    /**
     * Replaces the identifier only on an `active`, unexpired consent that still holds `$currentRefreshTokenIdentifier`,
     * so of two refreshes presenting the same token only one returns true.
     */
    public function updateAgenticCommerceConsentRefreshTokenIdentifier(
        int $idAgenticCommerceConsent,
        ?string $currentRefreshTokenIdentifier,
        string $newRefreshTokenIdentifier
    ): bool;

    /**
     * @param list<string> $grantedScopes
     */
    public function updateAgenticCommerceConsentGrantedScopes(int $idAgenticCommerceConsent, array $grantedScopes): void;

    public function createAgenticCommerceConsentEvent(
        AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer
    ): AgenticCommerceConsentEventTransfer;
}
