<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationServerMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;

interface AgenticCommerceServiceInterface
{
    /**
     * Specification:
     * - Builds the RFC 9728 (OAuth 2.0 Protected Resource Metadata) document of the delegated access resource.
     * - Sets `resource` to the configured resource URI, `authorization_servers` to the configured issuer and `bearer_methods_supported` to `header`.
     * - Sets `scopes_supported` to the scopes of the declared protected resource whose resource URI is the configured one, then `AgenticCommerceConstants::SUPPORTED_SCOPES`, in order and without duplicates.
     *
     * @api
     */
    public function buildProtectedResourceMetadata(): AgenticCommerceProtectedResourceMetadataTransfer;

    /**
     * Specification:
     * - Builds the RFC 8414 (OAuth 2.0 Authorization Server Metadata) document of the delegated access authorization server.
     * - Advertises the configured issuer and the configured authorization, token and RFC 7009 (OAuth 2.0 Token Revocation) revocation endpoints.
     * - Advertises response type `code`, grant types `authorization_code` and `refresh_token`, and `S256` as the only Proof Key for Code Exchange (PKCE, RFC 7636) code challenge method.
     * - Advertises `none` as the only client authentication method of the token and the revocation endpoint: clients are public clients.
     * - Advertises the scopes of every declared protected resource followed by the configured scopes, without duplicates, and the RFC 9207 (OAuth 2.0 Authorization Server Issuer Identification) `iss` authorization response parameter.
     *
     * @api
     */
    public function buildAuthorizationServerMetadata(): AgenticCommerceAuthorizationServerMetadataTransfer;

    /**
     * Specification:
     * - Returns the role a delegated access user holds for a scope: `ROLE_SCOPE_` followed by the scope in upper case, with `:` replaced by `_` and `-` replaced by `__`, so two scopes never share a role.
     * - Does not validate the scope, see `isDelegatedAccessScope()`.
     *
     * @api
     */
    public function mapScopeToRole(string $scope): string;

    /**
     * Specification:
     * - Returns whether the scope has the shape `<resource>:<action>`, each part lower-case words joined by hyphens.
     * - Returns `false` for the storefront user types `customer` and `company_user`.
     *
     * @api
     */
    public function isDelegatedAccessScope(string $scope): bool;

    /**
     * Specification:
     * - Returns the protected resources declared by the `AgenticCommerceProtectedResourceProviderPluginInterface` plugins.
     * - Validates the declarations on the first read and keeps the result for the lifetime of the instance.
     * - Throws `AgenticCommerceConfigurationException` for a duplicate name, resource URI or path prefix, a name outside `[a-z0-9-]`, a path prefix without leading `/` or with a trailing `/`, a resource URI that is not absolute, has a query or fragment, has user information, has a host not in the form `findNormalizedHost()` returns, or whose path differs from the path prefix, plain `http` for a host other than `localhost`, `127.0.0.1`, `[::1]` or `*.local`, a path prefix that is a public protocol endpoint (`/oauth/token`, `/oauth/revoke`, `/oauth/register`), lies above one or starts with `/.well-known/oauth-`, and a scope that does not have the shape `<resource>:<action>`.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResources(): array;

    /**
     * Specification:
     * - Returns the protected resources as `getProtectedResources()` does.
     * - Fails closed for an invalid declaration: returns an empty list instead of throwing, and logs the `AgenticCommerceConfigurationException` message at error level once for the lifetime of the instance.
     * - For the Glue storefront request path, where one misdeclared resource must not break routes no resource declares.
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function getProtectedResourcesFailClosed(): array;

    /**
     * Specification:
     * - Returns the declared protected resource whose resource URI equals the given string exactly.
     * - Returns `null` when no declared resource has this URI.
     * - Throws `AgenticCommerceConfigurationException` for an invalid declaration, as `getProtectedResources()` does.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    public function findProtectedResourceByResourceUri(string $resourceUri): ?AgenticCommerceProtectedResourceTransfer;

    /**
     * Specification:
     * - Returns the declared protected resource with the longest path prefix that matches the request path on whole segments: `/mcp` matches `/mcp` and `/mcp/x`, not `/mcpx`.
     * - Returns `null` when no path prefix matches.
     * - Reads the resources as `getProtectedResourcesFailClosed()` does: returns `null` for an invalid declaration.
     *
     * @api
     */
    public function findProtectedResourceByPath(string $path): ?AgenticCommerceProtectedResourceTransfer;

    /**
     * Specification:
     * - Returns one path pattern, without delimiters, for every path the delegated access firewall takes by its path: the public protocol endpoints (`AgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN`) and each declared protected resource's path prefix on whole segments (`/mcp` matches `/mcp` and `/mcp/x`, not `/mcpx`).
     * - Is matched the way Symfony's `PathRequestMatcher`, nelmio CORS paths and `OauthApiConfig::getAccessTokenValidationExcludedPathPatterns()` match: `{<pattern>}` against the request path.
     * - Does not cover operations marked `agenticCommerce: true` outside every declared prefix; they are known only after routing, and the firewall fails them closed.
     * - Reads the resources as `getProtectedResourcesFailClosed()` does: covers only the public protocol endpoints for an invalid declaration.
     *
     * @api
     */
    public function getDelegatedAccessPathPattern(): string;

    /**
     * Specification:
     * - Returns the declared protected resource whose resource URI path equals `/` followed by the given path, which is the part after `/.well-known/oauth-protected-resource/` without leading `/`.
     * - Returns `null` when no declared resource has this path.
     * - Reads the resources as `getProtectedResourcesFailClosed()` does: returns `null` for an invalid declaration.
     *
     * @api
     */
    public function findProtectedResourceByMetadataPath(string $resourcePath): ?AgenticCommerceProtectedResourceTransfer;

    /**
     * Specification:
     * - Returns the RFC 9728 (OAuth 2.0 Protected Resource Metadata) section 3.1 metadata URL of the resource: scheme and authority of its resource URI, `/.well-known/oauth-protected-resource` and the path of its resource URI.
     *
     * @api
     */
    public function getProtectedResourceMetadataUrl(AgenticCommerceProtectedResourceTransfer $agenticCommerceProtectedResourceTransfer): string;

    /**
     * Specification:
     * - Brings a host into the one form every host comparison of delegated access uses, so that a browser and the comparison see the same host.
     * - Percent-decodes the host, lower-cases it, converts an internationalized host to its ASCII (punycode) form and then removes one trailing dot, also one that was written as a Unicode full stop.
     * - Writes a bracketed IPv6 address in its compressed form, so `[0:0:0:0:0:0:0:1]` becomes `[::1]`.
     * - Returns `null` when the result is empty, still contains a percent sign or a trailing dot, is an IPv4 address in another spelling than dotted decimal (`127.1`, `2130706433`, `0x7f.0.0.1`, `127.0.0.01`) or as an IPv4-mapped or IPv4-compatible IPv6 address (`[::ffff:127.0.0.1]`, `[::127.0.0.1]`), or is neither a valid host name, an IPv4 address nor a bracketed IPv6 address. Host names with an underscore are not valid.
     *
     * @api
     */
    public function findNormalizedHost(string $host): ?string;

    /**
     * Specification:
     * - Normalizes every host as `findNormalizedHost()` does and leaves out the ones that are not valid.
     *
     * @api
     *
     * @param array<string> $hosts
     *
     * @return list<string>
     */
    public function normalizeHosts(array $hosts): array;

    /**
     * Specification:
     * - Returns the scope of every MCP tool, merged from the `AgenticCommerceMcpToolScopeProviderPluginInterface` plugins, by tool name; an empty scope means the tool needs none.
     * - Throws `AgenticCommerceConfigurationException` when two plugins declare the same tool with different scopes.
     *
     * @api
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return array<string, string>
     */
    public function getMcpToolScopes(): array;
}
