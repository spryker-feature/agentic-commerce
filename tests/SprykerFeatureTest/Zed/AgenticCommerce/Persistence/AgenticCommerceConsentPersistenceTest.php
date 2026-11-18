<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Persistence;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentLockQuery;
use Propel\Runtime\Propel;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig as ZedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManager;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepository;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommercePersistenceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Persistence
 * @group AgenticCommerceConsentPersistenceTest
 * Add your own group annotations below this line
 */
class AgenticCommerceConsentPersistenceTest extends Unit
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string FUTURE = '+7 days';

    protected const string PAST = '-1 day';

    protected const string REFRESH_TOKEN_IDENTIFIER = 'consent-uuid.refresh-token-identifier';

    protected const string ROTATED_REFRESH_TOKEN_IDENTIFIER = 'consent-uuid.rotated-refresh-token-identifier';

    protected const string STALE_REFRESH_TOKEN_IDENTIFIER = 'consent-uuid.stale-refresh-token-identifier';

    protected const string CONCURRENT_REFRESH_TOKEN_IDENTIFIER = 'consent-uuid.concurrent-refresh-token-identifier';

    protected const string PRINCIPAL_REFERENCE = 'company-user-listed';

    protected const int SECONDS_WITHIN_THE_WRITE_INTERVAL = 600;

    protected const int SECONDS_BEYOND_THE_WRITE_INTERVAL = 7200;

    protected AgenticCommercePersistenceTester $tester;

    public function testGivenAnActiveConsentWithGrantedScopesWhenCreatedThenItsIdAndUuidAreSetAndTheScopesAreStored(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => AgenticCommerceHelper::STORE_NAME]);

        // Act
        $consentTransfer = (new AgenticCommerceEntityManager())->createAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())
                ->setAgenticCommerceClient($clientTransfer)
                ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
                ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->setPrincipalType(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
                ->setPrincipalReference($customerTransfer->getCustomerReferenceOrFail())
                ->setIdStore($storeTransfer->getIdStoreOrFail())
                ->setStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
                ->setExpiresAt((new DateTime(static::FUTURE))->format(static::DATE_TIME_FORMAT))
                ->setGrantedScopes([AgenticCommerceHelper::SCOPE]),
        );

        // Assert
        $this->assertNotNull($consentTransfer->getIdAgenticCommerceConsent());
        $this->assertNotEmpty($consentTransfer->getUuid());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getGrantedScopes());
    }

    public function testGivenEventsWithAndWithoutAClientNameWhenStoredThenOnlyTheFirstCarriesIt(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $entityManager = new AgenticCommerceEntityManager();

        // Act
        $entityManager->createAgenticCommerceConsentEvent(
            (new AgenticCommerceConsentEventTransfer())
                ->setIdAgenticCommerceConsent($consentTransfer->getIdAgenticCommerceConsentOrFail())
                ->setEvent(AgenticCommerceConfig::CONSENT_EVENT_GRANTED)
                ->setActor(AgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER)
                ->setScopes([AgenticCommerceHelper::SCOPE])
                ->setClientName(AgenticCommerceHelper::CLIENT_NAME),
        );
        $entityManager->createAgenticCommerceConsentEvent(
            (new AgenticCommerceConsentEventTransfer())
                ->setIdAgenticCommerceConsent($consentTransfer->getIdAgenticCommerceConsentOrFail())
                ->setEvent(AgenticCommerceConfig::CONSENT_EVENT_REVOKED)
                ->setActor(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER)
                ->setScopes([AgenticCommerceHelper::SCOPE]),
        );

        // Assert
        $eventTransfers = $this->tester->getConsentEvents($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceHelper::CLIENT_NAME, $eventTransfers[0]->getClientName());
        $this->assertNull($eventTransfers[1]->getClientName());
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function provideInactiveConsentSeeds(): array
    {
        return [
            'revoked' => [[AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]],
            'past its expiry' => [[AgenticCommerceConsentTransfer::EXPIRES_AT => (new DateTime(static::PAST))->format(static::DATE_TIME_FORMAT)]],
        ];
    }

    public function testGivenActiveAndInactiveConsentsOfAClientPrincipalAndStoreWhenListingActiveUuidsThenOnlyTheActiveOnesOfThatClientPrincipalAndStoreAreReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $seed = [AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()];
        $firstConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed);
        $secondConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed + [AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed + [AgenticCommerceConsentTransfer::STORE_NAME => AgenticCommerceHelper::OTHER_STORE_NAME]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed + [
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE,
        ]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->haveAgenticCommerceConsentForCustomer($this->tester->haveAgenticCommerceCustomer(), $seed);

        // Act
        $consentUuids = (new AgenticCommerceRepository())->getActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
            $clientTransfer->getClientIdentifierOrFail(),
            AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER,
            $customerTransfer->getCustomerReferenceOrFail(),
            $firstConsentTransfer->getIdStoreOrFail(),
        );

        // Assert
        $this->assertEqualsCanonicalizing([$firstConsentTransfer->getUuid(), $secondConsentTransfer->getUuid()], $consentUuids);
    }

    public function testGivenACustomerWithoutAConsentLockWhenTheLockIsCreatedTwiceThenOneRowIsStored(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $agenticCommerceEntityManager = new AgenticCommerceEntityManager();

        // Act
        $agenticCommerceEntityManager->createAgenticCommerceConsentLock($customerTransfer->getIdCustomerOrFail());
        $agenticCommerceEntityManager->createAgenticCommerceConsentLock($customerTransfer->getIdCustomerOrFail());

        // Assert
        $this->assertSame(1, SpyAgenticCommerceConsentLockQuery::create()->filterByFkCustomer($customerTransfer->getIdCustomerOrFail())->count());
    }

    /**
     * A second connection would be needed to observe the lock blocking; the transactional test helper keeps one connection, so this proves the locking query runs.
     */
    public function testGivenACustomerWithAConsentLockWhenLockingItThenTrueIsReturnedAndAForUpdateQueryRuns(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        (new AgenticCommerceEntityManager())->createAgenticCommerceConsentLock($customerTransfer->getIdCustomerOrFail());
        /** @var \Propel\Runtime\Connection\ConnectionWrapper $connection */
        $connection = Propel::getConnection();
        $wasInDebugMode = $connection->isInDebugMode();
        $connection->useDebug(true);

        // Act
        try {
            $isLocked = (new AgenticCommerceRepository())->lockAgenticCommerceConsentLockByIdCustomer($customerTransfer->getIdCustomerOrFail());
            $lastExecutedQuery = $connection->getLastExecutedQuery();
        } finally {
            $connection->useDebug($wasInDebugMode);
        }

        // Assert
        $this->assertTrue($isLocked);
        $this->assertStringContainsString('spy_agentic_commerce_consent_lock', $lastExecutedQuery);
        $this->assertStringContainsString('FOR UPDATE', $lastExecutedQuery);
    }

    public function testGivenACustomerWithoutAConsentLockWhenLockingItThenFalseIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();

        // Act
        $isLocked = (new AgenticCommerceRepository())->lockAgenticCommerceConsentLockByIdCustomer($customerTransfer->getIdCustomerOrFail());

        // Assert
        $this->assertFalse($isLocked);
    }

    public function testGivenAnActiveConsentWithARefreshTokenWhenRevokedTwiceThenTheFirstRevokeClearsTheIdentifierAndTheSecondReturnsFalse(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);
        $entityManager = new AgenticCommerceEntityManager();

        // Act
        $isFirstRevoked = $entityManager->revokeAgenticCommerceConsent($consentTransfer->getIdAgenticCommerceConsentOrFail(), AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER);
        $isSecondRevoked = $entityManager->revokeAgenticCommerceConsent($consentTransfer->getIdAgenticCommerceConsentOrFail(), AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT);

        // Assert
        $this->assertTrue($isFirstRevoked);
        $this->assertFalse($isSecondRevoked);
        $this->assertNull($this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getRevokedBy());
    }

    public function testGivenTheStoredRefreshTokenIdentifierWhenSwappedThenTheNewIdentifierIsStored(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);

        // Act
        $isSwapped = (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentRefreshTokenIdentifier(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            static::REFRESH_TOKEN_IDENTIFIER,
            static::ROTATED_REFRESH_TOKEN_IDENTIFIER,
        );

        // Assert
        $this->assertTrue($isSwapped);
        $this->assertSame(static::ROTATED_REFRESH_TOKEN_IDENTIFIER, $this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAStaleRefreshTokenIdentifierWhenSwappedThenNothingChanges(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);

        // Act
        $isSwapped = (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentRefreshTokenIdentifier(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            static::STALE_REFRESH_TOKEN_IDENTIFIER,
            static::ROTATED_REFRESH_TOKEN_IDENTIFIER,
        );

        // Assert
        $this->assertFalse($isSwapped);
        $this->assertSame(static::REFRESH_TOKEN_IDENTIFIER, $this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAConsentWithoutARefreshTokenWhenSwappedFromNullThenTheNewIdentifierIsStored(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $isSwapped = (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentRefreshTokenIdentifier(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            null,
            static::ROTATED_REFRESH_TOKEN_IDENTIFIER,
        );

        // Assert
        $this->assertTrue($isSwapped);
        $this->assertSame(static::ROTATED_REFRESH_TOKEN_IDENTIFIER, $this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    /**
     * @dataProvider provideInactiveConsentSeeds
     *
     * @param array<string, mixed> $seed
     */
    public function testGivenAnInactiveConsentWhenItsRefreshTokenIdentifierIsSwappedThenNothingChanges(array $seed): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent($seed + [AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);

        // Act
        $isSwapped = (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentRefreshTokenIdentifier(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            static::REFRESH_TOKEN_IDENTIFIER,
            static::ROTATED_REFRESH_TOKEN_IDENTIFIER,
        );

        // Assert
        $this->assertFalse($isSwapped);
        $this->assertSame(static::REFRESH_TOKEN_IDENTIFIER, $this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    /**
     * Two refreshes that presented the same refresh token race on the conditional update; the transactional test helper keeps one connection, so the race is replayed in sequence.
     */
    public function testGivenTwoSwapsFromTheSameRefreshTokenIdentifierWhenBothRunThenOnlyTheFirstSucceeds(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);
        $entityManager = new AgenticCommerceEntityManager();

        // Act
        $isFirstSwapped = $entityManager->updateAgenticCommerceConsentRefreshTokenIdentifier(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            static::REFRESH_TOKEN_IDENTIFIER,
            static::ROTATED_REFRESH_TOKEN_IDENTIFIER,
        );
        $isSecondSwapped = $entityManager->updateAgenticCommerceConsentRefreshTokenIdentifier(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            static::REFRESH_TOKEN_IDENTIFIER,
            static::CONCURRENT_REFRESH_TOKEN_IDENTIFIER,
        );

        // Assert
        $this->assertTrue($isFirstSwapped);
        $this->assertFalse($isSecondSwapped);
        $this->assertSame(static::ROTATED_REFRESH_TOKEN_IDENTIFIER, $this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAConsentWhenItsGrantedScopesAreUpdatedThenTheNewListIsStored(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([
            AgenticCommerceConsentTransfer::GRANTED_SCOPES => [AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B],
        ]);

        // Act
        (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentGrantedScopes(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            [AgenticCommerceHelper::TEST_SCOPE_A],
        );

        // Assert
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getGrantedScopes());
    }

    public function testGivenAStoredRefreshTokenIdentifierWhenTheConsentIsReadThenItCarriesTheIdentifier(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);

        // Act
        $storedConsentTransfer = (new AgenticCommerceRepository())->findAgenticCommerceConsentByUuid($consentTransfer->getUuidOrFail());

        // Assert
        $this->assertSame(static::REFRESH_TOKEN_IDENTIFIER, $storedConsentTransfer?->getRefreshTokenIdentifier());
    }

    public function testGivenANeverUsedConsentWhenLastUsedAtIsUpdatedThenItIsWritten(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentLastUsedAt(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            $this->getLastUsedAtWriteIntervalInSeconds(),
        );

        // Assert
        $this->assertNotNull($this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAt());
    }

    public function testGivenAConsentUsedWithinTheWriteIntervalWhenLastUsedAtIsUpdatedThenItIsNotWritten(): void
    {
        // Arrange
        $lastUsedAt = $this->formatSecondsAgo(static::SECONDS_WITHIN_THE_WRITE_INTERVAL);
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::LAST_USED_AT => $lastUsedAt]);

        // Act
        (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentLastUsedAt(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            $this->getLastUsedAtWriteIntervalInSeconds(),
        );

        // Assert
        $this->assertSame($lastUsedAt, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAt());
    }

    public function testGivenAConsentUsedBeforeTheWriteIntervalWhenLastUsedAtIsUpdatedThenItIsWritten(): void
    {
        // Arrange
        $lastUsedAt = $this->formatSecondsAgo(static::SECONDS_BEYOND_THE_WRITE_INTERVAL);
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::LAST_USED_AT => $lastUsedAt]);

        // Act
        (new AgenticCommerceEntityManager())->updateAgenticCommerceConsentLastUsedAt(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            $this->getLastUsedAtWriteIntervalInSeconds(),
        );

        // Assert
        $this->assertGreaterThan($lastUsedAt, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAtOrFail());
    }

    public function testGivenActiveRevokedAndExpiredConsentsOfAPrincipalWhenListingActiveUuidsByPrincipalThenOnlyTheActiveOneIsReturned(): void
    {
        // Arrange
        $principalSeed = [
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE,
        ];
        $activeConsentTransfer = $this->haveActiveConsentBesideRevokedAndExpiredOnes($this->tester->haveAgenticCommerceCustomer(), $principalSeed);

        // Act
        $consentUuids = (new AgenticCommerceRepository())->getActiveAgenticCommerceConsentUuidsByPrincipal(
            AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
            static::PRINCIPAL_REFERENCE,
        );

        // Assert
        $this->assertSame([$activeConsentTransfer->getUuid()], $consentUuids);
    }

    public function testGivenActiveRevokedAndExpiredConsentsOfAClientWhenListingActiveUuidsByClientThenOnlyTheActiveOneIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $activeConsentTransfer = $this->haveActiveConsentBesideRevokedAndExpiredOnes(
            $this->tester->haveAgenticCommerceCustomer(),
            [AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()],
        );

        // Act
        $consentUuids = (new AgenticCommerceRepository())->getActiveAgenticCommerceConsentUuidsByClient($clientTransfer->getClientIdentifierOrFail());

        // Assert
        $this->assertSame([$activeConsentTransfer->getUuid()], $consentUuids);
    }

    public function testGivenActiveRevokedAndExpiredConsentsOfACustomerWhenListingActiveUuidsByCustomerReferenceThenOnlyTheActiveOneIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $activeConsentTransfer = $this->haveActiveConsentBesideRevokedAndExpiredOnes($customerTransfer, []);
        $this->tester->haveAgenticCommerceConsent();

        // Act
        $consentUuids = (new AgenticCommerceRepository())->getActiveAgenticCommerceConsentUuidsByCustomerReference($customerTransfer->getCustomerReferenceOrFail());

        // Assert
        $this->assertSame([$activeConsentTransfer->getUuid()], $consentUuids);
    }

    public function testGivenThreeEventsWhenReadingTheConsentEventsThenTheyAreReturnedOldestFirst(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $entityManager = new AgenticCommerceEntityManager();
        $eventNames = [
            AgenticCommerceConfig::CONSENT_EVENT_GRANTED,
            AgenticCommerceConfig::CONSENT_EVENT_SCOPES_NARROWED,
            AgenticCommerceConfig::CONSENT_EVENT_REVOKED,
        ];

        foreach ($eventNames as $eventName) {
            $entityManager->createAgenticCommerceConsentEvent(
                (new AgenticCommerceConsentEventTransfer())
                    ->setIdAgenticCommerceConsent($consentTransfer->getIdAgenticCommerceConsentOrFail())
                    ->setEvent($eventName)
                    ->setActor(AgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER)
                    ->setScopes([AgenticCommerceHelper::SCOPE]),
            );
        }

        // Act
        $readEventNames = $this->tester->getConsentEventNames($consentTransfer->getUuidOrFail());

        // Assert
        $this->assertSame($eventNames, $readEventNames);
    }

    /**
     * @param array<string, mixed> $seed
     */
    protected function haveActiveConsentBesideRevokedAndExpiredOnes(CustomerTransfer $customerTransfer, array $seed): AgenticCommerceConsentTransfer
    {
        $activeConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed + [AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed + [
            AgenticCommerceConsentTransfer::EXPIRES_AT => (new DateTime(static::PAST))->format(static::DATE_TIME_FORMAT),
        ]);

        return $activeConsentTransfer;
    }

    protected function getLastUsedAtWriteIntervalInSeconds(): int
    {
        return (new ZedAgenticCommerceConfig())->getLastUsedAtWriteIntervalInSeconds();
    }

    protected function formatSecondsAgo(int $seconds): string
    {
        return (new DateTime())->modify(sprintf('-%d seconds', $seconds))->format(static::DATE_TIME_FORMAT);
    }
}
