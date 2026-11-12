<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;

interface AgenticCommerceFacadeInterface
{
    /**
     * Specification:
     * - Sets the status of each delegated-access client identified by `AgenticCommerceClientTransfer.clientIdentifier` to `AgenticCommerceClientTransfer.status`.
     * - Requires `AgenticCommerceClientTransfer.clientIdentifier` and `AgenticCommerceClientTransfer.status` per item.
     * - Adds an error when `status` is neither `active` nor `blocked`, when `clientIdentifier` is empty, or when no delegated-access client has that identifier.
     * - Adds an error per failed item, with `ErrorTransfer.entityIdentifier` set to the item index.
     * - Changes nothing when `AgenticCommerceClientCollectionRequestTransfer.isTransactional` is `true` or not set and any item fails validation; updates the valid items otherwise.
     * - On `blocked`, revokes every active consent of the client with `revokedBy` `system_client_blocked`, as `revokeAgenticCommerceConsentsBySystem()` does, and returns the revoked consents in `agenticCommerceConsents`.
     * - On `active`, revokes nothing; consents revoked while the client was blocked stay revoked.
     * - Sets the statuses and revokes the consents in one transaction for all items when transactional, in one transaction per item otherwise; when a revocation fails, its status change rolls back.
     * - Setting the status the client already has is not an error.
     * - Returns the updated clients in `agenticCommerceClients` on success; returns the request's clients unchanged when transactional and any item fails validation; returns every client in request order when not transactional, updated ones as stored and failed ones as given.
     *
     * @api
     */
    public function updateAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer;

    /**
     * Specification:
     * - Revokes consents on behalf of the system, without an ownership check.
     * - Requires exactly one selector: `consentUuids`, `principalType` together with `principalReference`, `customerReference` or `clientIdentifier`; returns an error otherwise.
     * - Requires `revokedBy` to be one of the `AgenticCommerceConfig::CONSENT_REVOKED_BY_*` values; returns an error otherwise.
     * - Revokes only consents that are active and not past `expiresAt`; any other selected consent is left as it is.
     * - In one transaction per request: sets status `revoked`, `revokedAt` and `revokedBy`, clears the refresh token identifier, and writes a consent event with actor `revokedBy`.
     * - The event is `reuse_detected` for `system_reuse_detected`, `code_replayed` for `system_code_replay` and `revoked` otherwise.
     * - After this method's own transaction completes, executes the stack of `AgenticCommerceConsentPostRevokePluginInterface` once with all consents it revoked.
     * - When called inside a transaction the caller holds, the stack therefore runs before the caller commits; it runs after the database commit only when no outer transaction is open.
     * - Returns the revoked consents.
     *
     * @api
     */
    public function revokeAgenticCommerceConsentsBySystem(
        AgenticCommerceConsentRevocationRequestTransfer $agenticCommerceConsentRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    /**
     * Specification:
     * - Returns consents filtered by `AgenticCommerceConsentCriteriaTransfer.agenticCommerceConsentConditions`.
     * - Filters by `uuids`, `customerReferences`, `principalTypes`, `principalReferences` and `clientIdentifiers` when provided; several conditions apply together.
     * - Filters by `statuses` using the effective status: a consent past `expiresAt` counts as `expired`, never as `active`.
     * - Returns each consent with its effective status, principal, client and store name.
     * - Sorts by `createdAt`, `expiresAt` or `lastUsedAt` via `sortCollection`; other fields are ignored; defaults to newest first.
     * - Supports offset/limit and page/maxPerPage pagination.
     *
     * @api
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    /**
     * Specification:
     * - Revokes consents on behalf of the customer who gave them: the only supported transition is to `AgenticCommerceConsentTransfer.status` = `revoked`.
     * - Requires `AgenticCommerceConsentTransfer.uuid` and `AgenticCommerceConsentTransfer.customerReference`; a consent of another customer is reported exactly like an unknown one, as not found.
     * - Adds an error when the consent is not active, including consents already revoked or past `expiresAt`.
     * - Adds an error per failed item, with `ErrorTransfer.entityIdentifier` set to the item index.
     * - Revokes nothing when `AgenticCommerceConsentCollectionRequestTransfer.isTransactional` is `true` or not set and any item fails validation; revokes the valid items otherwise.
     * - An item whose consent is revoked concurrently, or appears twice in the request, reports `not_active`; the other items stay revoked, also in a transactional request.
     * - Revokes as `revokeAgenticCommerceConsentsBySystem()` does, with `revokedBy` `customer`: one transaction, status `revoked`, `revokedAt`, cleared refresh token identifier, event `revoked` with actor `customer`, then the post-revoke plugin stack once with all consents it revoked.
     * - Returns the request's consents unchanged when a transactional request fails validation; otherwise returns every item in request order: the revoked consent for a revoked item, the request item for a failed one.
     *
     * @api
     */
    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;
}
