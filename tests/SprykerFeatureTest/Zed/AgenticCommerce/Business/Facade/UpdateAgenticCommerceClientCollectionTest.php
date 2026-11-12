<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use RuntimeException;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group UpdateAgenticCommerceClientCollectionTest
 * Add your own group annotations below this line
 */
class UpdateAgenticCommerceClientCollectionTest extends Unit
{
    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_NOT_FOUND = 'agentic_commerce.client.validation.not_found';

    protected const string ERROR_STATUS_UNSUPPORTED = 'agentic_commerce.client.validation.status_unsupported';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenAClientWithTwoActiveConsentsWhenBlockedThenBothConsentsAreRevokedAsSystemClientBlocked(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $firstConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);
        $secondConsentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(2, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());

        foreach ([$firstConsentTransfer, $secondConsentTransfer] as $consentTransfer) {
            $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
            $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $storedConsentTransfer->getStatus());
            $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED, $storedConsentTransfer->getRevokedBy());
        }
    }

    public function testGivenConsentsOfTwoClientsWhenOneClientIsBlockedThenTheOtherClientsConsentStaysActive(): void
    {
        // Arrange
        $blockedClientTransfer = $this->tester->haveAgenticCommerceClient();
        $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $blockedClientTransfer->getClientIdentifierOrFail()]);
        $untouchedConsentTransfer = $this->tester->haveAgenticCommerceConsent();

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($blockedClientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Assert
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($untouchedConsentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenABlockedClientWhenUnblockedThenItIsActiveAndItsRevokedConsentsStayRevoked(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);
        $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_ACTIVE));

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenAnActiveClientWhenSetToActiveAgainThenNoErrorIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_ACTIVE));

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenABlockedClientWithAnActiveConsentWhenBlockedAgainThenTheConsentIsRevoked(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_BLOCKED]);
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Assert
        $this->assertCount(1, $responseTransfer->getAgenticCommerceConsents());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $storedConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_CLIENT_BLOCKED, $storedConsentTransfer->getRevokedBy());
    }

    public function testGivenAPostRevokePluginThatThrowsWhenBlockingAClientThenTheExceptionReachesTheCaller(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);
        $postRevokePluginMock = $this->createMock(AgenticCommerceConsentPostRevokePluginInterface::class);
        $postRevokePluginMock->method('postRevoke')->willThrowException(new RuntimeException('Post-revoke plugin failed.'));
        $this->tester->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, [$postRevokePluginMock]);

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Post-revoke plugin failed.');

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));
    }

    public function testGivenAnEmptyClientIdentifierWhenBlockedThenAClientIdentifierRequiredErrorIsReturned(): void
    {
        // Arrange
        $agenticCommerceClientCollectionRequestTransfer = $this->createAgenticCommerceClientCollectionRequestTransfer('', AgenticCommerceConfig::CLIENT_STATUS_BLOCKED);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_CLIENT_IDENTIFIER_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenAnUnknownClientWhenBlockedThenANotFoundErrorIsReturned(): void
    {
        // Arrange
        $agenticCommerceClientCollectionRequestTransfer = $this->createAgenticCommerceClientCollectionRequestTransfer('unknown-delegated-access-client', AgenticCommerceConfig::CLIENT_STATUS_BLOCKED);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_CLIENT_NOT_FOUND, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenAnUnsupportedStatusWhenUpdatingThenAStatusUnsupportedErrorIsReturnedAndTheClientStaysActive(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_ACTIVE]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($this->createAgenticCommerceClientCollectionRequestTransfer($clientTransfer->getClientIdentifierOrFail(), 'suspended'));

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_STATUS_UNSUPPORTED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenATransactionalRequestWithAnUnknownClientWhenBlockingThenTheValidClientStaysActive(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_ACTIVE]);
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);
        $agenticCommerceClientCollectionRequestTransfer = (new AgenticCommerceClientCollectionRequestTransfer())
            ->setIsTransactional(true)
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED))
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer('unknown-delegated-access-client', AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame('1', $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(static::ERROR_CLIENT_NOT_FOUND, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertCount(2, $responseTransfer->getAgenticCommerceClients());
        $this->assertCount(0, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenARequestWithoutTransactionalFlagAndAnUnknownClientWhenBlockingThenTheValidClientStaysActive(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_ACTIVE]);
        $agenticCommerceClientCollectionRequestTransfer = (new AgenticCommerceClientCollectionRequestTransfer())
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED))
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer('unknown-delegated-access-client', AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertCount(2, $responseTransfer->getAgenticCommerceClients());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenANonTransactionalRequestWithAnUnknownClientWhenBlockingThenTheValidClientIsBlockedAndItsConsentRevoked(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_ACTIVE]);
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);
        $agenticCommerceClientCollectionRequestTransfer = (new AgenticCommerceClientCollectionRequestTransfer())
            ->setIsTransactional(false)
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer('unknown-delegated-access-client', AgenticCommerceConfig::CLIENT_STATUS_BLOCKED))
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_BLOCKED));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertCount(2, $responseTransfer->getAgenticCommerceClients());
        $this->assertSame('unknown-delegated-access-client', $responseTransfer->getAgenticCommerceClients()->offsetGet(0)->getClientIdentifier());
        $this->assertSame($clientTransfer->getClientIdentifierOrFail(), $responseTransfer->getAgenticCommerceClients()->offsetGet(1)->getClientIdentifier());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $responseTransfer->getAgenticCommerceClients()->offsetGet(1)->getStatus());
        $this->assertCount(1, $responseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenANonTransactionalRequestWithTwoInvalidItemsWhenUpdatingThenEachErrorCarriesItsItemIndex(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $agenticCommerceClientCollectionRequestTransfer = (new AgenticCommerceClientCollectionRequestTransfer())
            ->setIsTransactional(false)
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer($clientTransfer->getClientIdentifierOrFail(), AgenticCommerceConfig::CLIENT_STATUS_ACTIVE))
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer('', AgenticCommerceConfig::CLIENT_STATUS_BLOCKED))
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer($clientTransfer->getClientIdentifierOrFail(), 'suspended'));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);

        // Assert
        $this->assertCount(2, $responseTransfer->getErrors());
        $this->assertSame('1', $responseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(static::ERROR_CLIENT_IDENTIFIER_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame('2', $responseTransfer->getErrors()->offsetGet(1)->getEntityIdentifier());
        $this->assertSame(static::ERROR_STATUS_UNSUPPORTED, $responseTransfer->getErrors()->offsetGet(1)->getMessage());
        $this->assertCount(3, $responseTransfer->getAgenticCommerceClients());
    }

    protected function createAgenticCommerceClientCollectionRequestTransfer(
        string $clientIdentifier,
        string $status
    ): AgenticCommerceClientCollectionRequestTransfer {
        return (new AgenticCommerceClientCollectionRequestTransfer())
            ->setIsTransactional(true)
            ->addAgenticCommerceClient($this->createAgenticCommerceClientTransfer($clientIdentifier, $status));
    }

    protected function createAgenticCommerceClientTransfer(string $clientIdentifier, string $status): AgenticCommerceClientTransfer
    {
        return (new AgenticCommerceClientTransfer())
            ->setClientIdentifier($clientIdentifier)
            ->setStatus($status);
    }
}
