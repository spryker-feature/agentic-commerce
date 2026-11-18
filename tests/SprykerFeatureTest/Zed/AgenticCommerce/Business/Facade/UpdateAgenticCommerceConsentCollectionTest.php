<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
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
 * @group UpdateAgenticCommerceConsentCollectionTest
 * Add your own group annotations below this line
 */
class UpdateAgenticCommerceConsentCollectionTest extends Unit
{
    protected const string ERROR_CONSENT_NOT_FOUND = 'agentic_commerce.consent.validation.not_found';

    protected const string ERROR_CONSENT_NOT_ACTIVE = 'agentic_commerce.consent.validation.not_active';

    protected const string ERROR_STATUS_TRANSITION_UNSUPPORTED = 'agentic_commerce.consent.validation.status_transition_unsupported';

    protected const string UNKNOWN_CONSENT_UUID = '00000000-0000-0000-0000-000000000000';

    protected const string REFRESH_TOKEN_IDENTIFIER = 'consent-uuid.refresh-token-identifier';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string PAST = '-1 minute';

    protected const string FIRST_ITEM_INDEX = '0';

    protected const string SECOND_ITEM_INDEX = '1';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenAnActiveConsentWhenItsCustomerRevokesItThenItIsRevokedByTheCustomerAndTheRefreshTokenIdentifierIsCleared(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceHelper::SEED_REFRESH_TOKEN_IDENTIFIER => static::REFRESH_TOKEN_IDENTIFIER,
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getAgenticCommerceConsents());
        $revokedConsentTransfer = $responseTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $revokedConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, $revokedConsentTransfer->getRevokedBy());
        $this->assertNotNull($revokedConsentTransfer->getRevokedAt());
        $this->assertNull($this->tester->findConsentRefreshTokenIdentifier($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAnActiveConsentWhenItsCustomerRevokesItThenARevokedEventWithTheCustomerAsActorIsWritten(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );

        // Assert
        $eventTransfers = $this->tester->getConsentEvents($consentTransfer->getUuidOrFail());
        $this->assertCount(1, $eventTransfers);
        $this->assertSame(AgenticCommerceConfig::CONSENT_EVENT_REVOKED, $eventTransfers[0]->getEvent());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, $eventTransfers[0]->getActor());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $eventTransfers[0]->getScopes());
    }

    public function testGivenAnActiveConsentWhenItsCustomerRevokesItThenTheEventStoresNoActorReference(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );

        // Assert
        $this->assertNull($this->tester->getConsentEvents($consentTransfer->getUuidOrFail())[0]->getActorReference());
    }

    public function testGivenAPostRevokePluginWhenACustomerRevokesAConsentThenThePluginReceivesTheConsentOnce(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $postRevokePluginMock = $this->createMock(AgenticCommerceConsentPostRevokePluginInterface::class);
        $postRevokePluginMock->expects($this->once())
            ->method('postRevoke')
            ->with($this->callback(
                fn (AgenticCommerceConsentCollectionTransfer $consentCollectionTransfer): bool => $consentCollectionTransfer->getAgenticCommerceConsents()->count() === 1
                    && $consentCollectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuid() === $consentTransfer->getUuid()
                    && $consentCollectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->getStatus() === AgenticCommerceConfig::CONSENT_STATUS_REVOKED,
            ));
        $this->tester->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, [$postRevokePluginMock]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );
    }

    public function testGivenAConsentOfAnotherCustomerWhenRevokingThenItIsReportedAsNotFoundAndStaysActive(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $this->tester->haveAgenticCommerceCustomer())], true),
        );

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_CONSENT_NOT_FOUND, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame([], $this->tester->getConsentEvents($consentTransfer->getUuidOrFail()));
    }

    public function testGivenARevocationWithoutACustomerReferenceWhenRevokingThenItIsReportedAsNotFoundAndStaysActive(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $revocationTransfer = (new AgenticCommerceConsentTransfer())
            ->setUuid($consentTransfer->getUuidOrFail())
            ->setStatus(AgenticCommerceConfig::CONSENT_STATUS_REVOKED);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection($this->createRequest([$revocationTransfer], true));

        // Assert
        $this->assertSame(static::ERROR_CONSENT_NOT_FOUND, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenAnUnknownUuidWhenRevokingThenItIsReportedAsNotFound(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = (new AgenticCommerceConsentTransfer())->setUuid(static::UNKNOWN_CONSENT_UUID);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );

        // Assert
        $this->assertSame(static::ERROR_CONSENT_NOT_FOUND, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenAnAlreadyRevokedConsentWhenRevokingThenANotActiveErrorIsReturnedAndNoEventIsWritten(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED,
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );

        // Assert
        $this->assertSame(static::ERROR_CONSENT_NOT_ACTIVE, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->tester->getConsentEvents($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAConsentPastItsExpiryWhenRevokingThenANotActiveErrorIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::EXPIRES_AT => (new DateTime(static::PAST))->format(static::DATE_TIME_FORMAT),
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([$this->createRevocation($consentTransfer, $customerTransfer)], true),
        );

        // Assert
        $this->assertSame(static::ERROR_CONSENT_NOT_ACTIVE, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenAStatusOtherThanRevokedWhenUpdatingThenAStatusTransitionUnsupportedErrorIsReturnedAndTheConsentStaysActive(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([
                $this->createRevocation($consentTransfer, $customerTransfer)->setStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE),
            ], true),
        );

        // Assert
        $this->assertSame(static::ERROR_STATUS_TRANSITION_UNSUPPORTED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenATransactionalRequestWithAConsentOfAnotherCustomerWhenRevokingThenNothingIsRevoked(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $foreignConsentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([
                $this->createRevocation($consentTransfer, $customerTransfer),
                $this->createRevocation($foreignConsentTransfer, $customerTransfer),
            ], true),
        );

        // Assert
        $this->assertCount(2, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(static::SECOND_ITEM_INDEX, $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenARequestWithoutTransactionalFlagAndAConsentOfAnotherCustomerWhenRevokingThenNothingIsRevoked(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $foreignConsentTransfer = $this->tester->haveAgenticCommerceConsent();
        $agenticCommerceConsentCollectionRequestTransfer = (new AgenticCommerceConsentCollectionRequestTransfer())
            ->addAgenticCommerceConsent($this->createRevocation($consentTransfer, $customerTransfer))
            ->addAgenticCommerceConsent($this->createRevocation($foreignConsentTransfer, $customerTransfer));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection($agenticCommerceConsentCollectionRequestTransfer);

        // Assert
        $this->assertCount(2, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(static::SECOND_ITEM_INDEX, $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenANonTransactionalRequestWithAConsentOfAnotherCustomerWhenRevokingThenOnlyTheOwnConsentIsRevoked(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $foreignConsentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([
                $this->createRevocation($foreignConsentTransfer, $customerTransfer),
                $this->createRevocation($consentTransfer, $customerTransfer),
            ], false),
        );

        // Assert
        $this->assertCount(2, $responseTransfer->getAgenticCommerceConsents());
        $this->assertNull($responseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getIdAgenticCommerceConsent());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $responseTransfer->getAgenticCommerceConsents()->offsetGet(1)->getStatus());
        $this->assertSame(static::FIRST_ITEM_INDEX, $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($foreignConsentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenTheSameConsentTwiceInOneRequestWhenRevokingThenItIsRevokedOnceAndTheSecondItemIsReportedAsNotActive(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceConsentCollection(
            $this->createRequest([
                $this->createRevocation($consentTransfer, $customerTransfer),
                $this->createRevocation($consentTransfer, $customerTransfer),
            ], false),
        );

        // Assert
        $this->assertCount(2, $responseTransfer->getAgenticCommerceConsents());
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_CONSENT_NOT_ACTIVE, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(static::SECOND_ITEM_INDEX, $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertCount(1, $this->tester->getConsentEvents($consentTransfer->getUuidOrFail()));
    }

    protected function createRevocation(
        AgenticCommerceConsentTransfer $consentTransfer,
        CustomerTransfer $customerTransfer
    ): AgenticCommerceConsentTransfer {
        return (new AgenticCommerceConsentTransfer())
            ->setUuid($consentTransfer->getUuidOrFail())
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setStatus(AgenticCommerceConfig::CONSENT_STATUS_REVOKED);
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $consentTransfers
     */
    protected function createRequest(array $consentTransfers, bool $isTransactional): AgenticCommerceConsentCollectionRequestTransfer
    {
        $requestTransfer = (new AgenticCommerceConsentCollectionRequestTransfer())->setIsTransactional($isTransactional);

        foreach ($consentTransfers as $consentTransfer) {
            $requestTransfer->addAgenticCommerceConsent($consentTransfer);
        }

        return $requestTransfer;
    }
}
