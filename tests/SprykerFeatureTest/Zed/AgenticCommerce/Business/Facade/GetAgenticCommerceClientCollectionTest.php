<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
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
 * @group GetAgenticCommerceClientCollectionTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceClientCollectionTest extends Unit
{
    protected const string CLIENT_NAME = 'Shopping Assistant';

    protected const string UNKNOWN_REGISTRATION_TYPE = 'dynamic';

    protected const int PAGE_SIZE = 1;

    protected const int FIRST_PAGE = 1;

    protected const int CLIENT_COUNT = 2;

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenAClientWhenFilteringByItsIdentifierThenOnlyThatClientIsReturnedWithItsName(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::NAME => static::CLIENT_NAME]);
        $this->tester->haveAgenticCommerceClient();

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceClientCollection(
            $this->createCriteria((new AgenticCommerceClientConditionsTransfer())->addClientIdentifier($clientTransfer->getClientIdentifierOrFail())),
        );

        // Assert
        $this->assertCount(1, $collectionTransfer->getAgenticCommerceClients());
        $foundClientTransfer = $collectionTransfer->getAgenticCommerceClients()->offsetGet(0);
        $this->assertSame($clientTransfer->getClientIdentifier(), $foundClientTransfer->getClientIdentifier());
        $this->assertSame(static::CLIENT_NAME, $foundClientTransfer->getName());
    }

    public function testGivenAnActiveAndABlockedClientWhenFilteringByStatusBlockedThenOnlyTheBlockedClientIsReturned(): void
    {
        // Arrange
        $activeClientTransfer = $this->tester->haveAgenticCommerceClient();
        $blockedClientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::STATUS => AgenticCommerceConfig::CLIENT_STATUS_BLOCKED]);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceClientCollection(
            $this->createCriteria(
                (new AgenticCommerceClientConditionsTransfer())
                    ->addClientIdentifier($activeClientTransfer->getClientIdentifierOrFail())
                    ->addClientIdentifier($blockedClientTransfer->getClientIdentifierOrFail())
                    ->addStatus(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED),
            ),
        );

        // Assert
        $this->assertCount(1, $collectionTransfer->getAgenticCommerceClients());
        $this->assertSame($blockedClientTransfer->getClientIdentifier(), $collectionTransfer->getAgenticCommerceClients()->offsetGet(0)->getClientIdentifier());
    }

    public function testGivenAPreRegisteredClientWhenFilteringByAnotherRegistrationTypeThenNoClientIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceClientCollection(
            $this->createCriteria(
                (new AgenticCommerceClientConditionsTransfer())
                    ->addClientIdentifier($clientTransfer->getClientIdentifierOrFail())
                    ->addRegistrationType(static::UNKNOWN_REGISTRATION_TYPE),
            ),
        );

        // Assert
        $this->assertCount(0, $collectionTransfer->getAgenticCommerceClients());
    }

    public function testGivenTwoClientsAndAPageSizeOfOneWhenReadingTheFirstPageThenOneClientAndTheTotalCountAreReturned(): void
    {
        // Arrange
        $conditionsTransfer = (new AgenticCommerceClientConditionsTransfer())
            ->addClientIdentifier($this->tester->haveAgenticCommerceClient()->getClientIdentifierOrFail())
            ->addClientIdentifier($this->tester->haveAgenticCommerceClient()->getClientIdentifierOrFail());

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceClientCollection(
            $this->createCriteria($conditionsTransfer)->setPagination(
                (new PaginationTransfer())->setPage(static::FIRST_PAGE)->setMaxPerPage(static::PAGE_SIZE),
            ),
        );

        // Assert
        $this->assertCount(static::PAGE_SIZE, $collectionTransfer->getAgenticCommerceClients());
        $this->assertSame(static::CLIENT_COUNT, $collectionTransfer->getPaginationOrFail()->getNbResults());
    }

    protected function createCriteria(AgenticCommerceClientConditionsTransfer $conditionsTransfer): AgenticCommerceClientCriteriaTransfer
    {
        return (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions($conditionsTransfer);
    }
}
