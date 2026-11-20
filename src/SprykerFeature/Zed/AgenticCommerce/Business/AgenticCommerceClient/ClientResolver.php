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
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ClientResolver implements ClientResolverInterface
{
    use LoggerTrait;

    protected const string URL_SCHEME_SEPARATOR = '://';

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected ClientMetadataDocumentFetcherInterface $clientMetadataDocumentFetcher,
        protected ClientMetadataMapperInterface $clientMetadataMapper,
        protected ClientMetadataValidatorInterface $clientMetadataValidator,
        protected ClientMetadataDocumentClientWriterInterface $clientMetadataDocumentClientWriter,
        protected BlockedClientMatcherInterface $blockedClientMatcher,
        protected ClientMetadataDocumentFailureCacheInterface $clientMetadataDocumentFailureCache,
        protected ClientMetadataDocumentRefusalCheckerInterface $clientMetadataDocumentRefusalChecker
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

        $configurationRefusalReason = $this->clientMetadataDocumentRefusalChecker->findConfigurationRefusalReason($clientIdentifier, $storedClientTransfer);

        if ($configurationRefusalReason !== null) {
            return $this->fail($resolveResponseTransfer, $configurationRefusalReason, $clientIdentifier);
        }

        if ($storedClientTransfer !== null && $this->isFresh($storedClientTransfer)) {
            return $resolveResponseTransfer->setAgenticCommerceClient($storedClientTransfer);
        }

        return $this->resolveClientFromMetadataDocument($resolveResponseTransfer, $clientIdentifier, $storedClientTransfer);
    }

    protected function resolveClientFromMetadataDocument(
        AgenticCommerceClientResolveResponseTransfer $resolveResponseTransfer,
        string $clientIdentifier,
        ?AgenticCommerceClientTransfer $storedClientTransfer
    ): AgenticCommerceClientResolveResponseTransfer {
        $fetchRefusalReason = $this->clientMetadataDocumentRefusalChecker->findFetchRefusalReason($clientIdentifier);

        if ($fetchRefusalReason !== null) {
            return $this->fail($resolveResponseTransfer, $fetchRefusalReason, $clientIdentifier);
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
