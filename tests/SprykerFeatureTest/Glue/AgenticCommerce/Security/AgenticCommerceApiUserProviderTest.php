<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceApiUserProviderTest
 * Add your own group annotations below this line
 */
class AgenticCommerceApiUserProviderTest extends Unit
{
    public function testGivenAnIdentifierWhenLoadingTheUserThenNoUserIsLoadedBecauseTheFirewallIsStateless(): void
    {
        // Arrange
        $agenticCommerceApiUserProvider = new AgenticCommerceApiUserProvider();

        // Assert
        $this->expectException(UserNotFoundException::class);

        // Act
        $agenticCommerceApiUserProvider->loadUserByIdentifier('token-identifier');
    }

    public function testGivenAUserOfAnotherKindWhenRefreshingThenTheUserIsRefused(): void
    {
        // Arrange
        $agenticCommerceApiUserProvider = new AgenticCommerceApiUserProvider();

        // Assert
        $this->expectException(UnsupportedUserException::class);

        // Act
        $agenticCommerceApiUserProvider->refreshUser(new InMemoryUser('user', null));
    }

    public function testGivenADelegatedAccessUserWhenRefreshingThenTheSameUserIsReturned(): void
    {
        // Arrange
        $agenticCommerceApiUser = new AgenticCommerceApiUser(
            (new AgenticCommerceAccessTokenTransfer())->setSubject('subject')->setClientId('client')->setTokenIdentifier('token'),
            new AgenticCommerceConsentTransfer(),
            [],
            'mcp',
            'customer-reference',
        );

        // Act
        $refreshedUser = (new AgenticCommerceApiUserProvider())->refreshUser($agenticCommerceApiUser);

        // Assert
        $this->assertSame($agenticCommerceApiUser, $refreshedUser);
    }

    public function testGivenTheDelegatedAccessUserClassAndAnotherClassWhenAskingForSupportThenOnlyTheDelegatedAccessUserClassIsSupported(): void
    {
        // Arrange
        $agenticCommerceApiUserProvider = new AgenticCommerceApiUserProvider();

        // Act
        $supportsDelegatedAccessUser = $agenticCommerceApiUserProvider->supportsClass(AgenticCommerceApiUser::class);
        $supportsOtherUser = $agenticCommerceApiUserProvider->supportsClass(InMemoryUser::class);

        // Assert
        $this->assertTrue($supportsDelegatedAccessUser);
        $this->assertFalse($supportsOtherUser);
    }
}
