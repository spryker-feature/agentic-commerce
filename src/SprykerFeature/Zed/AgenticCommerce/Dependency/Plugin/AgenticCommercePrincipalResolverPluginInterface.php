<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;

/**
 * Use this plugin to let a customer act through a delegated-access consent as a specific principal, for example as one of their company users.
 */
interface AgenticCommercePrincipalResolverPluginInterface
{
    /**
     * Specification:
     * - Returns the principal type this plugin resolves, e.g. `company_user`.
     * - The value is stored in `spy_agentic_commerce_consent.principal_type`.
     *
     * @api
     */
    public function getPrincipalType(): string;

    /**
     * Specification:
     * - Returns the principals the customer may choose on the consent page; empty when this plugin does not apply to the customer.
     * - The resolver stack is first-applicable: the first plugin in stack order that returns at least one principal supplies all candidates.
     * - Every returned principal has `principalType`, `principalReference`, `displayName` and `customerReference` set.
     * - Is executed when the consent page lists the principals and again when a consent is created, to check the principal the customer submitted.
     *
     * @api
     */
    public function getPrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer;

    /**
     * Specification:
     * - Returns the OAuth user identifier storefront tokens carry for this principal, so storefront business logic resolves it unchanged.
     * - Is called when a consent is created, to refuse a principal without an identifier, on token issue and again on every refresh, so the identifier always reflects the current principal.
     * - Returns `null` when the principal does not exist or does not belong to `AgenticCommercePrincipalTransfer.customerReference`.
     *
     * @api
     */
    public function buildUserIdentifier(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): ?string;

    /**
     * Specification:
     * - Returns `true` when the principal still exists, still belongs to the customer and is active.
     * - Is called on token issue, again on every refresh, on an API request whose consent check is not served from the consent cache, and once on every API request that reads the agentic commerce context, whether or not its consent check was served from the cache.
     * - On `false` during token issue, refresh or the consent check, the consent is revoked with `revokedBy` `system_principal_inactive` and the request is refused.
     * - On `false` while the agentic commerce context is read, the request gets no context and the consent is not revoked.
     *
     * @api
     */
    public function isPrincipalActive(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): bool;
}
