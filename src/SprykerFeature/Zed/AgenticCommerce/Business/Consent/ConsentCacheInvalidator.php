<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\CustomerTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Zed\Store\Business\StoreFacadeInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use Throwable;

/**
 * The change is committed before this runs, so a failure must not undo or hide it: it is logged, and a cache entry that
 * stays behind expires with the cache TTL. Every call resets the failure latches first: a Zed process, such as a
 * housekeeping run, can outlive a Redis outage, and a latch left from an earlier call would skip every later tombstone.
 */
class ConsentCacheInvalidator implements ConsentCacheInvalidatorInterface
{
    protected const string LOG_MESSAGE_INVALIDATION_FAILED = 'agentic_commerce consent cache invalidation failed; the entries expire with the cache TTL.';

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected StoreFacadeInterface $storeFacade,
        protected LoggerInterface $logger
    ) {
    }

    public function invalidateConsents(array $agenticCommerceConsentTransfers): void
    {
        try {
            $this->agenticCommerceClient->resetAgenticCommerceFailureLatches();
            $consentUuidsByStoreName = $this->groupConsentUuidsByStoreName($agenticCommerceConsentTransfers);
        } catch (Throwable $throwable) {
            $this->logger->error(static::LOG_MESSAGE_INVALIDATION_FAILED, ['exception' => $throwable]);

            return;
        }

        foreach ($consentUuidsByStoreName as $storeName => $consentUuids) {
            try {
                $this->agenticCommerceClient->invalidateAgenticCommerceConsentUsageCache((string)$storeName, $consentUuids);
            } catch (Throwable $throwable) {
                $this->logger->error(static::LOG_MESSAGE_INVALIDATION_FAILED, ['exception' => $throwable, 'storeName' => $storeName]);
            }
        }
    }

    public function invalidateByCustomerReference(string $customerReference): void
    {
        try {
            $this->agenticCommerceClient->resetAgenticCommerceFailureLatches();
            $storeTransfers = $this->storeFacade->getAllStores();
        } catch (Throwable $throwable) {
            $this->logger->error(static::LOG_MESSAGE_INVALIDATION_FAILED, ['exception' => $throwable]);

            return;
        }

        foreach ($storeTransfers as $storeTransfer) {
            try {
                $this->agenticCommerceClient->invalidateAgenticCommerceConsentUsageCacheByCustomerReference($storeTransfer->getNameOrFail(), $customerReference);
            } catch (Throwable $throwable) {
                $this->logger->error(static::LOG_MESSAGE_INVALIDATION_FAILED, ['exception' => $throwable, 'storeName' => $storeTransfer->getName()]);
            }
        }
    }

    public function invalidateByCustomer(CustomerTransfer $customerTransfer): void
    {
        $customerReference = $customerTransfer->getCustomerReference();

        if ($customerReference === null || $customerReference === '') {
            return;
        }

        $this->invalidateByCustomerReference($customerReference);
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return array<string, list<string>>
     */
    protected function groupConsentUuidsByStoreName(array $agenticCommerceConsentTransfers): array
    {
        $consentUuidsByStoreName = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $consentUuidsByStoreName[$agenticCommerceConsentTransfer->getStoreNameOrFail()][] = $agenticCommerceConsentTransfer->getUuidOrFail();
        }

        return $consentUuidsByStoreName;
    }
}
