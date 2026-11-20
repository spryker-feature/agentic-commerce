<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Authorization;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Authorization
 * @group AuthorizationRequestParkerTest
 * Add your own group annotations below this line
 */
class AuthorizationRequestParkerTest extends Unit
{
    protected const int MAX_PARKED_AUTHORIZATION_REQUESTS = 5;

    protected const int EXPIRED_AUTHORIZATION_REQUEST_LIFETIME_IN_SECONDS = -1;

    protected AgenticCommerceYvesTester $tester;

    public function testGivenTheMaximumOfParkedRequestsWhenAnotherRequestIsParkedThenOnlyTheOldestIsDropped(): void
    {
        // Arrange
        $authorizationRequestParker = $this->tester->createFactory([])->createAuthorizationRequestParker();
        $authorizationRequestIds = [];

        for ($index = 0; $index < static::MAX_PARKED_AUTHORIZATION_REQUESTS; $index++) {
            $authorizationRequestIds[] = $authorizationRequestParker->park((new AgenticCommerceAuthorizationResponseTransfer())->setIsValid(true));
        }

        // Act
        $newestAuthorizationRequestId = $authorizationRequestParker->park((new AgenticCommerceAuthorizationResponseTransfer())->setIsValid(true));

        // Assert
        $this->assertNull($authorizationRequestParker->findParkedAuthorization($authorizationRequestIds[0]));

        foreach (array_slice($authorizationRequestIds, 1) as $authorizationRequestId) {
            $this->assertNotNull($authorizationRequestParker->findParkedAuthorization($authorizationRequestId));
        }

        $this->assertNotNull($authorizationRequestParker->findParkedAuthorization($newestAuthorizationRequestId));
    }

    public function testGivenARequestParkedLongerThanItsLifetimeWhenItIsLookedUpThenNeitherTheRequestNorThePendingMarkerIsFound(): void
    {
        // Arrange
        $authorizationRequestParker = $this->tester->createFactory([
            'getConfig' => Stub::make(AgenticCommerceConfig::class, [
                'getAuthorizationRequestLifetimeInSeconds' => static::EXPIRED_AUTHORIZATION_REQUEST_LIFETIME_IN_SECONDS,
            ]),
        ])->createAuthorizationRequestParker();
        $authorizationRequestId = $authorizationRequestParker->park((new AgenticCommerceAuthorizationResponseTransfer())->setIsValid(true));
        $authorizationRequestParker->markPending($authorizationRequestId);

        // Act
        $agenticCommerceAuthorizationResponseTransfer = $authorizationRequestParker->findParkedAuthorization($authorizationRequestId);
        $pendingAuthorizationRequestId = $authorizationRequestParker->findPendingAuthorizationRequestId();

        // Assert
        $this->assertNull($agenticCommerceAuthorizationResponseTransfer);
        $this->assertNull($pendingAuthorizationRequestId);
    }

    public function testGivenAPendingRequestWhenItIsRemovedThenThePendingMarkerIsCleared(): void
    {
        // Arrange
        $authorizationRequestParker = $this->tester->createFactory([])->createAuthorizationRequestParker();
        $authorizationRequestId = $authorizationRequestParker->park((new AgenticCommerceAuthorizationResponseTransfer())->setIsValid(true));
        $authorizationRequestParker->markPending($authorizationRequestId);

        // Act
        $authorizationRequestParker->remove($authorizationRequestId);

        // Assert
        $this->assertNull($authorizationRequestParker->findPendingAuthorizationRequestId());
    }
}
