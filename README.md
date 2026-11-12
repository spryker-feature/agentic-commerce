# AgenticCommerce Module
[![Latest Stable Version](https://poser.pugx.org/spryker-feature/agentic-commerce/v/stable.svg)](https://packagist.org/packages/spryker-feature/agentic-commerce)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

Lets a storefront customer give a registered third-party client delegated access to the shop. The customer approves the client on a consent page, and the client then receives short-lived access tokens that only work on the delegated access resources and only carry the scopes the customer allowed.

## Installation

```
composer require spryker-feature/agentic-commerce
```

## Terms

- OAuth (Open Authorization) 2.0 is the protocol the client uses to get a token. The authorization code flow is described in RFC 6749.
- PKCE (Proof Key for Code Exchange, RFC 7636) binds an authorization code to the client that asked for it. Only the `S256` method is accepted.
- A client is a third-party application registered with the shop. A consent is the customer's approval of one client for a set of scopes, one principal and one store.
- A scope is one permission a client may ask for, written as `area:action`, for example `agentic-commerce:principal-read`. Every client has a scope ceiling, the scopes it may ever ask for. The customer can untick scopes on the consent page.
- A principal is the account the client acts for: the customer, or one of the customer's company users. The client never gets more rights than the principal has.
- The issuer is the base URL of the Glue storefront. Authorization server metadata (RFC 8414) and protected resource metadata (RFC 9728) are published under it. Resource indicators (RFC 8707) tie a token to the delegated access resource, and token revocation follows RFC 7009.
- Glue is the Spryker REST API layer. Yves is the storefront. Zed is the back end.

## What the feature provides

- Zed client status: `AgenticCommerceFacade::updateAgenticCommerceClientCollection()` blocks and unblocks registered clients. Blocking a client revokes its active consents.
- Zed consents: `AgenticCommerceFacade::getAgenticCommerceConsentCollection()` reads consents with their effective status, principal, client and store. `updateAgenticCommerceConsentCollection()` revokes consents on behalf of the customer who gave them, and `revokeAgenticCommerceConsentsBySystem()` revokes them on behalf of the system. Every revocation writes a consent event (`revoked`, `reuse_detected` or `code_replayed`), so the history of a consent is kept.
- Zed plugin interface in `Dependency/Plugin`: `AgenticCommerceConsentPostRevokePluginInterface`, executed once with all consents a revocation revoked.
- Service: `AgenticCommerceService` builds the authorization server metadata (RFC 8414) and the protected resource metadata (RFC 9728) documents.

## Quick start

### 1. Configure

Set these `AgenticCommerceConstants` in `config/Shared/config_default.php`:

| Constant | Meaning |
|---|---|
| `ISSUER` | Base URL of the Glue storefront. The token endpoint `<issuer>/oauth/token`, the revocation endpoint `<issuer>/oauth/revoke` and the resource URI `<issuer>/agentic-commerce` in the metadata follow from it. |
| `AUTHORIZATION_ENDPOINT` | Absolute URL of the authorization endpoint published in the authorization server metadata. |
| `SUPPORTED_SCOPES` | Scopes a client can be registered for, published in the metadata. |

Delegated access is switched on once `ISSUER`, `AUTHORIZATION_ENDPOINT` and `PUBLIC_KEY` are all set (`AgenticCommerceConfig::isConfigured()`). Until then the Yves pages and the Glue protocol endpoints answer `404`, so `ISSUER` and `PUBLIC_KEY` are the two switches to watch: one without the other leaves the feature off.

The Glue storefront host (`SPRYKER_GLUE_STOREFRONT_HOST`) must be available to every application, so the issuer is the same everywhere. The issuer must be written identically in the metadata, the `iss` response parameter and the token `iss` claim.

### 3. Wire the project

- `composer require nelmio/cors-bundle`: the feature does not require it, but the CORS (cross-origin resource sharing) rule below needs it.
- `config/GlueStorefront/bundles.php`: add `SprykerFeature\Glue\AgenticCommerce\AgenticCommerceBundle::class => ['all' => true]` and `Nelmio\CorsBundle\NelmioCorsBundle::class => ['all' => true]`.
- `config/GlueStorefront/packages/security.php`: add the `agentic_commerce` firewall before the `main` firewall, with `AgenticCommerceAuthenticator`, `AgenticCommerceAuthenticationEntryPoint` and `AgenticCommerceAccessDeniedHandler`. Give it the pattern `AgenticCommerceServiceInterface::getDelegatedAccessPathPattern()`. Add the access control rules `AgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN` (public) and, after it, `AgenticCommerceServiceInterface::getDelegatedAccessPathPattern()` (authenticated).
- `config/GlueStorefront/packages/nelmio_cors.php`: add one path rule, `AgenticCommerceServiceInterface::getDelegatedAccessPathPattern()`, which covers `/oauth/token`, `/oauth/revoke`, `/oauth/register`, `/.well-known/oauth-*` and the path prefix of every declared protected resource. It allows the `Authorization` and `Content-Type` headers and exposes `WWW-Authenticate`. Other paths keep their current CORS behaviour.
- `Pyz\Glue\OauthApi\OauthApiConfig::getAccessTokenValidationExcludedPathPatterns()`: return `AgenticCommerceServiceInterface::getDelegatedAccessPathPattern()`. This is required. Without it the storefront token check runs on delegated access calls and every one of them answers `401` with error code `001`.
- `Pyz\Glue\GlueStorefrontApiApplication\GlueStorefrontApiApplicationDependencyProvider`: add `AgenticCommerceStoreApplicationPlugin`, which resolves the store of a call from the token.
- `Pyz\Client\Oauth\OauthDependencyProvider::getAccessTokenValidatorPlugins()`: add `AgenticCommerceTokenRejectingAccessTokenValidatorPlugin`.
- `Pyz\Zed\Oauth\OauthDependencyProvider`: add `AgenticCommerceAuthorizationCodeOauthRequestGrantTypeConfigurationProviderPlugin` and `AgenticCommerceRefreshTokenOauthRequestGrantTypeConfigurationProviderPlugin`.
- `Pyz\Zed\Console\ConsoleDependencyProvider`: add the three client console commands.
- `Pyz\Zed\AgenticCommerce\AgenticCommerceDependencyProvider`: return the principal resolver plugins (`AgenticCommerceCustomerPrincipalResolverPlugin`, and for B2B `AgenticCommerceCompanyUserPrincipalResolverPlugin`) and the scope filter plugins (for B2B `AgenticCommerceCompanyRolePermissionScopeFilterPlugin`).
- `Pyz\Zed\Customer\CustomerDependencyProvider`: add `AgenticCommerceCustomerAnonymizerPlugin` to the customer anonymizer plugins.
- `Pyz\Zed\CompanyUser\CompanyUserDependencyProvider` (B2B): add `AgenticCommerceCompanyUserPreDeletePlugin` and `AgenticCommerceCompanyUserPostUpdatePlugin`.
- `Pyz\Yves\Router\RouterDependencyProvider`: add `AgenticCommerceRouteProviderPlugin`.
- `Pyz\Yves\CustomerPage\CustomerPageDependencyProvider`: add `AgenticCommerceAuthorizationCustomerRedirectStrategyPlugin`, which sends the customer back to the authorization request after login, and `AgenticCommerceAuthenticationTimeAfterCustomerAuthenticationSuccessPlugin`, which records the login time.
- `Pyz\Yves\EventDispatcher\EventDispatcherDependencyProvider`: add `AgenticCommerceFramingProtectionEventDispatcherPlugin`.
- `Pyz\Yves\ShopApplication\ShopApplicationDependencyProvider`: add `AgenticCommerceMenuItemWidget`. The "Connected apps" entry in the customer menu needs a `spryker-shop/customer-page` release whose `customer-navigation.twig` renders this widget; with an older release the widget is registered but nothing shows it.
- Import the glossary keys `agentic_commerce.*`.

The feature's `GlueRestPluginStackSubscriber` opt-out for GlueApplication is automatic: the feature's own subscriber keeps the Glue REST plugin stacks away from delegated access paths, so nothing needs to be wired for it.

### 4. Resources are found on install

The Glue storefront resources (`oauth-token`, `oauth-revoke`, the two metadata documents and `agentic-commerce-principals`) are in `resources/api/storefront/` and API Platform finds them as soon as the feature is installed. The Glue storefront container builds without the wiring of step 3, and other storefront endpoints keep working. The protocol endpoints answer `404` until delegated access is configured (see step 2). The principal resource answers `401` or `403` instead, because only a delegated access token that passed the `agentic_commerce` firewall can reach it. To drop them, add the feature's resource directory to `excludedPathFragments` in `config/GlueStorefront/packages/spryker_api_platform.php`:

- in a project: `vendor/spryker-feature/agentic-commerce/resources/api/`;
- in the suite: `src/SprykerFeature/AgenticCommerce/resources/api/`.

The fragment is matched as a substring of the resource file path, so it must include the vendor or source directory. A project adds its own delegated resources under `/agentic-commerce/` and guards them with `ROLE_SCOPE_<SCOPE>` roles, for example `ROLE_SCOPE_AGENTIC__COMMERCE_PRINCIPAL__READ` for the scope `agentic-commerce:principal-read` (`:` becomes `_`, `-` becomes `__`).

### 5. Deploy

```
vendor/bin/console transfer:generate
vendor/bin/console propel:install
```

## Documentation

[Spryker Documentation](https://docs.spryker.com)
