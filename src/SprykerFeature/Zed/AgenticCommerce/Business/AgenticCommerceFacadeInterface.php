<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
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
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\CustomerTransfer;

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
     * - Sets the status of every delegated-access client that matches `AgenticCommerceClientCriteriaTransfer.agenticCommerceClientConditions`, as `updateAgenticCommerceClientCollection()` does for each of them.
     * - Requires at least one of `clientIdentifiers`, `softwareIds` and `redirectHosts`; returns an `agentic_commerce.client.validation.criteria_required` error and changes nothing when none is given or any given value is empty.
     * - Matches `redirectHosts` against the exact host of a stored redirect URI, both normalized by `AgenticCommerceServiceInterface::findNormalizedHost()`, so `evil.example` matches `Evil.Example.` but not `notevil.example`.
     * - Returns an `agentic_commerce.client.validation.redirect_host_invalid` error and changes nothing when a redirect host is not valid once normalized or is a loopback host (`127.0.0.1`, `[::1]`, `localhost`).
     * - On `blocked` with `softwareIds` or `redirectHosts`, records the family block in `blocked_by` of every blocked client: `software_id:<software id>`, or `redirect_host:<normalized host>` when no software id of the criteria matches the client (the first matching host of the criteria). A client keeps the first family block it received; a later one never replaces it. With only `clientIdentifiers`, records none.
     * - The status change and the family block are written in one transaction; on `blocked` the client's consents are revoked afterwards as `revokeAgenticCommerceConsentsBySystem()` does.
     * - Returns an error and changes nothing when `$status` is neither `active` nor `blocked`.
     * - Matching no client is not an error.
     * - Returns the updated clients in `agenticCommerceClients` and, on `blocked`, the revoked consents in `agenticCommerceConsents`.
     *
     * @api
     */
    public function updateAgenticCommerceClientStatusByCriteria(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer,
        string $status
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
     * - Validates an OAuth 2.0 authorization request (RFC 6749, The OAuth 2.0 Authorization Framework, section 4.1.1) before anything is shown to the customer.
     * - Returns `isValid=false`, `isRedirectable=false` and error `invalid_client` when the client is unknown or its status is not `active`.
     * - Returns `isValid=false`, `isRedirectable=false`, error `invalid_client` and failure reason `client_id_metadata_document_redirect_host_blocked` when a fetched Client ID Metadata Document (CIMD) has a redirect URI on a host that another client carries as its family block (`redirect_host:<host>`); loopback redirect URIs are left out.
     * - Returns `isValid=false`, `isRedirectable=false` and error `invalid_request` when `redirectUri` matches none of the client's registered redirect URIs.
     * - Redirect URIs match by exact string comparison; only for a registered loopback redirect URI (`http` to `127.0.0.1`, `[::1]` or `localhost`) the port of the requested URI is ignored (RFC 8252, OAuth 2.0 for Native Apps, section 7.3). Scheme, host, path and query always match exactly.
     * - Sets `AgenticCommerceAuthorizationRequestTransfer.isLoopbackRedirect` to `true` when the matched redirect URI is a loopback redirect URI, `false` otherwise.
     * - Returns `isRedirectable=true` with the client for every later failure only when the client is pre-registered or its trust level is `verified`; every other client (for example a `dynamic` client with trust level `unverified` or a `client_id_metadata_document` client with trust level `domain_verified`) gets `isRedirectable=false` with the client, so the caller shows its own error page instead of redirecting (RFC 9700, OAuth 2.0 Security Best Current Practice, section 4.11.2).
     * - Returns error `unsupported_response_type` when `responseType` is not `code`.
     * - Returns error `invalid_request` when `codeChallengeMethod` is not `S256` or `codeChallenge` is not a 43-character base64url value (RFC 7636, Proof Key for Code Exchange (PKCE) by OAuth Public Clients).
     * - Returns error `invalid_target` when `resource` is not the resource URI of a declared protected resource (RFC 8707, Resource Indicators for OAuth 2.0).
     * - Narrows the space-delimited `scope` to the requested scopes within the client's scope ceiling and the supported scopes; scopes outside them are dropped, never rejected.
     * - Treats a missing or empty `scope` as a request for every supported scope in the client's scope ceiling.
     * - Returns error `invalid_scope` when the narrowed set is empty and the client's scope ceiling is not; a client with an empty scope ceiling gets no error and continues without grantable scopes, so the consent page can say that the shop has granted it nothing yet.
     * - Parses `authorizationDetails` (RFC 9396, OAuth 2.0 Rich Authorization Requests) when it is not empty, with the grantable scopes, the current store and its default currency, and without a principal:
     *   - maps each object to a requested constraint of the stored constraint type, with the object without `type` as its value, of the plugins that implement `AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface`;
     *   - returns error `invalid_authorization_details` when the parameter is not JSON, not a list, longer than 4096 bytes or has more than 10 objects, when an object has no string `type` or an unknown one, when two objects have one type, or when a plugin's `validate()` returns errors for a value;
     *   - does not check whether a plugin is applicable.
     * - Returns `isValid=true` with the client, the narrowed `grantableScopes` and `requestedConstraints` otherwise.
     *
     * @api
     */
    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer;

    /**
     * Specification:
     * - Returns the principals the customer may act as through a consent, from the stack of `AgenticCommercePrincipalResolverPluginInterface`.
     * - The stack is first-applicable: returns the candidates of the first plugin in stack order that returns at least one, never a merge of several plugins.
     * - Returns an empty collection when no plugin returns a candidate.
     *
     * @api
     */
    public function getAgenticCommercePrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer;

    /**
     * Specification:
     * - Requires `AgenticCommerceGrantableScopesRequestTransfer.principal`.
     * - Passes `AgenticCommerceGrantableScopesRequestTransfer.requestedScopes` through the stack of `AgenticCommerceScopeFilterPluginInterface`, each plugin narrowing the result of the previous one.
     * - Returns only scopes that were requested, in request order, even when a plugin adds a scope.
     * - Sets `AgenticCommerceGrantableScopesResponseTransfer.scopes` to one `AgenticCommerceScopeTransfer` per grantable scope in the same order, with the glossary key of the consent label; a scope no provider declares gets `agentic_commerce.scope.<name>` with `:` replaced by `.`.
     * - The requested scopes are expected to be the `grantableScopes` of `validateAgenticCommerceAuthorizationRequest()`, so the client's scope ceiling and the supported scopes already apply.
     *
     * @api
     */
    public function getAgenticCommerceGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer;

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
     * - Returns the `AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface::getAuthorizationDetailsType()` of every wired consent constraint plugin that implements the interface, in stack order.
     * - Ignores a plugin that does not implement the interface.
     *
     * @api
     */
    public function getAgenticCommerceAuthorizationDetailsTypeCollection(
        AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer
    ): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;

    /**
     * Specification:
     * - Requires `AgenticCommerceConsentCollectionRequestTransfer.authorizationRequest` and validates it again as `validateAgenticCommerceAuthorizationRequest()` does; returns one error and persists nothing when it is invalid.
     * - Removes repeated scopes from `grantedScopes`.
     * - Validates per consent that `grantedScopes` is not empty, `durationInDays` is one of the configured consent durations, the store `storeName`, when given, is the current store, and the customer `customerReference` exists; the consent is always bound to the current store.
     * - Validates that `AgenticCommerceConsentTransfer.principal` is one of the customer's principal candidates (see `getAgenticCommercePrincipalCandidates()`) and that its resolver builds a user identifier; returns error `principal_not_found` otherwise.
     * - From then on uses only the matching server-built candidate, never fields of the submitted principal, and returns it as `AgenticCommerceConsentTransfer.principal`.
     * - Validates that every granted scope is among the grantable scopes of the principal (see `getAgenticCommerceGrantableScopes()`); returns error `scope_not_grantable` otherwise.
     * - Validates `AgenticCommerceConsentTransfer.constraints` of a consent that passed the rules above against the stack of `AgenticCommerceConsentConstraintPluginInterface`,
     *   with the principal, the granted scopes, the store name and the store's default currency:
     *   - drops a constraint whose plugin is not applicable or whose type no plugin declares;
     *   - returns error `constraint_required` when an applicable plugin requires a constraint that is missing;
     *   - returns the errors of the plugin's `validate()`;
     *   - parses `AgenticCommerceAuthorizationRequestTransfer.authorizationDetails` again, with the consent's granted scopes, store and principal, and returns error `invalid_authorization_details` when it fails;
     *   - returns error `requested_removed` for a requested constraint of an applicable plugin that is not submitted, and `wider_than_requested` when the submitted value is not narrower or equal according to `AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface::isNarrowerOrEqual()`; both with the constraint type as `ErrorTransfer.entityIdentifier`;
     *   - does not enforce a requested constraint whose plugin is not applicable.
     * - Adds an error per failed rule, with `ErrorTransfer.entityIdentifier` set to the item index.
     * - Persists nothing when `AgenticCommerceConsentCollectionRequestTransfer.isTransactional` is `true` or not set and any item fails validation; persists the valid items otherwise.
     * - In one transaction per request, per consent:
     *   - locks the consenting customer's row in `spy_agentic_commerce_consent_lock`, created before the transaction when missing, so concurrent consents of one customer run one after another;
     *   - creates the consent with status `active`, the principal and an expiry `durationInDays` from now;
     *   - stores the validated constraints of the consent;
     *   - writes event `granted`, or `re_consented` when the principal already holds an active consent for the same client and store; actor `customer`, the granted scopes, the client name shown on the consent page and the stored constraints;
     *   - issues an authorization code through `OauthCodeFlowFacadeInterface::createOauthAuthorizationCodeCollection()`, bound to the client, redirect URI, code challenge, resource and consent, with the storefront identity scopes of the principal type before the granted scopes;
     *   - revokes every other active consent of the same client, principal and store with `revokedBy` `customer`, as `revokeAgenticCommerceConsentsBySystem()` does, including its post-revoke plugin stack; consents of the customer for another store or another principal stay active; a replaced consent is tombstoned in the cache before the transaction commits, so when it rolls back, the consent stays active but is denied until the cache entry expires.
     * - Rolls the whole transaction back and returns error `authorization_code_not_issued` when no code is issued, so replaced consents stay active.
     * - Returns the created consents with `authorizationCode` set.
     *
     * @api
     */
    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    /**
     * Specification:
     * - Returns consents filtered by `AgenticCommerceConsentCriteriaTransfer.agenticCommerceConsentConditions`.
     * - Filters by `uuids`, `customerReferences`, `principalTypes`, `principalReferences` and `clientIdentifiers` when provided; several conditions apply together.
     * - Filters by `statuses` using the effective status: a consent past `expiresAt` counts as `expired`, never as `active`.
     * - Returns each consent with its effective status, principal, client, store name and its constraints (see `getAgenticCommerceConsentConstraints()`), with value labels from the constraint plugins' field definitions.
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
     * - Returns the number of consents matching `AgenticCommerceConsentCriteriaTransfer.agenticCommerceConsentConditions`, without loading them.
     * - Applies the same conditions as `getAgenticCommerceConsentCollection()`, including the effective status: a consent past `expiresAt` counts as `expired`, never as `active`.
     * - Ignores `sortCollection` and `pagination`.
     *
     * @api
     */
    public function countAgenticCommerceConsents(AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer): int;

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
     * - Checks that the consent behind a delegated call may still be used; meant to run on every call.
     * - Returns `isValid=false` with one generic error, and revokes nothing, when the consent does not exist, is not effectively active (revoked or past `expiresAt`), or its client identifier, customer reference or store name differs from the request.
     * - Returns `isValid=false` with the same generic error, and revokes nothing, when no principal resolver is wired for the consent's principal type; the consent stays `active`.
     * - Otherwise asks the principal resolver of the consent's principal type whether the principal is still active.
     * - When the principal is inactive, revokes the consent with `revokedBy` `system_principal_inactive`, as `revokeAgenticCommerceConsentsBySystem()` does, and returns `isValid=false` with the same generic error.
     * - Otherwise writes `lastUsedAt`, but only when it is empty or older than `AgenticCommerceConfig::getLastUsedAtWriteIntervalInSeconds()`.
     * - Returns `isValid=true` with the consent; its `lastUsedAt` is the value read before this call's write.
     *
     * @api
     */
    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer;

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
     * - Returns the stored constraints of the consent, oldest first, each with its `type` and `value`.
     * - Skips a stored constraint whose type no `AgenticCommerceConsentConstraintPluginInterface` declares; the row stays in the database untouched.
     * - Returns an empty list for an unknown consent.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getAgenticCommerceConsentConstraints(string $consentUuid): array;

    /**
     * Specification:
     * - Requires `AgenticCommerceConstraintFieldRequestTransfer.principal`.
     * - Returns one field per `AgenticCommerceConsentConstraintPluginInterface` applicable for the principal and `scopes`, in plugin order.
     * - Each field is the plugin's field definition with `type` set to the plugin type and `isRequired` to the plugin's requirement for the principal and scopes.
     *
     * @api
     */
    public function getAgenticCommerceConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer;

    /**
     * Specification:
     * - Returns the newest terms event of the consent: `granted`, `re_consented` or `scopes_narrowed`; of two events written in the same second the later written one.
     * - The event carries `idAgenticCommerceConsentEvent`, its scopes and the constraints in force after it.
     * - Returns `null` when the consent is unknown or has no terms event.
     *
     * @api
     */
    public function findLatestAgenticCommerceConsentTermsEvent(string $consentUuid): ?AgenticCommerceConsentEventTransfer;

    /**
     * Specification:
     * - Returns the consent event with its scopes and constraints, expanded with `consentUuid`, the consent's current effective `consentStatus`, `consentRevokedAt`,
     *   `clientIdentifier` (the OAuth client identifier), `clientUri` and `idCustomer`.
     * - When the event stores no client name (a `scopes_narrowed` event), sets `clientName` from the latest `granted` or `re_consented` event of the same consent.
     * - Returns `null` for an unknown event.
     *
     * @api
     */
    public function findAgenticCommerceConsentEventById(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer;

    /**
     * Specification:
     * - Returns the consent uuid of the delegated Glue request the current Zed request is made for.
     * - Returns `null` outside a backend gateway request and for every Zed request that is not made for a delegated request.
     *
     * @api
     */
    public function findAgenticCommerceRequestConsentUuid(): ?string;

    /**
     * Specification:
     * - Tombstones every consent cache entry of the customer, in every store of the region, through the feature Client.
     * - For consents removed without a revocation, such as by the database cascade of a customer hard delete.
     * - Logs a failure at error level and never throws; an entry left behind expires with the cache TTL.
     *
     * @api
     */
    public function invalidateAgenticCommerceConsentCacheByCustomerReference(string $customerReference): void;

    /**
     * Specification:
     * - Returns those of the consent events that an order attribution references.
     * - For an event without a client name, such as `scopes_narrowed`, that an order attribution references, also returns the latest `granted` or `re_consented` event of its consent,
     *   ordered by `created_at` then id, both descending, when it is among the given ids: the Back Office block of the order shows the client name from it.
     *
     * @api
     *
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function filterAgenticCommerceOrderReferencedConsentEventIds(array $consentEventIds): array;
}
