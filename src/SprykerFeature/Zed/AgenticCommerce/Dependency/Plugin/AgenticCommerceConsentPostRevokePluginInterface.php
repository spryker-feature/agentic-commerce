<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;

/**
 * Use this plugin to react after delegated-access consents were revoked, for example to drop state a feature keeps per consent.
 */
interface AgenticCommerceConsentPostRevokePluginInterface
{
    /**
     * Specification:
     * - Is executed once per revocation, with every consent it revoked, after the revocation's own transaction completes.
     * - Is executed the same way once per housekeeping batch, with every consent that batch moved to `expired`.
     * - When the revocation was called inside a transaction its caller holds, it is executed before that caller commits, so the revoked state may still roll back.
     * - An exception it throws is not caught: it reaches the caller of the revocation, and the plugins after it in the stack are not executed.
     *
     * @api
     */
    public function postRevoke(AgenticCommerceConsentCollectionTransfer $agenticCommerceConsentCollectionTransfer): void;
}
