<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use SprykerFeature\Service\AgenticCommerce\Host\HostNormalizerInterface;
use SprykerFeature\Service\AgenticCommerce\Scope\ScopeRoleMapperInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;

class ProtectedResourceValidator implements ProtectedResourceValidatorInterface
{
    protected const string NAME_PATTERN = '/^[a-z0-9-]+$/D';

    protected const string SCHEME_HTTP = 'http';

    protected const string SCHEME_HTTPS = 'https';

    protected const string PLAIN_HTTP_HOST_SUFFIX = '.local';

    protected const string PATTERN_DELIMITER_FORMAT = '{%s}';

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

    public function __construct(
        protected ScopeRoleMapperInterface $scopeRoleMapper,
        protected HostNormalizerInterface $hostNormalizer
    ) {
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer> $declaredResourceTransfers
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    public function validate(
        AgenticCommerceProtectedResourceTransfer $protectedResourceTransfer,
        array $declaredResourceTransfers
    ): void {
        $name = (string)$protectedResourceTransfer->getName();
        $pathPrefix = (string)$protectedResourceTransfer->getPathPrefix();
        $resourceUri = (string)$protectedResourceTransfer->getResourceUri();

        if (preg_match(static::NAME_PATTERN, $name) !== 1) {
            throw new AgenticCommerceConfigurationException(sprintf('The protected resource name "%s" must consist of lower-case letters, digits and hyphens.', $name));
        }

        $this->validateUniqueness($declaredResourceTransfers, $name, $pathPrefix, $resourceUri);
        $this->validatePathPrefix($name, $pathPrefix);
        $this->validateResourceUri($name, $pathPrefix, $resourceUri);
        $this->validateScopes($protectedResourceTransfer, $name);
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer> $declaredResourceTransfers
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validateUniqueness(array $declaredResourceTransfers, string $name, string $pathPrefix, string $resourceUri): void
    {
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
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validatePathPrefix(string $name, string $pathPrefix): void
    {
        if (!str_starts_with($pathPrefix, ProtectedResourceReader::PATH_SEPARATOR) || str_ends_with($pathPrefix, ProtectedResourceReader::PATH_SEPARATOR)) {
            throw new AgenticCommerceConfigurationException(sprintf('The path prefix "%s" of the protected resource "%s" must start with "/" and must not end with "/".', $pathPrefix, $name));
        }

        if ($this->isOverlappingPublicProtocolPath($pathPrefix)) {
            throw new AgenticCommerceConfigurationException(sprintf('The path prefix "%s" of the protected resource "%s" overlaps the public protocol endpoints.', $pathPrefix, $name));
        }
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validateScopes(AgenticCommerceProtectedResourceTransfer $protectedResourceTransfer, string $name): void
    {
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

        if (!$this->isValidResourceUriShape($resourceUriParts, $pathPrefix)) {
            throw new AgenticCommerceConfigurationException(sprintf('The resource URI "%s" of the protected resource "%s" must be absolute, without user information, query or fragment, and its path must equal the path prefix "%s".', $resourceUri, $name, $pathPrefix));
        }

        $this->validateResourceUriHost($resourceUriParts, $name, $resourceUri);
        $this->validateResourceUriScheme($resourceUriParts, $name, $resourceUri);
    }

    /**
     * @phpstan-assert-if-true array<string, int|string> $resourceUriParts
     *
     * @param array<string, int|string>|false $resourceUriParts
     */
    protected function isValidResourceUriShape(array|false $resourceUriParts, string $pathPrefix): bool
    {
        if ($resourceUriParts === false || !isset($resourceUriParts['scheme'], $resourceUriParts['host'])) {
            return false;
        }

        if (isset($resourceUriParts['query']) || isset($resourceUriParts['fragment'])) {
            return false;
        }

        if (isset($resourceUriParts['user']) || isset($resourceUriParts['pass'])) {
            return false;
        }

        return ($resourceUriParts['path'] ?? '') === $pathPrefix;
    }

    /**
     * @param array<string, int|string> $resourceUriParts
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validateResourceUriHost(array $resourceUriParts, string $name, string $resourceUri): void
    {
        $host = (string)$resourceUriParts['host'];
        $normalizedHost = $this->hostNormalizer->findNormalizedHost($host);

        if ($normalizedHost === null) {
            throw new AgenticCommerceConfigurationException(sprintf('The host "%s" of the resource URI "%s" of the protected resource "%s" is not a valid host.', $host, $resourceUri, $name));
        }

        if ($normalizedHost !== $host) {
            throw new AgenticCommerceConfigurationException(sprintf('The host "%s" of the resource URI "%s" of the protected resource "%s" must be written in its normalized form "%s".', $host, $resourceUri, $name, $normalizedHost));
        }
    }

    /**
     * @param array<string, int|string> $resourceUriParts
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function validateResourceUriScheme(array $resourceUriParts, string $name, string $resourceUri): void
    {
        $scheme = strtolower((string)$resourceUriParts['scheme']);

        if ($scheme === static::SCHEME_HTTPS) {
            return;
        }

        if ($scheme !== static::SCHEME_HTTP || !$this->isPlainHttpHost(strtolower((string)$resourceUriParts['host']))) {
            throw new AgenticCommerceConfigurationException(sprintf('The resource URI "%s" of the protected resource "%s" must use https; http is accepted for local hosts only.', $resourceUri, $name));
        }
    }

    protected function isOverlappingPublicProtocolPath(string $pathPrefix): bool
    {
        if (preg_match(sprintf(static::PATTERN_DELIMITER_FORMAT, SharedAgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN), $pathPrefix) === 1) {
            return true;
        }

        foreach (static::PUBLIC_PROTOCOL_PATHS as $publicProtocolPath) {
            if (str_starts_with($publicProtocolPath, $pathPrefix . ProtectedResourceReader::PATH_SEPARATOR)) {
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
