<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group UpdateAgenticCommerceClientStatusByCriteriaTest
 * Add your own group annotations below this line
 */
class UpdateAgenticCommerceClientStatusByCriteriaTest extends Unit
{
    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_NOT_FOUND = 'agentic_commerce.client.validation.not_found';

    protected const string ERROR_STATUS_UNSUPPORTED = 'agentic_commerce.client.validation.status_unsupported';

    protected const string ERROR_CRITERIA_REQUIRED = 'agentic_commerce.client.validation.criteria_required';

    protected const string ERROR_REDIRECT_HOST_INVALID = 'agentic_commerce.client.validation.redirect_host_invalid';

    protected const int SINGLE_CONSENT_BATCH_SIZE = 1;

    protected const int CONSENT_COUNT = 3;

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenTwoClientsOfOneSoftwareAndOneOtherWhenBlockingBySoftwareIdThenOnlyTheirConsentsAreRevoked(): void
    {
        // Arrange
        $badSoftwareId = uniqid('bad-software-');
        $firstClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $badSoftwareId]);
        $secondClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $badSoftwareId]);
        $otherClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => uniqid('good-software-')]);
        $firstConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $firstClientTransfer->getClientIdentifierOrFail()]);
        $secondConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $secondClientTransfer->getClientIdentifierOrFail()]);
        $otherConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $otherClientTransfer->getClientIdentifierOrFail()]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($badSoftwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(2, $responseTransfer->getAgenticCommerceClients());
        $this->assertCount(2, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED, $this->tester->findConsentByUuid($firstConsentTransfer->getUuidOrFail())->getRevokedBy());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED, $this->tester->findConsentByUuid($secondConsentTransfer->getUuidOrFail())->getRevokedBy());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($otherConsentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($otherClientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenRedirectHostsThatOnlyShareASuffixWhenBlockingByRedirectHostThenOnlyTheExactHostIsBlocked(): void
    {
        // Arrange
        $host = sprintf('%s.evil.example', uniqid());
        $exactClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/callback', $host)]]);
        $suffixClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://not%s/callback', $host)]]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $this->tester->findClient($exactClientTransfer->getClientIdentifierOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($suffixClientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAnUpperCaseRedirectHostWhenBlockingByRedirectHostThenTheClientWithTheLowerCaseHostIsBlocked(): void
    {
        // Arrange
        $host = sprintf('%s.evil.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/callback', $host)]]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost(strtoupper($host))),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAMixedCaseStoredRedirectHostWhenBlockingByTheLowerCaseHostThenTheClientIsBlocked(): void
    {
        // Arrange
        $host = sprintf('%s.evil.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://Evil-%s/callback', $host)]]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost(sprintf('evil-%s', $host))),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenABlockedClientOfOneSoftwareWhenUnblockingBySoftwareIdThenItIsActiveAgain(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId,
            AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_ACTIVE,
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAClientOfASoftwareIdWhenBlockingBySoftwareIdThenItIsBlockedBySoftwareId(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId,
            $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy(),
        );
    }

    public function testGivenAMixedCaseRedirectHostWhenBlockingByAnUpperCaseRedirectHostThenItIsBlockedByTheLowerCaseHost(): void
    {
        // Arrange
        $host = sprintf('%s.evil.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://Evil-%s/callback', $host)]]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost(strtoupper(sprintf('evil-%s', $host)))),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . sprintf('evil-%s', $host),
            $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy(),
        );
    }

    public function testGivenASoftwareIdAndARedirectHostWhenBlockingByBothThenTheClientIsBlockedBySoftwareId(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $host = sprintf('%s.evil.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId,
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/callback', $host)],
        ]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host)->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId,
            $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy(),
        );
    }

    public function testGivenAClientWhenBlockingByClientIdentifierThenItIsBlockedWithoutAFamily(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => uniqid('software-')]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addClientIdentifier($clientTransfer->getClientIdentifierOrFail())),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $storedClientTransfer = $this->tester->findClient($clientTransfer->getClientIdentifierOrFail());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $storedClientTransfer->getStatus());
        $this->assertNull($storedClientTransfer->getBlockedBy());
    }

    public function testGivenAClientBlockedBySoftwareIdWhenBlockingItsRedirectHostThenItKeepsTheSoftwareIdFamilyBlock(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $host = sprintf('%s.evil.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId,
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/callback', $host)],
        ]);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId,
            $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy(),
        );
    }

    public function testGivenAClientBlockedByRedirectHostWhenBlockingItsSoftwareIdThenItKeepsTheRedirectHostFamilyBlock(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $host = sprintf('%s.evil.example', uniqid());
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId,
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s/callback', $host)],
        ]);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($host)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . $host,
            $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy(),
        );
    }

    public function testGivenAClientWithAFamilyBlockWhenBlockingItByClientIdentifierThenItKeepsTheFamilyBlock(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addClientIdentifier($clientTransfer->getClientIdentifierOrFail())),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );
        $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Assert
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId,
            $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getBlockedBy(),
        );
    }

    public function testGivenAClientBlockedBySoftwareIdWhenUnblockingBySoftwareIdThenItsFamilyBlockIsCleared(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);
        $conditionsTransfer = (new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria($this->createCriteria($conditionsTransfer), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria($this->createCriteria($conditionsTransfer), AgenticCommerceConfig::CLIENT_STATUS_ACTIVE);

        // Assert
        $storedClientTransfer = $this->tester->findClient($clientTransfer->getClientIdentifierOrFail());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $storedClientTransfer->getStatus());
        $this->assertNull($storedClientTransfer->getBlockedBy());
    }

    public function testGivenEmptyCriteriaWhenBlockingThenAnErrorIsReturnedAndNoClientChanges(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            new AgenticCommerceClientCriteriaTransfer(),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_CRITERIA_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenOnlyAStatusConditionWhenBlockingThenACriteriaRequiredErrorIsReturnedAndNoClientChanges(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addStatus(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(static::ERROR_CRITERIA_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAnEmptyRedirectHostWhenBlockingThenACriteriaRequiredErrorIsReturnedAndNoClientChanges(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost('')),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(static::ERROR_CRITERIA_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAnEmptySoftwareIdWhenBlockingThenACriteriaRequiredErrorIsReturnedAndNoClientChanges(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId('')),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(static::ERROR_CRITERIA_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAnUnsupportedStatusWhenUpdatingByCriteriaThenAStatusUnsupportedErrorIsReturnedAndTheClientStaysActive(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            'suspended',
        );

        // Assert
        $this->assertSame(static::ERROR_STATUS_UNSUPPORTED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAClientOfASoftwareIdWhenBlockingBySoftwareIdThenTheReturnedClientIsBlockedBySoftwareId(): void
    {
        // Arrange
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
        $returnedClientTransfer = $responseTransfer->getAgenticCommerceClients()->offsetGet(0);
        $this->assertSame($clientTransfer->getClientIdentifierOrFail(), $returnedClientTransfer->getClientIdentifier());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $returnedClientTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_SOFTWARE_ID . $softwareId, $returnedClientTransfer->getBlockedBy());
    }

    public function testGivenAClientWithSeveralConsentsAndARevocationBatchSizeOfOneWhenBlockingBySoftwareIdThenEveryConsentOfTheClientIsRevoked(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getConsentRevocationBatchSize', static::SINGLE_CONSENT_BATCH_SIZE);
        $softwareId = uniqid('software-');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SOFTWARE_ID => $softwareId]);
        $consentUuids = [];

        for ($consentIndex = 0; $consentIndex < static::CONSENT_COUNT; $consentIndex++) {
            $consentUuids[] = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()])->getUuidOrFail();
        }

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addSoftwareId($softwareId)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(static::CONSENT_COUNT, $responseTransfer->getAgenticCommerceConsents());

        foreach ($consentUuids as $consentUuid) {
            $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($consentUuid)->getStatus());
        }
    }

    /**
     * @dataProvider loopbackRedirectHostDataProvider
     */
    public function testGivenAClientWithALoopbackRedirectUriWhenBlockingByALoopbackRedirectHostThenARedirectHostInvalidErrorIsReturnedAndTheClientStaysActive(
        string $redirectUri,
        string $redirectHost
    ): void {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::REDIRECT_URIS => [$redirectUri]]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addRedirectHost($redirectHost)),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_REDIRECT_HOST_INVALID, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function loopbackRedirectHostDataProvider(): array
    {
        return [
            'IPv4 loopback' => ['http://127.0.0.1/callback', '127.0.0.1'],
            'IPv6 loopback' => ['http://[::1]/callback', '[::1]'],
            'localhost' => ['http://localhost/callback', 'localhost'],
        ];
    }

    /**
     * @dataProvider missingSelectorConditionsDataProvider
     */
    public function testGivenConditionsWithoutAUsableSelectorWhenBlockingThenACriteriaRequiredErrorIsReturnedAndNoClientChanges(
        AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer
    ): void {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            $this->createCriteria($agenticCommerceClientConditionsTransfer),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(static::ERROR_CRITERIA_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    /**
     * @return array<string, array{\Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer}>
     */
    public static function missingSelectorConditionsDataProvider(): array
    {
        return [
            'an empty client identifier' => [(new AgenticCommerceClientConditionsTransfer())->addClientIdentifier('')],
            'only a registration type' => [(new AgenticCommerceClientConditionsTransfer())->addRegistrationType(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED)],
        ];
    }

    protected function createCriteria(AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer): AgenticCommerceClientCriteriaTransfer
    {
        return (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions($agenticCommerceClientConditionsTransfer);
    }

    protected function createAgenticCommerceClientCollectionRequestTransfer(
        string $clientIdentifier,
        string $status
    ): AgenticCommerceClientCollectionRequestTransfer {
        return (new AgenticCommerceClientCollectionRequestTransfer())
            ->setIsTransactional(true)
            ->addAgenticCommerceClient((new AgenticCommerceClientTransfer())->setClientIdentifier($clientIdentifier)->setStatus($status));
    }
}
