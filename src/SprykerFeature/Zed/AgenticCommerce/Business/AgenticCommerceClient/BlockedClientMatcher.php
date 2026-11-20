<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\RedirectUriMatcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class BlockedClientMatcher implements BlockedClientMatcherInterface
{
    protected const int FAMILY_BLOCK_LOOKUP_LIMIT = 2;

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected RedirectUriValidatorInterface $redirectUriValidator,
        protected RedirectUriMatcherInterface $redirectUriMatcher,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function hasBlockedClientWithSoftwareId(?string $softwareId): bool
    {
        if ($softwareId === null || $softwareId === '') {
            return false;
        }

        return $this->isDenylistedSoftwareId($softwareId)
            || $this->hasFamilyBlock([SharedAgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId]);
    }

    public function isDenylistedSoftwareId(?string $softwareId): bool
    {
        return $softwareId !== null && $softwareId !== '' && in_array($softwareId, $this->agenticCommerceConfig->getClientDenylistSoftwareIds(), true);
    }

    /**
     * @param array<string> $redirectUris
     */
    public function hasDenylistedRedirectHost(array $redirectUris): bool
    {
        foreach ($redirectUris as $redirectUri) {
            $redirectHost = $this->findNonLoopbackRedirectHost($redirectUri);

            if ($redirectHost !== null && $this->isDenylistedHost($redirectHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $redirectUris
     */
    public function hasBlockedClientWithRedirectHost(array $redirectUris, ?string $ignoredClientIdentifier = null): bool
    {
        $blockedByValues = [];

        foreach ($redirectUris as $redirectUri) {
            $redirectHost = $this->findNonLoopbackRedirectHost($redirectUri);

            if ($redirectHost !== null && $this->isDenylistedHost($redirectHost)) {
                return true;
            }

            if ($redirectHost !== null) {
                $blockedByValues[] = SharedAgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . $redirectHost;
            }
        }

        if ($blockedByValues === []) {
            return false;
        }

        return $this->hasFamilyBlock(array_values(array_unique($blockedByValues)), $ignoredClientIdentifier);
    }

    protected function findNonLoopbackRedirectHost(string $redirectUri): ?string
    {
        return $this->redirectUriMatcher->isLoopbackRedirectUri($redirectUri) ? null : $this->redirectUriValidator->findHost($redirectUri);
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function isDenylistedHost(string $host): bool
    {
        $configuredHosts = $this->agenticCommerceConfig->getClientDenylistHosts();
        $invalidHosts = array_filter(
            $configuredHosts,
            fn (string $configuredHost): bool => $this->agenticCommerceService->findNormalizedHost($configuredHost) === null,
        );

        if ($invalidHosts !== []) {
            throw new AgenticCommerceConfigurationException(sprintf('The delegated access client host denylist holds entries that are not valid hosts: %s.', implode(', ', $invalidHosts)));
        }

        return in_array($host, $this->agenticCommerceService->normalizeHosts($configuredHosts), true);
    }

    /**
     * Reads at most one client besides the ignored one: any blocked client of the family is enough.
     *
     * @param list<string> $blockedByValues
     */
    protected function hasFamilyBlock(array $blockedByValues, ?string $ignoredClientIdentifier = null): bool
    {
        $agenticCommerceClientCollectionTransfer = $this->agenticCommerceRepository->getAgenticCommerceClientCollection(
            (new AgenticCommerceClientCriteriaTransfer())
                ->setAgenticCommerceClientConditions(
                    (new AgenticCommerceClientConditionsTransfer())
                        ->setBlockedByValues($blockedByValues)
                        ->addStatus(SharedAgenticCommerceConfig::CLIENT_STATUS_BLOCKED),
                )
                ->setPagination((new PaginationTransfer())->setOffset(0)->setLimit(static::FAMILY_BLOCK_LOOKUP_LIMIT)),
        );

        foreach ($agenticCommerceClientCollectionTransfer->getAgenticCommerceClients() as $agenticCommerceClientTransfer) {
            if ($agenticCommerceClientTransfer->getClientIdentifierOrFail() !== $ignoredClientIdentifier) {
                return true;
            }
        }

        return false;
    }
}
