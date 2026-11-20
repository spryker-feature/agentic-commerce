<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class ClientMetadataDocumentRefusalChecker implements ClientMetadataDocumentRefusalCheckerInterface
{
    protected const string HOST_LIST_ALLOWLIST = 'client metadata document host allowlist';

    protected const string HOST_LIST_DENYLIST = 'client host denylist';

    protected const string SCHEME_HTTPS = 'https';

    protected const int OAUTH_CLIENT_IDENTIFIER_COLUMN_MAX_LENGTH = 1024;

    protected const string PATH_ROOT = '/';

    protected const string PATTERN_DOT_SEGMENT = '#/\.{1,2}(?:/|$)#';

    public function __construct(
        protected BlockedClientMatcherInterface $blockedClientMatcher,
        protected ClientMetadataDocumentFailureCacheInterface $clientMetadataDocumentFailureCache,
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function findConfigurationRefusalReason(string $clientIdentifier, ?AgenticCommerceClientTransfer $storedClientTransfer): ?string
    {
        if (!$this->agenticCommerceConfig->isClientIdMetadataDocumentEnabled()) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_CLIENT_ID_METADATA_DOCUMENT_DISABLED;
        }

        if (!$this->isValidMetadataDocumentUrl($clientIdentifier)) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_URL;
        }

        return $this->findConfiguredListRefusalReason($clientIdentifier, $storedClientTransfer);
    }

    public function findFetchRefusalReason(string $clientIdentifier): ?string
    {
        $host = $this->findNormalizedClientIdentifierHost($clientIdentifier);

        if ($this->clientMetadataDocumentFailureCache->isFailureCached($clientIdentifier)) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_RECENTLY_FAILED;
        }

        if ($this->agenticCommerceClient->checkAgenticCommerceRateLimit(SharedAgenticCommerceConfig::SECURITY_BLOCKER_TYPE_METADATA_FETCH, '', $host)->getIsBlocked()) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_RATE_LIMITED;
        }

        return null;
    }

    protected function findConfiguredListRefusalReason(string $clientIdentifier, ?AgenticCommerceClientTransfer $storedClientTransfer): ?string
    {
        $host = $this->findNormalizedClientIdentifierHost($clientIdentifier);
        $configuredHostAllowlist = $this->agenticCommerceConfig->getClientIdMetadataDocumentHostAllowlist();

        if ($configuredHostAllowlist !== [] && !in_array($host, $this->normalizeConfiguredHosts($configuredHostAllowlist, static::HOST_LIST_ALLOWLIST), true)) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_NOT_ALLOWED;
        }

        if (in_array($host, $this->normalizeConfiguredHosts($this->agenticCommerceConfig->getClientDenylistHosts(), static::HOST_LIST_DENYLIST), true)) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED;
        }

        if ($storedClientTransfer === null) {
            return null;
        }

        if ($this->blockedClientMatcher->isDenylistedSoftwareId($storedClientTransfer->getSoftwareId())) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_SOFTWARE_ID_BLOCKED;
        }

        if ($this->blockedClientMatcher->hasDenylistedRedirectHost(array_values($storedClientTransfer->getRedirectUris()))) {
            return SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_REDIRECT_HOST_BLOCKED;
        }

        return null;
    }

    protected function findNormalizedClientIdentifierHost(string $clientIdentifier): string
    {
        return (string)$this->agenticCommerceService->findNormalizedHost((string)parse_url($clientIdentifier, PHP_URL_HOST));
    }

    /**
     * @param array<string> $configuredHosts
     *
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     *
     * @return list<string>
     */
    protected function normalizeConfiguredHosts(array $configuredHosts, string $hostListName): array
    {
        $invalidHosts = array_filter(
            $configuredHosts,
            fn (string $host): bool => $this->agenticCommerceService->findNormalizedHost($host) === null,
        );

        if ($invalidHosts !== []) {
            throw new AgenticCommerceConfigurationException(sprintf('The delegated access %s holds entries that are not valid hosts: %s.', $hostListName, implode(', ', $invalidHosts)));
        }

        return $this->agenticCommerceService->normalizeHosts($configuredHosts);
    }

    protected function isValidMetadataDocumentUrl(string $clientIdentifier): bool
    {
        $urlParts = parse_url($clientIdentifier);

        return $urlParts !== false
            && strlen($clientIdentifier) <= static::OAUTH_CLIENT_IDENTIFIER_COLUMN_MAX_LENGTH
            && ($urlParts['scheme'] ?? null) === static::SCHEME_HTTPS
            && isset($urlParts['host'])
            && $this->agenticCommerceService->findNormalizedHost($urlParts['host']) !== null
            && !isset($urlParts['user'])
            && !isset($urlParts['fragment'])
            && ($urlParts['path'] ?? static::PATH_ROOT) !== static::PATH_ROOT
            && preg_match(static::PATTERN_DOT_SEGMENT, $urlParts['path'] ?? static::PATH_ROOT) !== 1;
    }
}
