<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
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
 * @group CountAgenticCommerceConsentsTest
 * Add your own group annotations below this line
 */
class CountAgenticCommerceConsentsTest extends Unit
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string PAST = '-1 minute';

    protected const int ACTIVE_CONSENT_COUNT = 2;

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenTwoActiveConsentsOfAClientAndOneOfAnotherWhenCountingActiveByClientThenTwoIsReturned(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveAgenticCommerceClient()->getClientIdentifierOrFail();
        $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientIdentifier]);
        $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientIdentifier]);
        $this->tester->haveAgenticCommerceConsent();

        // Act
        $consentCount = $this->tester->getFacade()->countAgenticCommerceConsents($this->createActiveByClientCriteria($clientIdentifier));

        // Assert
        $this->assertSame(static::ACTIVE_CONSENT_COUNT, $consentCount);
    }

    public function testGivenAStoredActiveConsentPastItsExpiryWhenCountingActiveByClientThenItIsNotCounted(): void
    {
        // Arrange
        $clientIdentifier = $this->tester->haveAgenticCommerceClient()->getClientIdentifierOrFail();
        $this->tester->haveAgenticCommerceConsent([
            AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientIdentifier,
            AgenticCommerceConsentTransfer::EXPIRES_AT => (new DateTime(static::PAST))->format(static::DATE_TIME_FORMAT),
        ]);

        // Act
        $consentCount = $this->tester->getFacade()->countAgenticCommerceConsents($this->createActiveByClientCriteria($clientIdentifier));

        // Assert
        $this->assertSame(0, $consentCount);
    }

    protected function createActiveByClientCriteria(string $clientIdentifier): AgenticCommerceConsentCriteriaTransfer
    {
        return (new AgenticCommerceConsentCriteriaTransfer())->setAgenticCommerceConsentConditions(
            (new AgenticCommerceConsentConditionsTransfer())
                ->addClientIdentifier($clientIdentifier)
                ->addStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE),
        );
    }
}
