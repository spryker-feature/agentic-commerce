<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Helper;

use ArrayObject;
use Closure;
use Codeception\Module;
use Codeception\Stub;
use Codeception\TestInterface;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClient;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig as ClientAgenticCommerceConfig;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Client\AgenticCommerce\Cache\ConsentUsageCache;
use SprykerFeature\Client\AgenticCommerce\Cache\ConsentUsageCacheInterface;
use SprykerFeature\Client\AgenticCommerce\Cache\MetadataDocumentFailureCache;
use SprykerFeature\Client\AgenticCommerce\Cache\MetadataDocumentFailureCacheInterface;
use SprykerFeature\Client\AgenticCommerce\KillSwitch\KillSwitch;
use SprykerFeature\Client\AgenticCommerce\KillSwitch\KillSwitchInterface;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Client\AgenticCommerce\SecurityBlocker\RateLimitChecker;
use SprykerFeature\Client\AgenticCommerce\SecurityBlocker\RateLimitCheckerInterface;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeatureTest\Client\AgenticCommerce\Helper\InMemoryAgenticCommerceRedisWrapper;
use SprykerFeatureTest\Client\AgenticCommerce\Helper\RecordingLogger;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class AgenticCommerceConsentCacheHelper extends Module
{
    use DependencyHelperTrait;
    use LocatorHelperTrait;

    public const string VALUE_TOMBSTONE = 'revoked';

    public const string CALL_CACHE_INVALIDATE = 'cache_invalidate';

    protected const string ENTRY_KEY_PATTERN = 'agentic_commerce:consent:%s:%s';

    protected InMemoryAgenticCommerceRedisWrapper $inMemoryRedisWrapper;

    protected RecordingLogger $logger;

    /**
     * @var list<string>
     */
    protected array $fullRateLimitBucketTypes = [];

    /**
     * @var \ArrayObject<int, string>|null
     */
    protected ?ArrayObject $callOrder = null;

    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        $this->inMemoryRedisWrapper = new InMemoryAgenticCommerceRedisWrapper();
        $this->logger = new RecordingLogger();
        $this->callOrder = null;
        $this->fullRateLimitBucketTypes = [];

        $this->getDependencyHelper()->setDependency(
            AgenticCommerceDependencyProvider::CLIENT_AGENTIC_COMMERCE,
            $this->createClient($this->createConsentUsageCache(), new MetadataDocumentFailureCache($this->inMemoryRedisWrapper, $this->logger), $this->createRateLimitChecker()),
        );
    }

    public function primeConsentCache(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): void
    {
        $this->createConsentUsageCache()->save(
            $agenticCommerceConsentTransfer->getStoreNameOrFail(),
            $agenticCommerceConsentTransfer->getUuidOrFail(),
            $agenticCommerceConsentTransfer->getCustomerReferenceOrFail(),
            (new AgenticCommerceConsentUsageResponseTransfer())->setIsValid(true)->setAgenticCommerceConsent($agenticCommerceConsentTransfer),
        );
    }

    public function findRawConsentCacheValue(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): ?string
    {
        return $this->inMemoryRedisWrapper->get(sprintf(
            static::ENTRY_KEY_PATTERN,
            $agenticCommerceConsentTransfer->getStoreNameOrFail(),
            $agenticCommerceConsentTransfer->getUuidOrFail(),
        ));
    }

    public function fillRateLimitBucket(string $type): void
    {
        $this->fullRateLimitBucketTypes[] = $type;
    }

    public function useFailingConsentCache(): void
    {
        $this->inMemoryRedisWrapper->failFromNowOn();
    }

    /**
     * @param \ArrayObject<int, string> $callOrder
     */
    public function setConsentCacheRecorder(ArrayObject $callOrder): void
    {
        $this->callOrder = $callOrder;
    }

    public function assertErrorLogged(string $message): void
    {
        foreach ($this->logger->getErrorMessages() as $errorMessage) {
            if (str_contains($errorMessage, $message)) {
                return;
            }
        }

        $this->fail(sprintf('No error containing "%s" was logged. Logged errors: %s', $message, implode(' | ', $this->logger->getErrorMessages())));
    }

    public function getConsentCacheLogger(): RecordingLogger
    {
        return $this->logger;
    }

    public function deleteCustomerByReference(string $customerReference): CustomerTransfer
    {
        $customerTransfer = $this->getLocator()->customer()->facade()->findByReference($customerReference)
            ?? $this->fail(sprintf('Customer "%s" does not exist.', $customerReference));

        $this->getLocator()->customer()->facade()->deleteCustomer($customerTransfer);

        return $customerTransfer;
    }

    protected function createConsentUsageCache(): ConsentUsageCacheInterface
    {
        return new ConsentUsageCache($this->inMemoryRedisWrapper, new ClientAgenticCommerceConfig(), $this->logger);
    }

    protected function createRateLimitChecker(): RateLimitCheckerInterface
    {
        $securityBlockerClient = Stub::makeEmpty(SecurityBlockerClientInterface::class, [
            'isAccountBlocked' => fn (SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): SecurityCheckAuthResponseTransfer => (new SecurityCheckAuthResponseTransfer())
                ->setIsBlocked(in_array($securityCheckAuthContextTransfer->getType(), $this->fullRateLimitBucketTypes, true)),
            'incrementLoginAttemptCount' => static fn (): SecurityCheckAuthResponseTransfer => new SecurityCheckAuthResponseTransfer(),
        ]);

        return new RateLimitChecker($securityBlockerClient, new ClientAgenticCommerceConfig(), $this->logger);
    }

    protected function createClient(
        ConsentUsageCacheInterface $consentUsageCache,
        MetadataDocumentFailureCacheInterface $metadataDocumentFailureCache,
        RateLimitCheckerInterface $rateLimitChecker
    ): AgenticCommerceClient {
        $agenticCommerceClient = new class ($consentUsageCache, $metadataDocumentFailureCache, $rateLimitChecker, fn () => $this->recordCall(static::CALL_CACHE_INVALIDATE)) extends AgenticCommerceClient {
            /**
             * @param \Closure(): void $onInvalidate
             */
            public function __construct(
                protected ConsentUsageCacheInterface $consentUsageCache,
                protected MetadataDocumentFailureCacheInterface $metadataDocumentFailureCache,
                protected RateLimitCheckerInterface $rateLimitChecker,
                protected Closure $onInvalidate
            ) {
            }

            public function hasAgenticCommerceMetadataDocumentFailure(string $clientIdentifier): bool
            {
                return $this->metadataDocumentFailureCache->has($clientIdentifier);
            }

            public function saveAgenticCommerceMetadataDocumentFailure(string $clientIdentifier, int $ttlSeconds): void
            {
                $this->metadataDocumentFailureCache->save($clientIdentifier, $ttlSeconds);
            }

            public function checkAgenticCommerceRateLimit(string $type, string $ip, ?string $account): SecurityCheckAuthResponseTransfer
            {
                return $this->rateLimitChecker->checkRateLimit($type, $ip, $account);
            }

            public function findAgenticCommerceConsentUsageInCache(string $storeName, string $consentUuid): ?AgenticCommerceConsentUsageResponseTransfer
            {
                return $this->consentUsageCache->find($storeName, $consentUuid);
            }

            public function saveAgenticCommerceConsentUsageToCache(
                string $storeName,
                string $consentUuid,
                string $customerReference,
                AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer
            ): void {
                $this->consentUsageCache->save($storeName, $consentUuid, $customerReference, $agenticCommerceConsentUsageResponseTransfer);
            }

            public function invalidateAgenticCommerceConsentUsageCache(string $storeName, array $consentUuids): void
            {
                ($this->onInvalidate)();
                $this->consentUsageCache->invalidate($storeName, $consentUuids);
            }

            public function invalidateAgenticCommerceConsentUsageCacheByCustomerReference(string $storeName, string $customerReference): void
            {
                ($this->onInvalidate)();
                $this->consentUsageCache->invalidateByCustomerReference($storeName, $customerReference);
            }

            public function shouldTouchAgenticCommerceConsentLastUsedAt(string $storeName, string $consentUuid): bool
            {
                return $this->consentUsageCache->shouldTouchLastUsedAt($storeName, $consentUuid);
            }
        };
        $agenticCommerceClient->setFactory($this->createFactory());

        return $agenticCommerceClient;
    }

    protected function createFactory(): AgenticCommerceFactory
    {
        $factory = new class ($this->inMemoryRedisWrapper) extends AgenticCommerceFactory {
            public function __construct(protected AgenticCommerceRedisWrapperInterface $inMemoryRedisWrapper)
            {
            }

            public function createKillSwitch(): KillSwitchInterface
            {
                return new KillSwitch($this->inMemoryRedisWrapper, $this->getLogger());
            }
        };

        return $factory;
    }

    protected function recordCall(string $call): void
    {
        if ($this->callOrder !== null) {
            $this->callOrder[] = $call;
        }
    }
}
