<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group RevokeAgenticCommerceConsentsBySystemTest
 * Add your own group annotations below this line
 */
class RevokeAgenticCommerceConsentsBySystemTest extends Unit
{
    protected const string ERROR_REVOKED_BY_UNSUPPORTED = 'agentic_commerce.consent.validation.revoked_by_unsupported';

    protected const string ERROR_SELECTOR_REQUIRED = 'agentic_commerce.consent.validation.exactly_one_selector_required';

    protected const string PRINCIPAL_TYPE_COMPANY_USER = 'company_user';

    protected const string PRINCIPAL_REFERENCE_REVOKED = 'company-user-revoked';

    protected const string PRINCIPAL_REFERENCE_UNTOUCHED = 'company-user-untouched';

    protected const string REFRESH_TOKEN_IDENTIFIER = 'consent-uuid.refresh-token-identifier';

    protected const string UNSUPPORTED_REVOKED_BY = 'someone';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenAnActiveConsentWhenRevokingByItsUuidThenItIsRevokedWithTheGivenRevokedByAndReturned(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $responseTransfer = $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            $this->createRevocationRequestByUuid($consentTransfer, AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame($consentTransfer->getUuid(), $responseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuid());

        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $storedConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT, $storedConsentTransfer->getRevokedBy());
        $this->assertNotNull($storedConsentTransfer->getRevokedAt());
    }

    public function testGivenAConsentWithARefreshTokenWhenRevokedThenTheRefreshTokenIdentifierIsCleared(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER]);

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            $this->createRevocationRequestByUuid($consentTransfer, AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_REUSE_DETECTED),
        );

        // Assert
        $this->assertNull($this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    public function testGivenARevokedConsentWhenRevokedAgainThenNothingIsRevokedAndNoSecondEventIsWritten(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            $this->createRevocationRequestByUuid($consentTransfer, AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT),
        );

        // Act
        $responseTransfer = $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            $this->createRevocationRequestByUuid($consentTransfer, AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CODE_REPLAY),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getRevokedBy());
        $this->assertCount(1, $this->tester->getConsentEvents($consentTransfer->getUuidOrFail()));
    }

    /**
     * @dataProvider revokedByToEventDataProvider
     */
    public function testGivenAnActiveConsentAndEachSupportedRevokedByValueWhenRevokedThenOneEventWithTheMatchingNameAndTheRevokedByAsActorIsWritten(
        string $revokedBy,
        string $expectedEvent
    ): void {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($this->createRevocationRequestByUuid($consentTransfer, $revokedBy));

        // Assert
        $consentEventTransfers = $this->tester->getConsentEvents($consentTransfer->getUuidOrFail());
        $this->assertCount(1, $consentEventTransfers);
        $this->assertSame($expectedEvent, $consentEventTransfers[0]->getEvent());
        $this->assertSame($revokedBy, $consentEventTransfers[0]->getActor());
        $this->assertSame($consentTransfer->getGrantedScopes(), $consentEventTransfers[0]->getScopes());
        $this->assertNull($consentEventTransfers[0]->getClientName());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function revokedByToEventDataProvider(): array
    {
        return [
            'reuse detected' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_REUSE_DETECTED, AgenticCommerceConfig::CONSENT_EVENT_REUSE_DETECTED],
            'code replay' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CODE_REPLAY, AgenticCommerceConfig::CONSENT_EVENT_CODE_REPLAYED],
            'client blocked' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED, AgenticCommerceConfig::CONSENT_EVENT_REVOKED],
            'principal inactive' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE, AgenticCommerceConfig::CONSENT_EVENT_REVOKED],
            'company admin' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_COMPANY_ADMIN, AgenticCommerceConfig::CONSENT_EVENT_REVOKED],
            'client' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT, AgenticCommerceConfig::CONSENT_EVENT_REVOKED],
            'customer' => [AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, AgenticCommerceConfig::CONSENT_EVENT_REVOKED],
        ];
    }

    public function testGivenConsentsOfTwoPrincipalsWhenRevokingByOnePrincipalThenOnlyItsConsentsAreRevoked(): void
    {
        // Arrange
        $revokedConsentTransfer = $this->tester->haveAgenticCommerceConsent([
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => static::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE_REVOKED,
        ]);
        $untouchedConsentTransfer = $this->tester->haveAgenticCommerceConsent([
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => static::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE_UNTOUCHED,
        ]);
        $revocationRequestTransfer = (new AgenticCommerceConsentRevocationRequestTransfer())
            ->setPrincipalType(static::PRINCIPAL_TYPE_COMPANY_USER)
            ->setPrincipalReference(static::PRINCIPAL_REFERENCE_REVOKED)
            ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_COMPANY_ADMIN);

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($revocationRequestTransfer);

        // Assert
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($revokedConsentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($untouchedConsentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenConsentsOfTwoCustomersWhenRevokingByOneCustomerReferenceThenOnlyThatCustomersConsentsAreRevoked(): void
    {
        // Arrange
        $revokedConsentTransfer = $this->tester->haveAgenticCommerceConsent();
        $untouchedConsentTransfer = $this->tester->haveAgenticCommerceConsent();
        $revocationRequestTransfer = (new AgenticCommerceConsentRevocationRequestTransfer())
            ->setCustomerReference($revokedConsentTransfer->getCustomerReferenceOrFail())
            ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE);

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($revocationRequestTransfer);

        // Assert
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($revokedConsentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($untouchedConsentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenConsentsOfTwoClientsWhenRevokingByOneClientIdentifierThenOnlyThatClientsConsentsAreRevoked(): void
    {
        // Arrange
        $revokedConsentTransfer = $this->tester->haveAgenticCommerceConsent();
        $untouchedConsentTransfer = $this->tester->haveAgenticCommerceConsent();
        $revocationRequestTransfer = (new AgenticCommerceConsentRevocationRequestTransfer())
            ->setClientIdentifier($revokedConsentTransfer->getClientIdentifierOrFail())
            ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED);

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($revocationRequestTransfer);

        // Assert
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($revokedConsentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($untouchedConsentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenAnUnsupportedRevokedByValueWhenRevokingBySystemThenARevokedByUnsupportedErrorIsReturnedAndNothingIsRevoked(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $responseTransfer = $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            $this->createRevocationRequestByUuid($consentTransfer, static::UNSUPPORTED_REVOKED_BY),
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_REVOKED_BY_UNSUPPORTED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenNoSelectorWhenRevokingBySystemThenASelectorRequiredErrorIsReturned(): void
    {
        // Arrange
        $revocationRequestTransfer = (new AgenticCommerceConsentRevocationRequestTransfer())
            ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT);

        // Act
        $responseTransfer = $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($revocationRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_SELECTOR_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenTwoSelectorsWhenRevokingBySystemThenASelectorRequiredErrorIsReturnedAndNothingIsRevoked(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $revocationRequestTransfer = $this->createRevocationRequestByUuid($consentTransfer, AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT)
            ->setClientIdentifier($consentTransfer->getClientIdentifierOrFail());

        // Act
        $responseTransfer = $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($revocationRequestTransfer);

        // Assert
        $this->assertSame(static::ERROR_SELECTOR_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenAPrincipalTypeWithoutAPrincipalReferenceWhenRevokingBySystemThenASelectorErrorIsReturnedAndNothingIsRevoked(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $revocationRequestTransfer = (new AgenticCommerceConsentRevocationRequestTransfer())
            ->setPrincipalType(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
            ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_COMPANY_ADMIN);

        // Act
        $responseTransfer = $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem($revocationRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_SELECTOR_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertCount(0, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenAPostRevokePluginAndTwoConsentsOfOneClientWhenRevokingByClientThenThePluginIsCalledOnceWithBothRevokedConsents(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveAgenticCommerceClient()->getClientIdentifierOrFail();
        $firstConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientIdentifier]);
        $secondConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientIdentifier]);
        $expectedConsentUuids = [$firstConsentTransfer->getUuidOrFail(), $secondConsentTransfer->getUuidOrFail()];
        sort($expectedConsentUuids);
        $postRevokePluginMock = $this->createMock(AgenticCommerceConsentPostRevokePluginInterface::class);
        $postRevokePluginMock->expects($this->once())
            ->method('postRevoke')
            ->with($this->callback(
                fn (AgenticCommerceConsentCollectionTransfer $consentCollectionTransfer): bool => $this->extractSortedRevokedUuids($consentCollectionTransfer) === $expectedConsentUuids,
            ));
        $this->tester->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, [$postRevokePluginMock]);

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            (new AgenticCommerceConsentRevocationRequestTransfer())
                ->setClientIdentifier($clientIdentifier)
                ->setRevokedBy(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED),
        );
    }

    public function testGivenAPostRevokePluginAndAnAlreadyRevokedConsentWhenRevokingItBySystemThenThePluginIsNotCalled(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]);
        $postRevokePluginMock = $this->createMock(AgenticCommerceConsentPostRevokePluginInterface::class);
        $postRevokePluginMock->expects($this->never())->method('postRevoke');
        $this->tester->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, [$postRevokePluginMock]);

        // Act
        $this->tester->getFacade()->revokeAgenticCommerceConsentsBySystem(
            $this->createRevocationRequestByUuid($consentTransfer, AgenticCommerceConfig::CONSENT_REVOKED_BY_CLIENT),
        );
    }

    protected function createRevocationRequestByUuid(
        AgenticCommerceConsentTransfer $consentTransfer,
        string $revokedBy
    ): AgenticCommerceConsentRevocationRequestTransfer {
        return (new AgenticCommerceConsentRevocationRequestTransfer())
            ->addConsentUuid($consentTransfer->getUuidOrFail())
            ->setRevokedBy($revokedBy);
    }

    /**
     * @return list<string>
     */
    protected function extractSortedRevokedUuids(AgenticCommerceConsentCollectionTransfer $consentCollectionTransfer): array
    {
        $consentUuids = [];

        foreach ($consentCollectionTransfer->getAgenticCommerceConsents() as $consentTransfer) {
            if ($consentTransfer->getStatus() === AgenticCommerceConfig::CONSENT_STATUS_REVOKED) {
                $consentUuids[] = $consentTransfer->getUuidOrFail();
            }
        }

        sort($consentUuids);

        return $consentUuids;
    }
}
