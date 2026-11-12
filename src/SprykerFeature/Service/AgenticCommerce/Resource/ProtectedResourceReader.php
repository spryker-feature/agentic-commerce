<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Psr\Log\LoggerInterface;
use SprykerFeature\Service\AgenticCommerce\Host\HostNormalizerInterface;
use SprykerFeature\Service\AgenticCommerce\Scope\ScopeRoleMapperInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;

class ProtectedResourceReader implements ProtectedResourceReaderInterface
{
    protected const string NAME_PATTERN = '/^[a-z0-9-]+$/D';

    protected const string PATH_SEPARATOR = '/';

    protected const string SCHEME_HTTP = 'http';

    protected const string SCHEME_HTTPS = 'https';

    protected const string PLAIN_HTTP_HOST_SUFFIX = '.local';

    /**
     * RFC 9728 (OAuth 2.0 Protected Resource Metadata), section 3.1: the well-known path segment sits between the authority and the resource path.
     */
    protected const string METADATA_PATH_SEGMENT = '/.well-known/oauth-protected-resource';

    /**
     * A path prefix matches itself and every path below it, never a path that only starts with the same characters.
     */
    protected const string PATH_PREFIX_PATTERN_FORMAT = '^%s(?:/|$)';

    protected const string ALTERNATIVE_PATTERN_FORMAT = '(?:%s)';

    protected const string ALTERNATIVE_SEPARATOR = '|';

    /**
     * The access token validation of OauthApi uses only patterns anchored at the start of the path.
     */
    protected const string ANCHORED_PATTERN_FORMAT = '^(?:%s)';

    /**
     * The paths `AgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN` matches; a path prefix above one of them would take it over.
     *
     * @var list<string>
     */
    protected const array PUBLIC_PROTOCOL_PATHS = [
        SharedAgenticCommerceConfig::TOKEN_ENDPOINT_PATH,
        SharedAgenticCommerceConfig::REVOCATION_ENDPOINT_PATH,
        SharedAgenticCommerceConfig::REGISTRATION_ENDPOINT_PATH,
        '/.well-known/oauth-authorization-server',
        '/.well-known/oauth-protected-resource',
    ];

    protected const string PATTERN_DELIMITER_FORMAT = '{%s}';

    protected const string LOG_MESSAGE_INVALID_DECLARATION = 'The declared protected resources are invalid, so the storefront treats them as not declared: %s';

    /**
     * @var list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>|null
     */
    protected ?array $protectedResourceTransfers = null;

    protected bool $isDeclarationInvalid = false;

    /**
     * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface> $protectedResourceProviderPlugins
     */
    public function __construct(
        protected array $protectedResourceProviderPlugins,
        protected ScopeRoleMapperInterface $scopeRoleMapper,
        protected HostNormalizerInterface $hostNormalizer,
        protected LoggerInterface $logger
    ) {
    }

    public function getProtectedResources(): array
    {
        if ($this->protectedResourceTransfers !== null) {
            return $this->protectedResourceTransfers;
        }

        $protectedResourceTransfers = [];

        foreach ($this->protectedResourceProviderPlugins as $protectedResourceProviderPlugin) {
            foreach ($protectedResourceProviderPlugin->getProtectedResources() as $protectedResourceTransfer) {
                $this->validateProtectedResource($protectedResourceTransfer, $protectedResourceTransfers);
                $protectedResourceTransfers[] = $protectedResourceTransfer;
            }
        }

        $this->protectedResourceTransfers = $protectedResourceTransfers;

        return $this->protectedResourceTransfers;
    }

    public function getProtectedResourcesFailClosed(): array
    {
        if ($this->isDeclarationInvalid) {
            return [];
        }

        try {
            return $this->getProtectedResources();
        } catch (AgenticCommerceConfigurationException $agenticCommerceConfigurationException) {
            $this->isDeclarationInvalid = true;
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_INVALID_DECLARATION, $agenticCommerceConfigurationException->getMessage()),
                ['exception' => $agenticCommerceConfigurationException],
            );

            return [];
        }
    }

    public function findProtectedResourceByResourceUri(string $resourceUri): ?AgenticCommerceProtectedResourceTransfer
    {
        foreach ($this->getProtectedResources() as $protectedResourceTransfer) {
            if ($protectedResourceTransfer->getResourceUri() === $resourceUri) {
                return $protectedResourceTransfer;
            }
        }

        return null;
    }

    public function findProtectedResourceByPath(string $path): ?AgenticCommerceProtectedResourceTransfer
    {
        $matchedResourceTransfer = null;

        foreach ($this->getProtectedResourcesFailClosed() as $protectedResourceTransfer) {
            $pathPrefix = (string)$protectedResourceTransfer->getPathPrefix();

            if ($path !== $pathPrefix && !str_starts_with($path, $pathPrefix . static::PATH_SEPARATOR)) {
                continue;
            }

            if ($matchedResourceTransfer === null || strlen($pathPrefix) > strlen((string)$matchedResourceTransfer->getPathPrefix())) {
                $matchedResourceTransfer = $protectedResourceTransfer;
            }
        }

        return $matchedResourceTransfer;
    }

    public function findProtectedResourceByMetadataPath(string $resourcePath): ?AgenticCommerceProtectedResourceTransfer
    {
        $path = static::PATH_SEPARATOR . $resourcePath;

        foreach ($this->getProtectedResourcesFailClosed() as $protectedResourceTransfer) {
            if (parse_url((string)$protectedResourceTransfer->getResourceUri(), PHP_URL_PATH) === $path) {
                return $protectedResourceTransfer;
            }
        }

        return null;
    }

    public function getDelegatedAccessPathPattern(): string
    {
        $alternativePatterns = [sprintf(static::ALTERNATIVE_PATTERN_FORMAT, SharedAgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN)];

        foreach ($this->getProtectedResourcesFailClosed() as $protectedResourceTransfer) {
            $alternativePatterns[] = sprintf(
                static::ALTERNATIVE_PATTERN_FORMAT,
                sprintf(static::PATH_PREFIX_PATTERN_FORMAT, preg_quote($protectedResourceTransfer->getPathPrefixOrFail())),
            );
        }

        return sprintf(static::ANCHORED_PATTERN_FORMAT, implode(static::ALTERNATIVE_SEPARATOR, $alternativePatterns));
    }

    public function getProtectedResourceMetadataUrl(AgenticCommerceProtectedResourceTransfer $agenticCommerceProtectedResourceTransfer): string
    {
        $resourceUriParts = parse_url($agenticCommerceProtectedResourceTransfer->getResourceUriOrFail());
        $port = isset($resourceUriParts['port']) ? ':' . $resourceUriParts['port'] : '';

        return sprintf(
            '%s://%s%s%s%s',
            $resourceUriParts['scheme'] ?? '',
            $resourceUriParts['host'] ?? '',
            $port,
            static::METADATA_PATH_SEGMENT,
            $resourceUriParts['path'] ?? '',
        );
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer> $declaredResourceTransfers
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validateProtectedResource(
        AgenticCommerceProtectedResourceTransfer $protectedResourceTransfer,
        array $declaredResourceTransfers
    ): void {
        $name = (string)$protectedResourceTransfer->getName();
        $pathPrefix = (string)$protectedResourceTransfer->getPathPrefix();
        $resourceUri = (string)$protectedResourceTransfer->getResourceUri();

        if (preg_match(static::NAME_PATTERN, $name) !== 1) {
            throw new AgenticCommerceConfigurationException(sprintf('The protected resource name "%s" must consist of lower-case letters, digits and hyphens.', $name));
        }

        foreach ($declaredResourceTransfers as $declaredResourceTransfer) {
            if ($declaredResourceTransfer->getName() === $name) {
                throw new AgenticCommerceConfigurationException(sprintf('The protected resource name "%s" is declared twice.', $name));
            }

            if ($declaredResourceTransfer->getResourceUri() === $resourceUri) {
                throw new AgenticCommerceConfigurationException(sprintf('The protected resource URI "%s" is declared twice.', $resourceUri));
            }

            if ($declaredResourceTransfer->getPathPrefix() === $pathPrefix) {
                throw new AgenticCommerceConfigurationException(sprintf('The path prefix "%s" of the protected resource "%s" is declared twice.', $pathPrefix, $name));
            }
        }

        if (!str_starts_with($pathPrefix, static::PATH_SEPARATOR) || str_ends_with($pathPrefix, static::PATH_SEPARATOR)) {
            throw new AgenticCommerceConfigurationException(sprintf('The path prefix "%s" of the protected resource "%s" must start with "/" and must not end with "/".', $pathPrefix, $name));
        }

        if ($this->isOverlappingPublicProtocolPath($pathPrefix)) {
            throw new AgenticCommerceConfigurationException(sprintf('The path prefix "%s" of the protected resource "%s" overlaps the public protocol endpoints.', $pathPrefix, $name));
        }

        $this->validateResourceUri($name, $pathPrefix, $resourceUri);

        foreach ($protectedResourceTransfer->getScopes() as $scope) {
            if (!$this->scopeRoleMapper->isDelegatedAccessScope($scope)) {
                throw new AgenticCommerceConfigurationException(sprintf('The scope "%s" of the protected resource "%s" must have the shape "<resource>:<action>".', $scope, $name));
            }
        }
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validateResourceUri(string $name, string $pathPrefix, string $resourceUri): void
    {
        $resourceUriParts = parse_url($resourceUri);

        if (
            $resourceUriParts === false
            || !isset($resourceUriParts['scheme'], $resourceUriParts['host'])
            || isset($resourceUriParts['query'])
            || isset($resourceUriParts['fragment'])
            || isset($resourceUriParts['user'])
            || isset($resourceUriParts['pass'])
            || ($resourceUriParts['path'] ?? '') !== $pathPrefix
        ) {
            throw new AgenticCommerceConfigurationException(sprintf('The resource URI "%s" of the protected resource "%s" must be absolute, without user information, query or fragment, and its path must equal the path prefix "%s".', $resourceUri, $name, $pathPrefix));
        }

        $normalizedHost = $this->hostNormalizer->findNormalizedHost($resourceUriParts['host']);

        if ($normalizedHost === null) {
            throw new AgenticCommerceConfigurationException(sprintf('The host "%s" of the resource URI "%s" of the protected resource "%s" is not a valid host.', $resourceUriParts['host'], $resourceUri, $name));
        }

        if ($normalizedHost !== $resourceUriParts['host']) {
            throw new AgenticCommerceConfigurationException(sprintf('The host "%s" of the resource URI "%s" of the protected resource "%s" must be written in its normalized form "%s".', $resourceUriParts['host'], $resourceUri, $name, $normalizedHost));
        }

        $scheme = strtolower($resourceUriParts['scheme']);

        if ($scheme === static::SCHEME_HTTPS) {
            return;
        }

        if ($scheme !== static::SCHEME_HTTP || !$this->isPlainHttpHost(strtolower($resourceUriParts['host']))) {
            throw new AgenticCommerceConfigurationException(sprintf('The resource URI "%s" of the protected resource "%s" must use https; http is accepted for local hosts only.', $resourceUri, $name));
        }
    }

    protected function isOverlappingPublicProtocolPath(string $pathPrefix): bool
    {
        if (preg_match(sprintf(static::PATTERN_DELIMITER_FORMAT, SharedAgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN), $pathPrefix) === 1) {
            return true;
        }

        foreach (static::PUBLIC_PROTOCOL_PATHS as $publicProtocolPath) {
            if (str_starts_with($publicProtocolPath, $pathPrefix . static::PATH_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    protected function isPlainHttpHost(string $host): bool
    {
        return in_array($host, SharedAgenticCommerceConfig::LOOPBACK_REDIRECT_HOSTS, true) || str_ends_with($host, static::PLAIN_HTTP_HOST_SUFFIX);
    }
}
