<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakePrincipalResolverPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group ValidateAgenticCommerceConsentUsageTest
 * Add your own group annotations below this line
 */
class ValidateAgenticCommerceConsentUsageTest extends Unit
{
    protected const string ERROR_CONSENT_NOT_USABLE = 'agentic_commerce.consent.usage.not_usable';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string PAST = '-1 minute';

    protected const string USER_IDENTIFIER = '{}';

    protected const string UNKNOWN_CONSENT_UUID = '00000000-0000-0000-0000-000000000000';

    protected const string OTHER_CLIENT_IDENTIFIER = 'other-client';

    protected const string OTHER_CUSTOMER_REFERENCE = 'DE--other';

    protected const string OTHER_STORE_NAME = 'AT';

    protected const string COMPANY_USER_REFERENCE = 'company-user-deactivated';

    protected const int SECONDS_WITHIN_THE_WRITE_INTERVAL = 600;

    protected const int SECONDS_BEYOND_THE_WRITE_INTERVAL = 7200;

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenAnActiveConsentAndAMatchingCallWhenUsageIsValidatedThenItIsValidAndLastUsedAtIsWritten(): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal();

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertTrue($usageResponseTransfer->getIsValid());
        $this->assertCount(0, $usageResponseTransfer->getErrors());
        $this->assertSame($consentTransfer->getUuid(), $usageResponseTransfer->getAgenticCommerceConsentOrFail()->getUuid());
        $this->assertNotNull($this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAt());
    }

    public function testGivenAConsentUsedTwiceWithinAnHourWhenUsageIsValidatedThenLastUsedAtIsWrittenOnlyOnce(): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal();
        $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));
        $firstLastUsedAt = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAtOrFail();
        $this->tester->moveLastUsedAtBackBySeconds($consentTransfer, static::SECONDS_WITHIN_THE_WRITE_INTERVAL);

        // Act
        $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertSame(
            $this->tester->shiftTimestampBySeconds($firstLastUsedAt, -static::SECONDS_WITHIN_THE_WRITE_INTERVAL),
            $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAtOrFail(),
        );
    }

    public function testGivenAConsentLastUsedMoreThanAnHourAgoWhenUsageIsValidatedThenLastUsedAtIsWrittenAgain(): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal();
        $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));
        $this->tester->moveLastUsedAtBackBySeconds($consentTransfer, static::SECONDS_BEYOND_THE_WRITE_INTERVAL);
        $staleLastUsedAt = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAtOrFail();

        // Act
        $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertNotSame($staleLastUsedAt, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getLastUsedAtOrFail());
    }

    public function testGivenAConsentPastItsExpiryWhenUsageIsValidatedThenANotUsableErrorIsReturnedAndTheConsentIsStoredAsExpiredButNotRevoked(): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal([
            AgenticCommerceConsentTransfer::EXPIRES_AT => (new DateTime(static::PAST))->format(static::DATE_TIME_FORMAT),
        ]);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_CONSENT_NOT_USABLE, $usageResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_EXPIRED, $storedConsentTransfer->getStatus());
        $this->assertNull($storedConsentTransfer->getRevokedBy());
        $this->assertNull($storedConsentTransfer->getLastUsedAt());
    }

    public function testGivenACallFromAnotherStoreWhenUsageIsValidatedThenItIsInvalidWithoutAConsentAndTheConsentStaysActiveAndUnused(): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal();
        $usageRequestTransfer = $this->tester->createUsageRequest($consentTransfer)->setStoreName(static::OTHER_STORE_NAME);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($usageRequestTransfer);

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertNull($usageResponseTransfer->getAgenticCommerceConsent());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $storedConsentTransfer->getStatus());
        $this->assertNull($storedConsentTransfer->getLastUsedAt());
        $this->assertSame([], $this->tester->getConsentEvents($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAConsentWhosePrincipalBecameInactiveWhenUsageIsValidatedThenANotUsableErrorIsReturnedAndTheConsentIsRevokedAsPrincipalInactive(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [$consentTransfer->getPrincipalReferenceOrFail() => static::USER_IDENTIFIER], false),
        ]);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_CONSENT_NOT_USABLE, $usageResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $storedConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE, $storedConsentTransfer->getRevokedBy());
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_REVOKED], $this->tester->getConsentEventNames($consentTransfer->getUuidOrFail()));
    }

    public function testGivenAConsentWithoutAResolverForItsPrincipalTypeWhenUsageIsValidatedThenANotUsableErrorIsReturnedAndTheConsentStaysActive(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->setPrincipalResolverPlugins([]);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_CONSENT_NOT_USABLE, $usageResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertNull($usageResponseTransfer->getAgenticCommerceConsent());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $storedConsentTransfer->getStatus());
        $this->assertNull($storedConsentTransfer->getRevokedBy());
        $this->assertSame([], $this->tester->getConsentEvents($consentTransfer->getUuidOrFail()));
    }

    public function testGivenACompanyUserConsentWhoseCompanyUserBecameInactiveWhileTheCustomerIsActiveWhenUsageIsValidatedThenItIsRevokedAndNoConsentIsReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::COMPANY_USER_REFERENCE,
        ]);
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, [static::COMPANY_USER_REFERENCE => static::USER_IDENTIFIER], false),
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [$customerTransfer->getCustomerReferenceOrFail() => static::USER_IDENTIFIER]),
        ]);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertNull($usageResponseTransfer->getAgenticCommerceConsent());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $storedConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE, $storedConsentTransfer->getRevokedBy());
    }

    public function testGivenAnInactivePrincipalAndACallOfAnotherClientWhenUsageIsValidatedThenItIsInvalidAndTheConsentIsNotRevoked(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [$consentTransfer->getPrincipalReferenceOrFail() => static::USER_IDENTIFIER], false),
        ]);
        $usageRequestTransfer = $this->tester->createUsageRequest($consentTransfer)->setClientIdentifier(static::OTHER_CLIENT_IDENTIFIER);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($usageRequestTransfer);

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail())->getStatus());
    }

    public function testGivenARevokedConsentWhenUsageIsValidatedThenOneGenericErrorIsReturned(): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal([AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($this->tester->createUsageRequest($consentTransfer));

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertCount(1, $usageResponseTransfer->getErrors());
        $this->assertSame(static::ERROR_CONSENT_NOT_USABLE, $usageResponseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    /**
     * @dataProvider provideMismatchingCalls
     */
    public function testGivenACallThatDoesNotMatchTheConsentWhenUsageIsValidatedThenOneGenericErrorIsReturned(string $property, string $value): void
    {
        // Arrange
        $consentTransfer = $this->haveConsentWithActivePrincipal();
        $usageRequestTransfer = $this->tester->createUsageRequest($consentTransfer)->fromArray([$property => $value]);

        // Act
        $usageResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceConsentUsage($usageRequestTransfer);

        // Assert
        $this->assertFalse($usageResponseTransfer->getIsValid());
        $this->assertCount(1, $usageResponseTransfer->getErrors());
        $this->assertSame(static::ERROR_CONSENT_NOT_USABLE, $usageResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertNull($usageResponseTransfer->getAgenticCommerceConsent());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideMismatchingCalls(): array
    {
        return [
            'unknown consent' => [AgenticCommerceConsentUsageRequestTransfer::CONSENT_UUID, static::UNKNOWN_CONSENT_UUID],
            'empty consent' => [AgenticCommerceConsentUsageRequestTransfer::CONSENT_UUID, ''],
            'other client' => [AgenticCommerceConsentUsageRequestTransfer::CLIENT_IDENTIFIER, static::OTHER_CLIENT_IDENTIFIER],
            'other customer' => [AgenticCommerceConsentUsageRequestTransfer::CUSTOMER_REFERENCE, static::OTHER_CUSTOMER_REFERENCE],
            'other store' => [AgenticCommerceConsentUsageRequestTransfer::STORE_NAME, static::OTHER_STORE_NAME],
        ];
    }

    /**
     * @param array<string, mixed> $seed
     */
    protected function haveConsentWithActivePrincipal(array $seed = []): AgenticCommerceConsentTransfer
    {
        $consentTransfer = $this->tester->haveAgenticCommerceConsent($seed);
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [$consentTransfer->getPrincipalReferenceOrFail() => static::USER_IDENTIFIER]),
        ]);

        return $consentTransfer;
    }
}
