<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group ConsentUsageCacheTest
 * Add your own group annotations below this line
 */
class ConsentUsageCacheTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    protected const string CONSENT_UUID = 'consent-1';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const int TTL_SECONDS = 60;

    protected const int LAST_USED_AT_WRITE_INTERVAL_IN_SECONDS = 3600;

    /**
     * Seconds from the first save of A to the save of B, from there to the refill of A, and from there to past the expiry of the index B wrote.
     */
    protected const int SECONDS_UNTIL_SECOND_CONSENT_IS_SAVED = 50;

    protected const int SECONDS_UNTIL_FIRST_CONSENT_IS_REFILLED = 20;

    protected const int SECONDS_UNTIL_AFTER_THE_SECOND_SAVES_INDEX_EXPIRY = 45;

    protected const string LOG_MESSAGE_INVALIDATION_FAILED = 'agentic_commerce consent cache invalidation failed';

    protected const string LOG_MESSAGE_FILL_FAILED = 'agentic_commerce consent cache could not be filled';

    protected const string LOG_MESSAGE_READ_FAILED = 'agentic_commerce consent cache could not be read';

    protected const string ENTRY_KEY = 'agentic_commerce:consent:DE:consent-1';

    protected const string CORRUPT_ENTRY_VALUE = '{not json';

    protected AgenticCommerceClientTester $tester;

    public function testGivenACachedValidUsageWhenFindingThenItIsReturnedWithoutZed(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis();
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Act
        $usageResponseTransfer = $consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertTrue($usageResponseTransfer->getIsValid());
        $this->assertSame(static::CONSENT_UUID, $usageResponseTransfer->getAgenticCommerceConsentOrFail()->getUuid());
    }

    public function testGivenAnEntryForAnotherStoreWhenFindingThenItIsAMiss(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis();
        $consentUsageCache->save('AT', static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Act
        $usageResponseTransfer = $consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertNull($usageResponseTransfer);
    }

    public function testGivenARevocationTombstoneWhenASlowRequestSavesAValidUsageThenTheTombstoneStaysAndFindIsAMiss(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis();
        $consentUsageCache->invalidate(static::STORE_NAME, [static::CONSENT_UUID]);

        // Act
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Assert
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID));
    }

    public function testGivenACachedValidUsageWhenItIsInvalidatedThenFindIsAMiss(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis();
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Act
        $consentUsageCache->invalidate(static::STORE_NAME, [static::CONSENT_UUID]);

        // Assert
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID));
    }

    public function testGivenATombstoneOlderThanTheCacheTtlWhenASlowRequestSavesAValidUsageThenTheTombstoneStillBlocksTheFill(): void
    {
        // Arrange
        $clock = $this->tester->createFakeClock();
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis(clock: $clock);
        $consentUsageCache->invalidate(static::STORE_NAME, [static::CONSENT_UUID]);
        $clock->advanceSeconds(static::TTL_SECONDS + 1);
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Act
        $usageResponseTransfer = $consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertNull($usageResponseTransfer, 'The tombstone must outlive the cache TTL.');
    }

    public function testGivenAnEntryWhoseTtlHasPassedWhenFindingThenItIsAMiss(): void
    {
        // Arrange
        $clock = $this->tester->createFakeClock();
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis(clock: $clock);
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());
        $clock->advanceSeconds(static::TTL_SECONDS + 1);

        // Act
        $usageResponseTransfer = $consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertNull($usageResponseTransfer);
    }

    public function testGivenAnInvalidUsageWhenSavingThenNothingIsCached(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis();

        // Act
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, (new AgenticCommerceConsentUsageResponseTransfer())->setIsValid(false));

        // Assert
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID));
    }

    public function testGivenRedisIsUnavailableWhenFindingThenNullIsReturnedSoZedDecides(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithFailingRedis();

        // Act
        $usageResponseTransfer = $consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertNull($usageResponseTransfer);
    }

    public function testGivenRedisIsUnavailableWhenInvalidatingThenNothingIsThrownAndAnErrorIsLogged(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithFailingRedis();

        // Act
        $consentUsageCache->invalidate(static::STORE_NAME, [static::CONSENT_UUID]);

        // Assert
        $this->assertCount(1, $this->tester->grabLoggedConsentCacheErrors());
        $this->assertStringContainsString(static::LOG_MESSAGE_INVALIDATION_FAILED, $this->tester->grabLoggedConsentCacheErrors()[0]);
    }

    public function testGivenRedisIsUnavailableWhenSavingThenNothingIsThrownAndAnErrorIsLogged(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithFailingRedis();

        // Act
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Assert
        $this->assertCount(1, $this->tester->grabLoggedConsentCacheErrors());
        $this->assertStringContainsString(static::LOG_MESSAGE_FILL_FAILED, $this->tester->grabLoggedConsentCacheErrors()[0]);
    }

    public function testGivenACorruptEntryWhenFindingThenNullIsReturnedAndAnErrorIsLogged(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithStoredEntry(static::ENTRY_KEY, static::CORRUPT_ENTRY_VALUE);

        // Act
        $usageResponseTransfer = $consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertNull($usageResponseTransfer);
        $this->assertCount(1, $this->tester->grabLoggedConsentCacheErrors());
        $this->assertStringContainsString(static::LOG_MESSAGE_READ_FAILED, $this->tester->grabLoggedConsentCacheErrors()[0]);
    }

    public function testGivenATtlOfZeroWhenSavingThenNothingIsCached(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis(ttlSeconds: 0);

        // Act
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Assert
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, static::CONSENT_UUID));
    }

    public function testGivenTwoCachedConsentsOfACustomerWhenInvalidatingByCustomerThenBothAreMisses(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis();
        $consentUsageCache->save(static::STORE_NAME, 'consent-1', static::CUSTOMER_REFERENCE, $this->createValidUsageResponse('consent-1'));
        $consentUsageCache->save(static::STORE_NAME, 'consent-2', static::CUSTOMER_REFERENCE, $this->createValidUsageResponse('consent-2'));

        // Act
        $consentUsageCache->invalidateByCustomerReference(static::STORE_NAME, static::CUSTOMER_REFERENCE);

        // Assert
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, 'consent-1'));
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, 'consent-2'));
    }

    public function testGivenAConsentRefilledAfterItsEntryExpiredWhenTheIndexWouldHaveExpiredBeforeItThenInvalidatingByCustomerStillTombstonesIt(): void
    {
        // Arrange
        $clock = $this->tester->createFakeClock();
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis(clock: $clock);
        $consentUsageCache->save(static::STORE_NAME, 'consent-a', static::CUSTOMER_REFERENCE, $this->createValidUsageResponse('consent-a'));
        $clock->advanceSeconds(static::SECONDS_UNTIL_SECOND_CONSENT_IS_SAVED);
        $consentUsageCache->save(static::STORE_NAME, 'consent-b', static::CUSTOMER_REFERENCE, $this->createValidUsageResponse('consent-b'));
        $clock->advanceSeconds(static::SECONDS_UNTIL_FIRST_CONSENT_IS_REFILLED);
        $consentUsageCache->save(static::STORE_NAME, 'consent-a', static::CUSTOMER_REFERENCE, $this->createValidUsageResponse('consent-a'));
        $clock->advanceSeconds(static::SECONDS_UNTIL_AFTER_THE_SECOND_SAVES_INDEX_EXPIRY);

        // Act
        $consentUsageCache->invalidateByCustomerReference(static::STORE_NAME, static::CUSTOMER_REFERENCE);

        // Assert
        $this->assertNull($consentUsageCache->find(static::STORE_NAME, 'consent-a'));
    }

    public function testGivenAConsentJustCachedWhenAskingWhetherToTouchLastUsedAtThenItIsNotDueUntilTheIntervalHasPassed(): void
    {
        // Arrange
        $clock = $this->tester->createFakeClock();
        $consentUsageCache = $this->tester->createConsentUsageCacheWithInMemoryRedis(clock: $clock);
        $consentUsageCache->save(static::STORE_NAME, static::CONSENT_UUID, static::CUSTOMER_REFERENCE, $this->createValidUsageResponse());

        // Act
        $isDueWithinTheInterval = $consentUsageCache->shouldTouchLastUsedAt(static::STORE_NAME, static::CONSENT_UUID);
        $clock->advanceSeconds(static::LAST_USED_AT_WRITE_INTERVAL_IN_SECONDS);
        $isDueAfterTheInterval = $consentUsageCache->shouldTouchLastUsedAt(static::STORE_NAME, static::CONSENT_UUID);
        $isDueRightAfterThat = $consentUsageCache->shouldTouchLastUsedAt(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertFalse($isDueWithinTheInterval);
        $this->assertTrue($isDueAfterTheInterval);
        $this->assertFalse($isDueRightAfterThat);
    }

    public function testGivenRedisIsUnavailableWhenAskingWhetherToTouchLastUsedAtThenItIsDueSoZedIsAsked(): void
    {
        // Arrange
        $consentUsageCache = $this->tester->createConsentUsageCacheWithFailingRedis();

        // Act
        $isDue = $consentUsageCache->shouldTouchLastUsedAt(static::STORE_NAME, static::CONSENT_UUID);

        // Assert
        $this->assertTrue($isDue);
    }

    protected function createValidUsageResponse(string $consentUuid = self::CONSENT_UUID): AgenticCommerceConsentUsageResponseTransfer
    {
        return (new AgenticCommerceConsentUsageResponseTransfer())
            ->setIsValid(true)
            ->setAgenticCommerceConsent(
                (new AgenticCommerceConsentTransfer())
                    ->setUuid($consentUuid)
                    ->setCustomerReference(static::CUSTOMER_REFERENCE)
                    ->setStoreName(static::STORE_NAME)
                    ->setGrantedScopes(['catalog:read']),
            );
    }
}
