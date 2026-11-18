<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Persistence;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceClientRedirectHostTableMap;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientRedirectHostQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
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
 * @group AgenticCommerceClientPersistenceTest
 * Add your own group annotations below this line
 */
class AgenticCommerceClientPersistenceTest extends Unit
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string LONG_AGO = '2000-01-01 00:00:00';

    protected AgenticCommercePersistenceTester $tester;

    public function testGivenAClientWhenFoundByItsIdentifierThenItCarriesTheOauthClientName(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $foundClientTransfer = (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientTransfer->getClientIdentifierOrFail());

        // Assert
        $this->assertNotNull($foundClientTransfer);
        $this->assertSame(AgenticCommerceHelper::CLIENT_NAME, $foundClientTransfer->getName());
        $this->assertSame([AgenticCommerceHelper::REDIRECT_URI], $foundClientTransfer->getRedirectUris());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $foundClientTransfer->getScopeCeiling());
    }

    public function testGivenAnUnknownIdentifierWhenFindingAClientThenNullIsReturned(): void
    {
        // Act
        $foundClientTransfer = (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier('unknown-delegated-access-client');

        // Assert
        $this->assertNull($foundClientTransfer);
    }

    public function testGivenAnActiveClientWhenItsStatusChangesThenTrueIsReturnedAndUpdatedAtMovesForward(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        SpyAgenticCommerceClientQuery::create()->filterByFkOauthClient($clientTransfer->getClientIdentifierOrFail())->update(['UpdatedAt' => static::LONG_AGO]);

        // Act
        $isUpdated = (new AgenticCommerceEntityManager())->updateAgenticCommerceClientStatus(
            $clientTransfer->getClientIdentifierOrFail(),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertTrue($isUpdated);
        $clientEntity = SpyAgenticCommerceClientQuery::create()->findOneByFkOauthClient($clientTransfer->getClientIdentifierOrFail());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $clientEntity->getStatus());
        $this->assertGreaterThan(new DateTime(static::LONG_AGO), $clientEntity->getUpdatedAt());
    }

    public function testGivenAnActiveClientWhenSetToActiveAgainThenFalseIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $isUpdated = (new AgenticCommerceEntityManager())->updateAgenticCommerceClientStatus(
            $clientTransfer->getClientIdentifierOrFail(),
            AgenticCommerceConfig::CLIENT_STATUS_ACTIVE,
        );

        // Assert
        $this->assertFalse($isUpdated);
    }

    public function testGivenAnUnknownClientWhenItsStatusIsUpdatedThenFalseIsReturned(): void
    {
        // Act
        $isUpdated = (new AgenticCommerceEntityManager())->updateAgenticCommerceClientStatus(
            'unknown-delegated-access-client',
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertFalse($isUpdated);
    }

    public function testGivenAnActiveConsentWhenRevokedThenUpdatedAtMovesForward(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        SpyAgenticCommerceConsentQuery::create()->filterByUuid($consentTransfer->getUuidOrFail())->update(['UpdatedAt' => static::LONG_AGO]);

        // Act
        (new AgenticCommerceEntityManager())->revokeAgenticCommerceConsent(
            $consentTransfer->getIdAgenticCommerceConsentOrFail(),
            AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT,
        );

        // Assert
        $consentEntity = SpyAgenticCommerceConsentQuery::create()->findOneByUuid($consentTransfer->getUuidOrFail());
        $this->assertGreaterThan(new DateTime(static::LONG_AGO), $consentEntity->getUpdatedAt());
    }

    public function testGivenAClientWhenItsMetadataIsUpdatedThenOnlyTheMetadataColumnsChange(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        (new AgenticCommerceEntityManager())->updateAgenticCommerceClientStatus($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED);
        $metadataTransfer = (new AgenticCommerceClientTransfer())
            ->setClientIdentifier($clientTransfer->getClientIdentifierOrFail())
            ->setClientUri('https://client.example/home')
            ->setLogoUri('https://client.example/logo.png')
            ->setRedirectUris(['https://client.example/callback', 'https://client.example/other'])
            ->setMetadataFetchedAt('2026-10-19 10:00:00')
            ->setMetadataExpiresAt('2026-10-19 11:00:00')
            ->setStatus(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE)
            ->setScopeCeiling(['other:scope'])
            ->setRegistrationType(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC)
            ->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED);

        // Act
        (new AgenticCommerceEntityManager())->updateAgenticCommerceClientMetadata($metadataTransfer);

        // Assert
        $foundClientTransfer = (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientTransfer->getClientIdentifierOrFail());
        $this->assertSame('https://client.example/home', $foundClientTransfer->getClientUri());
        $this->assertSame('https://client.example/logo.png', $foundClientTransfer->getLogoUri());
        $this->assertSame(['https://client.example/callback', 'https://client.example/other'], $foundClientTransfer->getRedirectUris());
        $this->assertSame('2026-10-19 10:00:00', $foundClientTransfer->getMetadataFetchedAt());
        $this->assertSame('2026-10-19 11:00:00', $foundClientTransfer->getMetadataExpiresAt());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $foundClientTransfer->getStatus());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $foundClientTransfer->getScopeCeiling());
        $this->assertSame(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED, $foundClientTransfer->getRegistrationType());
        $this->assertSame(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED, $foundClientTransfer->getTrustLevel());
    }

    public function testGivenRedirectUrisInSeveralSpellingsWhenAClientIsCreatedThenEachNormalizedHostIsStoredOnce(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveOauthClientWithoutAgenticCommerceClient();
        $clientTransfer = (new AgenticCommerceClientTransfer())
            ->setClientIdentifier($clientIdentifier)
            ->setRedirectUris(['https://Client.Example./callback', 'https://client.example/other', 'http://[0:0:0:0:0:0:0:1]:8080/cb'])
            ->setScopeCeiling([])
            ->setRegistrationType(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC)
            ->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED)
            ->setStatus(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE);

        // Act
        $createdClientTransfer = (new AgenticCommerceEntityManager())->createAgenticCommerceClient($clientTransfer);

        // Assert
        $this->assertSame(['[::1]', 'client.example'], $this->findStoredRedirectHosts($createdClientTransfer->getIdAgenticCommerceClientOrFail()));
    }

    public function testGivenAClientWhenItsMetadataMovesTheRedirectUrisToAnotherHostThenTheStoredHostsAreReplaced(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://old.example/callback']]);
        $metadataTransfer = (new AgenticCommerceClientTransfer())
            ->setClientIdentifier($clientTransfer->getClientIdentifierOrFail())
            ->setRedirectUris(['https://NEW.example/callback'])
            ->setMetadataFetchedAt('2026-10-19 10:00:00')
            ->setMetadataExpiresAt('2026-10-19 11:00:00');

        // Act
        (new AgenticCommerceEntityManager())->updateAgenticCommerceClientMetadata($metadataTransfer);

        // Assert
        $this->assertSame(['new.example'], $this->findStoredRedirectHosts($clientTransfer->getIdAgenticCommerceClientOrFail()));
        $this->assertSame([], $this->findClientIdentifiers((new AgenticCommerceClientConditionsTransfer())->addRedirectHost('old.example')));
        $this->assertSame(
            [$clientTransfer->getClientIdentifierOrFail()],
            $this->findClientIdentifiers((new AgenticCommerceClientConditionsTransfer())->addRedirectHost('new.example')),
        );
    }

    public function testGivenAClientWhenItsScopeCeilingChangesThenTrueIsReturnedAndTheCeilingIsStored(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $isUpdated = (new AgenticCommerceEntityManager())->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), ['a:read', 'b:read']);

        // Assert
        $this->assertTrue($isUpdated);
        $foundClientTransfer = (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientTransfer->getClientIdentifierOrFail());
        $this->assertSame(['a:read', 'b:read'], $foundClientTransfer->getScopeCeiling());
    }

    public function testGivenAClientWhenTheSameScopeCeilingIsWrittenThenFalseIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $isUpdated = (new AgenticCommerceEntityManager())->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), [AgenticCommerceHelper::SCOPE]);

        // Assert
        $this->assertFalse($isUpdated);
    }

    public function testGivenAnUnknownClientWhenItsScopeCeilingChangesThenFalseIsReturned(): void
    {
        // Act
        $isUpdated = (new AgenticCommerceEntityManager())->updateAgenticCommerceClientScopeCeiling('unknown-delegated-access-client', ['a:read']);

        // Assert
        $this->assertFalse($isUpdated);
    }

    public function testGivenASelfRegisteredClientWhenCreatedThenTheSelfRegistrationColumnsRoundTrip(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveOauthClientWithoutAgenticCommerceClient();
        $clientTransfer = (new AgenticCommerceClientTransfer())
            ->setClientIdentifier($clientIdentifier)
            ->setRedirectUris([AgenticCommerceHelper::REDIRECT_URI])
            ->setScopeCeiling([])
            ->setRegistrationType(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC)
            ->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED)
            ->setStatus(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE)
            ->setSoftwareId('software-1')
            ->setSoftwareVersion('1.2.3')
            ->setRegistrationMetadata('{"client_name":"Example"}')
            ->setMetadataFetchedAt('2026-10-19 10:00:00')
            ->setMetadataExpiresAt('2026-10-19 11:00:00');

        // Act
        (new AgenticCommerceEntityManager())->createAgenticCommerceClient($clientTransfer);

        // Assert
        $foundClientTransfer = (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientIdentifier);
        $this->assertSame('software-1', $foundClientTransfer->getSoftwareId());
        $this->assertSame('1.2.3', $foundClientTransfer->getSoftwareVersion());
        $this->assertSame('{"client_name":"Example"}', $foundClientTransfer->getRegistrationMetadata());
        $this->assertSame('2026-10-19 10:00:00', $foundClientTransfer->getMetadataFetchedAt());
        $this->assertSame('2026-10-19 11:00:00', $foundClientTransfer->getMetadataExpiresAt());
    }

    public function testGivenClientsWithDifferentSoftwareIdsWhenFilteredBySoftwareIdThenOnlyThoseClientsAreReturned(): void
    {
        // Arrange
        $softwareId = 'software-' . uniqid();
        $matchingClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId . '-other']);
        $this->tester->haveAgenticCommerceClient();

        // Act
        $clientIdentifiers = $this->findClientIdentifiers((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId));

        // Assert
        $this->assertSame([$matchingClientTransfer->getClientIdentifierOrFail()], $clientIdentifiers);
    }

    public function testGivenClientsWithSimilarRedirectHostsWhenFilteredByRedirectHostThenOnlyAnExactHostMatchIsReturned(): void
    {
        // Arrange
        $host = 'claude-' . uniqid() . '.example';
        $exactClientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://other.example/cb', sprintf('https://%s:8443/cb', $host)],
        ]);
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s.evil.example/cb', $host)]]);
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://evil.example/cb?next=https://%s/', $host)]]);
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://sub.%s/cb', $host)]]);

        // Act
        $clientIdentifiers = $this->findClientIdentifiers((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host));

        // Assert
        $this->assertSame([$exactClientTransfer->getClientIdentifierOrFail()], $clientIdentifiers);
    }

    public function testGivenAClientWithAnIpv6LoopbackRedirectWhenFilteredByItsBracketedHostThenItIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => ['http://[::1]:7777/cb']]);

        // Act
        $clientIdentifiers = $this->findClientIdentifiers((new AgenticCommerceClientConditionsTransfer())->addRedirectHost('[::1]'));

        // Assert
        $this->assertContains($clientTransfer->getClientIdentifierOrFail(), $clientIdentifiers);
    }

    public function testGivenARedirectHostWithLikeWildcardsWhenFilteredThenTheWildcardsMatchNothing(): void
    {
        // Arrange
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://anything.example/cb']]);

        // Act
        $clientIdentifiers = $this->findClientIdentifiers((new AgenticCommerceClientConditionsTransfer())->addRedirectHost('%'));

        // Assert
        $this->assertSame([], $clientIdentifiers);
    }

    public function testGivenAMatchingRedirectHostAndSoftwareIdWhenFilteredTogetherThenBothMustMatch(): void
    {
        // Arrange
        $host = 'both-' . uniqid() . '.example';
        $softwareId = 'software-' . uniqid();
        $matchingClientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/cb', $host)],
            AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId,
        ]);
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/cb', $host)]]);

        // Act
        $clientIdentifiers = $this->findClientIdentifiers(
            (new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host)->addSoftwareId($softwareId),
        );

        // Assert
        $this->assertSame([$matchingClientTransfer->getClientIdentifierOrFail()], $clientIdentifiers);
    }

    public function testGivenMoreRedirectHostMatchesThanOnePageWhenPaginatedThenThePaginationCountsOnlyExactMatches(): void
    {
        // Arrange
        $host = 'paged-' . uniqid() . '.example';
        for ($index = 0; $index < 3; $index++) {
            $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/cb', $host)]]);
        }
        $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s.evil.example/cb', $host)]]);
        $criteriaTransfer = (new AgenticCommerceClientCriteriaTransfer())
            ->setAgenticCommerceClientConditions((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host))
            ->setPagination((new PaginationTransfer())->setPage(1)->setMaxPerPage(2));

        // Act
        $collectionTransfer = (new AgenticCommerceRepository())->getAgenticCommerceClientCollection($criteriaTransfer);

        // Assert
        $this->assertCount(2, $collectionTransfer->getAgenticCommerceClients());
        $this->assertSame(3, $collectionTransfer->getPagination()->getNbResults());
    }

    /**
     * @return list<string>
     */
    protected function findStoredRedirectHosts(int $idAgenticCommerceClient): array
    {
        $storedRedirectHosts = SpyAgenticCommerceClientRedirectHostQuery::create()
            ->filterByFkAgenticCommerceClient($idAgenticCommerceClient)
            ->select([SpyAgenticCommerceClientRedirectHostTableMap::COL_HOST])
            ->find()
            ->getData();
        sort($storedRedirectHosts);

        return $storedRedirectHosts;
    }

    /**
     * @return list<string>
     */
    protected function findClientIdentifiers(AgenticCommerceClientConditionsTransfer $conditionsTransfer): array
    {
        $collectionTransfer = (new AgenticCommerceRepository())->getAgenticCommerceClientCollection(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions($conditionsTransfer),
        );

        return array_map(
            static fn (AgenticCommerceClientTransfer $clientTransfer): string => $clientTransfer->getClientIdentifierOrFail(),
            $collectionTransfer->getAgenticCommerceClients()->getArrayCopy(),
        );
    }
}
