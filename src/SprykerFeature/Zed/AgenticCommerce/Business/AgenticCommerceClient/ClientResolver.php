<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use DateTimeImmutable;
use Generated\Shared\Transfer\AgenticCommerceClientResolveResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ClientResolver implements ClientResolverInterface
{
    use LoggerTrait;

    protected const string HOST_LIST_ALLOWLIST = 'client metadata document host allowlist';

    protected const string HOST_LIST_DENYLIST = 'client host denylist';

    protected const string URL_SCHEME_SEPARATOR = '://';

    protected const string SCHEME_HTTPS = 'https';

    /**
     * The size of `spy_oauth_client.identifier`.
     */
    protected const int CLIENT_IDENTIFIER_MAX_LENGTH = 1024;

    protected const string PATH_ROOT = '/';

    protected const string PATTERN_DOT_SEGMENT = '#/\.{1,2}(?:/|$)#';

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected ClientMetadataDocumentFetcherInterface $clientMetadataDocumentFetcher,
        protected ClientMetadataMapperInterface $clientMetadataMapper,
        protected ClientMetadataValidatorInterface $clientMetadataValidator,
        protected ClientMetadataDocumentClientWriterInterface $clientMetadataDocumentClientWriter,
        protected BlockedClientMatcherInterface $blockedClientMatcher,
        protected ClientMetadataDocumentFailureCacheInterface $clientMetadataDocumentFailureCache,
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function resolveClient(string $clientIdentifier): AgenticCommerceClientResolveResponseTransfer
    {
        $resolveResponseTransfer = new AgenticCommerceClientResolveResponseTransfer();
        $storedClientTransfer = $this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientIdentifier);

        if ($storedClientTransfer !== null && $storedClientTransfer->getClientIdentifier() !== $clientIdentifier) {
            return $this->refuseCaseVariant($resolveResponseTransfer, $clientIdentifier);
        }

        if (!$this->looksLikeUrl($clientIdentifier) || ($storedClientTransfer !== null && !$this->isMetadataDocumentClient($storedClientTransfer))) {
            return $resolveResponseTransfer->setAgenticCommerceClient($storedClientTransfer);
        }

        if (!$this->agenticCommerceConfig->isClientIdMetadataDocumentEnabled()) {
            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_CLIENT_ID_METADATA_DOCUMENT_DISABLED, $clientIdentifier);
        }

        if (!$this->isValidMetadataDocumentUrl($clientIdentifier)) {
            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_URL, $clientIdentifier);
        }

        $configuredListRefusalReason = $this->findConfiguredListRefusalReason($clientIdentifier, $storedClientTransfer);

        if ($configuredListRefusalReason !== null) {
            return $this->fail($resolveResponseTransfer, $configuredListRefusalReason, $clientIdentifier);
        }

        if ($storedClientTransfer !== null && $this->isFresh($storedClientTransfer)) {
            return $resolveResponseTransfer->setAgenticCommerceClient($storedClientTransfer);
        }

        $refusalReason = $this->findFetchRefusalReason($clientIdentifier);

        if ($refusalReason !== null) {
            return $this->fail($resolveResponseTransfer, $refusalReason, $clientIdentifier);
        }

        $documentTransfer = $this->clientMetadataDocumentFetcher->fetch($clientIdentifier);

        if (!$documentTransfer->getIsFetched()) {
            $this->clientMetadataDocumentFailureCache->cacheFailure($clientIdentifier);

            return $this->fail($resolveResponseTransfer, $documentTransfer->getFailureReasonOrFail(), $clientIdentifier);
        }

        $clientMetadataTransfer = $this->clientMetadataMapper->mapRegistrationFields($documentTransfer->getContent());

        if ($clientMetadataTransfer->getClientId() !== $clientIdentifier) {
            $this->clientMetadataDocumentFailureCache->cacheFailure($clientIdentifier);

            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_CLIENT_ID_MISMATCH, $clientIdentifier);
        }

        $validationResponseTransfer = $this->clientMetadataValidator->validate($clientMetadataTransfer, null, SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT);

        if (!$validationResponseTransfer->getIsValid()) {
            $this->clientMetadataDocumentFailureCache->cacheFailure($clientIdentifier);

            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_DOCUMENT, $clientIdentifier);
        }

        if ($this->blockedClientMatcher->hasBlockedClientWithSoftwareId($clientMetadataTransfer->getSoftwareId())) {
            $this->clientMetadataDocumentFailureCache->cacheFailure($clientIdentifier);

            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_SOFTWARE_ID_BLOCKED, $clientIdentifier);
        }

        // The client itself is left out: a blocked client keeps receiving its document updates and stays blocked.
        if ($this->blockedClientMatcher->hasBlockedClientWithRedirectHost(array_values($clientMetadataTransfer->getRedirectUris()), $clientIdentifier)) {
            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_REDIRECT_HOST_BLOCKED, $clientIdentifier);
        }

        $clientTransfer = $this->clientMetadataDocumentClientWriter->writeClient(
            $clientMetadataTransfer->setClientName($validationResponseTransfer->getClientNameOrFail()),
            $storedClientTransfer,
            $documentTransfer->getCacheLifetimeSecondsOrFail(),
        );

        if ($clientTransfer === null) {
            return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_CLIENT_ID_TAKEN, $clientIdentifier);
        }

        return $resolveResponseTransfer->setAgenticCommerceClient($clientTransfer);
    }

    /**
     * Checks the configured lists, which cost no request, before the cache, so a list change stops a stored client at once:
     * host allowlist, host denylist and, for a stored client, its software ID and redirect hosts.
     * Whether an allowlist applies is decided on the configured list, so a list of only invalid entries allows no host.
     */
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

    /**
     * Checks in order: negative cache, fetch bucket of the host.
     */
    protected function findFetchRefusalReason(string $clientIdentifier): ?string
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

    protected function findNormalizedClientIdentifierHost(string $clientIdentifier): string
    {
        return (string)$this->agenticCommerceService->findNormalizedHost((string)parse_url($clientIdentifier, PHP_URL_HOST));
    }

    /**
     * An entry that is not a valid host is left out with a warning: in the allowlist it allows no host, in the denylist it blocks none.
     *
     * @param array<string> $configuredHosts
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
            $this->getLogger()->warning(sprintf('The delegated access %s holds hosts that are not valid; they are ignored.', $hostListName), ['invalidHosts' => array_values($invalidHosts)]);
        }

        return $this->agenticCommerceService->normalizeHosts($configuredHosts);
    }

    /**
     * The database compares client identifiers without letter case, so the stored client may hold another URL: it is never the client of this one.
     * A non-URL identifier is treated as unknown; a URL identifier is refused as a client ID mismatch.
     */
    protected function refuseCaseVariant(
        AgenticCommerceClientResolveResponseTransfer $resolveResponseTransfer,
        string $clientIdentifier
    ): AgenticCommerceClientResolveResponseTransfer {
        if (!$this->looksLikeUrl($clientIdentifier)) {
            return $resolveResponseTransfer;
        }

        return $this->fail($resolveResponseTransfer, SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_CLIENT_ID_MISMATCH, $clientIdentifier);
    }

    protected function looksLikeUrl(string $clientIdentifier): bool
    {
        return str_contains($clientIdentifier, static::URL_SCHEME_SEPARATOR);
    }

    /**
     * An HTTPS URL with a valid host once percent-decoded and a path, without user information, fragment or dot segments, that fits the identifier column.
     */
    protected function isValidMetadataDocumentUrl(string $clientIdentifier): bool
    {
        $urlParts = parse_url($clientIdentifier);

        return $urlParts !== false
            && strlen($clientIdentifier) <= static::CLIENT_IDENTIFIER_MAX_LENGTH
            && ($urlParts['scheme'] ?? null) === static::SCHEME_HTTPS
            && isset($urlParts['host'])
            && $this->agenticCommerceService->findNormalizedHost($urlParts['host']) !== null
            && !isset($urlParts['user'])
            && !isset($urlParts['fragment'])
            && ($urlParts['path'] ?? static::PATH_ROOT) !== static::PATH_ROOT
            && preg_match(static::PATTERN_DOT_SEGMENT, $urlParts['path'] ?? static::PATH_ROOT) !== 1;
    }

    protected function isMetadataDocumentClient(AgenticCommerceClientTransfer $clientTransfer): bool
    {
        return $clientTransfer->getRegistrationType() === SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT;
    }

    protected function isFresh(AgenticCommerceClientTransfer $clientTransfer): bool
    {
        return $clientTransfer->getMetadataExpiresAt() !== null
            && new DateTimeImmutable($clientTransfer->getMetadataExpiresAt()) > new DateTimeImmutable();
    }

    protected function fail(
        AgenticCommerceClientResolveResponseTransfer $resolveResponseTransfer,
        string $failureReason,
        string $clientIdentifier
    ): AgenticCommerceClientResolveResponseTransfer {
        $this->getLogger()->warning('Client could not be resolved from its metadata document.', ['reason' => $failureReason, 'host' => parse_url($clientIdentifier, PHP_URL_HOST)]);

        return $resolveResponseTransfer->setFailureReason($failureReason);
    }
}
