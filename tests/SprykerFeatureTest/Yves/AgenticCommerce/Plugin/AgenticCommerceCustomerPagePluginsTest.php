<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Plugin;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Plugin\CustomerPage\AgenticCommerceAuthenticationTimeAfterCustomerAuthenticationSuccessPlugin;
use SprykerFeature\Yves\AgenticCommerce\Plugin\CustomerPage\AgenticCommerceAuthorizationCustomerRedirectStrategyPlugin;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Plugin
 * @group AgenticCommerceCustomerPagePluginsTest
 * Add your own group annotations below this line
 */
class AgenticCommerceCustomerPagePluginsTest extends Unit
{
    protected const string SESSION_KEY_AUTHENTICATED_AT = 'agentic_commerce.authenticated_at';

    protected const string SESSION_KEY_PENDING_AUTHORIZATION_REQUEST = 'agentic_commerce.pending_authorization_request';

    protected const string KEY_CUSTOMER_REFERENCE = 'customerReference';

    protected const string KEY_AUTHENTICATED_AT = 'authenticatedAt';

    protected const string CUSTOMER_REFERENCE = 'DE--7';

    protected const string OTHER_CUSTOMER_REFERENCE = 'DE--1';

    protected const int EXPIRED_AUTHORIZATION_REQUEST_LIFETIME_IN_SECONDS = -1;

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceFactory $agenticCommerceFactory;

    protected function _before(): void
    {
        $this->agenticCommerceFactory = $this->tester->createFactory([]);
    }

    public function testGivenNoPendingAuthorizationRequestWhenTheRedirectStrategyIsCheckedThenItDoesNotApply(): void
    {
        // Act
        $isApplicable = $this->createRedirectStrategyPlugin()->isApplicable(new CustomerTransfer());

        // Assert
        $this->assertFalse($isApplicable);
    }

    public function testGivenAPendingAuthorizationRequestThatIsGoneWhenTheRedirectStrategyIsCheckedThenItDoesNotApply(): void
    {
        // Arrange
        $this->tester->setSessionValue(static::SESSION_KEY_PENDING_AUTHORIZATION_REQUEST, 'expired-or-removed');

        // Act
        $isApplicable = $this->createRedirectStrategyPlugin()->isApplicable(new CustomerTransfer());

        // Assert
        $this->assertFalse($isApplicable);
    }

    public function testGivenAnExpiredPendingAuthorizationRequestWhenTheRedirectStrategyIsCheckedThenItDoesNotApply(): void
    {
        // Arrange
        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getConfig' => Stub::make(AgenticCommerceConfig::class, [
                'getAuthorizationRequestLifetimeInSeconds' => static::EXPIRED_AUTHORIZATION_REQUEST_LIFETIME_IN_SECONDS,
            ]),
        ]);
        $authorizationRequestParker = $this->agenticCommerceFactory->createAuthorizationRequestParker();
        $authorizationRequestParker->markPending($authorizationRequestParker->park((new AgenticCommerceAuthorizationResponseTransfer())->setIsValid(true)));

        // Act
        $isApplicable = $this->createRedirectStrategyPlugin()->isApplicable(new CustomerTransfer());

        // Assert
        $this->assertFalse($isApplicable);
    }

    public function testGivenAPendingAuthorizationRequestWhenTheCustomerSignsInThenTheCustomerIsSentToConsentOnce(): void
    {
        // Arrange
        $authorizationRequestParker = $this->agenticCommerceFactory->createAuthorizationRequestParker();
        $authorizationRequestId = $authorizationRequestParker->park((new AgenticCommerceAuthorizationResponseTransfer())->setIsValid(true));
        $authorizationRequestParker->markPending($authorizationRequestId);
        $customerRedirectStrategyPlugin = $this->createRedirectStrategyPlugin();

        // Act
        $isApplicable = $customerRedirectStrategyPlugin->isApplicable(new CustomerTransfer());
        $redirectUrl = $customerRedirectStrategyPlugin->getRedirectUrl(new CustomerTransfer());

        // Assert
        $this->assertTrue($isApplicable);
        $this->assertSame(sprintf('/agentic-commerce/consent?request=%s', $authorizationRequestId), $redirectUrl);
        $this->assertFalse($customerRedirectStrategyPlugin->isApplicable(new CustomerTransfer()));
        $this->assertNotNull($authorizationRequestParker->findParkedAuthorization($authorizationRequestId));
    }

    public function testGivenACompletedSignInWhenTheAuthenticationTimeIsRecordedThenTheSignInIsFreshForTheSignedInCustomerOnly(): void
    {
        // Arrange
        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn((new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE));
        $this->agenticCommerceFactory = $this->tester->createFactory(['getCustomerClient' => $customerClientMock]);
        $freshAuthenticationChecker = $this->agenticCommerceFactory->createFreshAuthenticationChecker();

        // Act
        $this->tester->createPlugin(AgenticCommerceAuthenticationTimeAfterCustomerAuthenticationSuccessPlugin::class, $this->agenticCommerceFactory)->execute();

        // Assert
        $this->assertTrue($freshAuthenticationChecker->isAuthenticationFresh((new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE)));
        $this->assertFalse($freshAuthenticationChecker->isAuthenticationFresh((new CustomerTransfer())->setCustomerReference(static::OTHER_CUSTOMER_REFERENCE)));
    }

    public function testGivenASignInOlderThanTheFreshnessWindowWhenFreshnessIsCheckedThenTheSignInIsNotFresh(): void
    {
        // Arrange
        $this->tester->setSessionValue(static::SESSION_KEY_AUTHENTICATED_AT, [
            static::KEY_CUSTOMER_REFERENCE => static::CUSTOMER_REFERENCE,
            static::KEY_AUTHENTICATED_AT => time() - 601,
        ]);

        // Act
        $isAuthenticationFresh = $this->agenticCommerceFactory->createFreshAuthenticationChecker()->isAuthenticationFresh(
            (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        // Assert
        $this->assertFalse($isAuthenticationFresh);
    }

    protected function createRedirectStrategyPlugin(): AgenticCommerceAuthorizationCustomerRedirectStrategyPlugin
    {
        return $this->tester->createPlugin(AgenticCommerceAuthorizationCustomerRedirectStrategyPlugin::class, $this->agenticCommerceFactory);
    }
}
