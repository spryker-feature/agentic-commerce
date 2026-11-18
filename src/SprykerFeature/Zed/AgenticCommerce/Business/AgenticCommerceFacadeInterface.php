<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataDocumentTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;

interface AgenticCommerceFacadeInterface
{
    /**
     * Specification:
     * - Registers pre-registered delegated-access clients: creates a public (non-confidential) `spy_oauth_client` and its `spy_agentic_commerce_client` per item.
     * - Validates that `AgenticCommerceClientTransfer.clientIdentifier` is set and unused, including within the same request.
     * - Validates that `AgenticCommerceClientTransfer.name` is set.
     * - Validates that `AgenticCommerceClientTransfer.redirectUris` is not empty and every URI is absolute `https`, or `http` to a loopback host spelled exactly `127.0.0.1`, `localhost` or `[::1]` (another spelling such as `LOCALHOST` is refused, because redirect URIs match by exact string comparison), without a fragment, user information or whitespace.
     * - Validates that `AgenticCommerceClientTransfer.scopeCeiling` is not empty and contains only supported scopes.
     * - Validates that `AgenticCommerceClientTransfer.clientUri` and `.logoUri`, when set, are absolute `https` URIs without a fragment, user information or whitespace, and at most 2048 characters long.
     * - Validates that `AgenticCommerceClientTransfer.trustLevel`, when set, is one of the `AgenticCommerceConfig::CLIENT_TRUST_LEVEL_*` values.
     * - Adds an error per failed rule, with `ErrorTransfer.entityIdentifier` set to the item index.
     * - Persists nothing when `AgenticCommerceClientCollectionRequestTransfer.isTransactional` is `true` and any item fails validation.
     * - Persists valid items and skips invalid ones when `AgenticCommerceClientCollectionRequestTransfer.isTransactional` is `false`.
     * - Sets registration type `pre_registered` and status `active` on persisted items, and trust level `verified` when none is given.
     *
     * @api
     */
    public function createAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer;

    /**
     * Specification:
     * - Returns delegated-access clients filtered by `AgenticCommerceClientCriteriaTransfer.agenticCommerceClientConditions`.
     * - Filters by `clientIdentifiers`, `statuses`, `registrationTypes`, `softwareIds`, `redirectHosts` and `blockedByValues` (exact `blocked_by` values) when provided; several conditions apply together.
     * - Compares `redirectHosts` with the stored redirect URI hosts in the form of `AgenticCommerceServiceInterface::findNormalizedHost()`; a redirect host that is not valid matches no client.
     * - Returns each client with the name of its OAuth client.
     * - Sorts newest first.
     * - Supports offset/limit and page/maxPerPage pagination; sets `PaginationTransfer.nbResults`.
     *
     * @api
     */
    public function getAgenticCommerceClientCollection(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer
    ): AgenticCommerceClientCollectionTransfer;

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
     * - Replaces the scope ceiling of the delegated-access client identified by `$clientIdentifier` with `$scopeCeiling`; an empty list is a valid ceiling.
     * - Returns an error and changes nothing when `$clientIdentifier` is empty, no client has that identifier, or a scope is not supported.
     * - Returns the stored client in `agenticCommerceClients`.
     *
     * @api
     *
     * @param list<string> $scopeCeiling
     */
    public function updateAgenticCommerceClientScopeCeiling(
        string $clientIdentifier,
        array $scopeCeiling
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
     * - Requires `AgenticCommerceScopeCriteriaTransfer.agenticCommerceScopeConditions`.
     * - Returns one `AgenticCommerceScopeTransfer` per name of `AgenticCommerceScopeConditionsTransfer.names`, in the same order.
     * - Sets the glossary key of the consent label that the first `AgenticCommerceScopeProviderPluginInterface` declaring the scope names; any other scope, including one that only the deprecated `AgenticCommerceConstants::SUPPORTED_SCOPES` lists, gets `agentic_commerce.scope.<name>` with `:` replaced by `.`.
     * - Throws `AgenticCommerceConfigurationException` when the declared scopes are invalid, as `getAgenticCommerceSupportedScopes()` does.
     *
     * @api
     */
    public function getAgenticCommerceScopeCollection(
        AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer
    ): AgenticCommerceScopeCollectionTransfer;

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

    /**
     * Specification:
     * - Returns the delegated-access scopes of all `AgenticCommerceScopeProviderPluginInterface` plugins in plugin order, followed by the scopes of the deprecated `AgenticCommerceConstants::SUPPORTED_SCOPES`.
     * - Returns the first occurrence of a scope name only; a scope that only the config key lists gets the glossary key `agentic_commerce.scope.<name>` with `:` replaced by `.`.
     * - Throws `AgenticCommerceConfigurationException` naming the source and the scope when a name is not of the form `<resource>:<action>`, when its role is already taken by a different scope, or when its role is reserved.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getAgenticCommerceSupportedScopes(): array;

    /**
     * Specification:
     * - Fetches the Client ID Metadata Document (CIMD) at `$documentUrl` over HTTPS, hardened against Server-Side Request Forgery (SSRF).
     * - Returns `failureReason` `client_id_metadata_document_invalid_url` without a request when the URL scheme is not `https`.
     * - Resolves the host once; returns `failureReason` `client_id_metadata_document_address_refused` without a request when the host does not resolve or any of its addresses is private, loopback, link-local, reserved, documentation, multicast or an IETF protocol assignment.
     * - Pins the connection to the checked address and the URL's port, uses no proxy, follows no redirect and stops after the configured timeout and body size.
     * - Returns `failureReason` `client_id_metadata_document_fetch_failed` when the transfer fails, the connected address differs from the checked one, the status is not 200, the content type is not JSON or the body is not a JSON object.
     * - Otherwise returns `isFetched` true, the decoded `content` and `cacheLifetimeSeconds` from `Cache-Control`, clamped to the configured bounds.
     * - Sets `connectedAddress` to the address the transfer reached, when one was reached.
     *
     * @api
     */
    public function fetchAgenticCommerceClientMetadataDocument(string $documentUrl): AgenticCommerceClientMetadataDocumentTransfer;

    /**
     * Specification:
     * - Registers a public client following RFC 7591 OAuth 2.0 Dynamic Client Registration Protocol.
     * - Returns an unsuccessful response with `errorCode` `invalid_client_metadata` when dynamic registration is disabled by configuration.
     * - Returns `errorCode` `invalid_client_metadata` when `rawRequest` is larger than `AgenticCommerceConfig::getDynamicClientRegistrationRequestMaxBytes()`, the size limit of a Client ID Metadata Document (CIMD).
     * - Requires `rawRequest` to be a JSON object, else returns `errorCode` `invalid_client_metadata`; a registration field of the wrong type counts as absent, an empty `client_uri` or `logo_uri` as absent.
     * - Validates the metadata; in allowlist mode every redirect URI host must be on the configured allowlist, loopback redirect URIs are always allowed.
     * - Returns `errorCode` `invalid_client_metadata` when `client_name` is blank or contains a control character or a Unicode bidirectional embedding, override or isolate; an empty `client_name` counts as absent.
     * - Returns `errorCode` `invalid_client_metadata` when a blocked client carries the family block `software_id:<software_id>`, and `invalid_redirect_uri` when a redirect URI is on a host that a blocked client carries as its family block `redirect_host:<host>`, compared ignoring letter case; loopback redirect URIs are left out. A client blocked without a family block refuses nothing.
     * - Creates an OAuth client with a random UUID version 4 identifier and a delegated-access client with registration type `dynamic` and trust level `unverified`.
     * - Scope ceiling: the configured ceiling for the request's `software_id` if the shop listed it, else the configured default; never from the request. `software_id` is self-asserted, so the map is a convenience, not a trust signal.
     * - Stores `rawRequest` as the client's `registrationMetadata`.
     * - Returns the created client, `clientIdIssuedAt`, the requested scopes that are supported as `acceptedScopes`, and the accepted `grantTypes`.
     *
     * @api
     */
    public function registerAgenticCommerceClient(
        AgenticCommerceClientRegistrationRequestTransfer $agenticCommerceClientRegistrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer;

    /**
     * Specification:
     * - Returns the consent uuid of the delegated Glue request the current Zed request is made for.
     * - Returns `null` outside a backend gateway request and for every Zed request that is not made for a delegated request.
     *
     * @api
     */
    public function findAgenticCommerceRequestConsentUuid(): ?string;
}
