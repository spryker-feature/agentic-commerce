<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Consent;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Yves\AgenticCommerce\Consent\ConsentCollectionRequestBuilderInterface;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Consent
 * @group ConsentCollectionRequestBuilderTest
 * Add your own group annotations below this line
 */
class ConsentCollectionRequestBuilderTest extends Unit
{
    protected const string STORE_NAME = 'DE';

    protected const string CUSTOMER_REFERENCE = 'DE--7';

    protected const string CLIENT_ID = 'procure-assist';

    protected const string PRINCIPAL_TYPE_COMPANY_USER = 'company_user';

    protected const string COMPANY_USER_UUID = 'company-user-uuid';

    protected const string SCOPE_CART_READ = 'cart:read';

    protected const string SCOPE_ORDER_PLACE = 'order:place';

    protected const string CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT = 'order_total_limit';

    protected const string INPUT_KIND_MONEY = 'money';

    protected const string CURRENCY_CODE = 'EUR';

    protected const int LIMIT_IN_CENTS = 5000;

    protected const string IP_ADDRESS = '203.0.113.7';

    protected AgenticCommerceYvesTester $tester;

    public function testGivenSubmittedConsentDataWhenTheRequestIsBuiltThenItIsTransactionalForTheCurrentStoreAndCarriesTheSubmittedChoices(): void
    {
        // Arrange
        $principal = $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID);
        $request = $this->tester->createRequest();
        $request->server->set('REMOTE_ADDR', static::IP_ADDRESS);
        $consentData = [
            'principal' => $principal,
            'scopes' => [1 => static::SCOPE_CART_READ, 3 => static::SCOPE_ORDER_PLACE],
            'durationInDays' => '7',
            'constraints' => [static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => static::LIMIT_IN_CENTS],
        ];
        $formOptions = [
            'constraintFieldsByPrincipal' => [
                $principal => [
                    static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => (new AgenticCommerceConstraintFieldTransfer())
                        ->setType(static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT)
                        ->setInputKind(static::INPUT_KIND_MONEY)
                        ->setCurrencyCode(static::CURRENCY_CODE),
                ],
            ],
        ];

        // Act
        $agenticCommerceConsentCollectionRequestTransfer = $this->createConsentCollectionRequestBuilder()->build(
            $request,
            $consentData,
            $formOptions,
            $this->createAuthorizationResponse(),
            (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        // Assert
        $agenticCommerceConsentTransfer = $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $this->assertTrue($agenticCommerceConsentCollectionRequestTransfer->getIsTransactional());
        $this->assertSame(static::CLIENT_ID, $agenticCommerceConsentCollectionRequestTransfer->getAuthorizationRequestOrFail()->getClientId());
        $this->assertSame(static::IP_ADDRESS, $agenticCommerceConsentCollectionRequestTransfer->getRequestClientDataOrFail()->getIpAddress());
        $this->assertSame(static::CUSTOMER_REFERENCE, $agenticCommerceConsentTransfer->getCustomerReference());
        $this->assertSame(static::PRINCIPAL_TYPE_COMPANY_USER, $agenticCommerceConsentTransfer->getPrincipalOrFail()->getPrincipalType());
        $this->assertSame(static::COMPANY_USER_UUID, $agenticCommerceConsentTransfer->getPrincipalOrFail()->getPrincipalReference());
        $this->assertSame(static::STORE_NAME, $agenticCommerceConsentTransfer->getStoreName());
        $this->assertSame([static::SCOPE_CART_READ, static::SCOPE_ORDER_PLACE], $agenticCommerceConsentTransfer->getGrantedScopes());
        $this->assertSame(7, $agenticCommerceConsentTransfer->getDurationInDays());
        $this->assertSame(
            ['amount' => static::LIMIT_IN_CENTS, 'currency' => static::CURRENCY_CODE],
            $agenticCommerceConsentTransfer->getConstraints()->offsetGet(0)->getValue(),
        );
    }

    public function testGivenNoConstraintFieldsForTheSubmittedPrincipalWhenTheRequestIsBuiltThenTheConsentCarriesNoConstraints(): void
    {
        // Arrange
        $consentData = [
            'principal' => $this->tester->createPrincipalValue(static::PRINCIPAL_TYPE_COMPANY_USER, static::COMPANY_USER_UUID),
            'scopes' => [static::SCOPE_CART_READ],
            'durationInDays' => '7',
            'constraints' => [static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => static::LIMIT_IN_CENTS],
        ];

        // Act
        $agenticCommerceConsentCollectionRequestTransfer = $this->createConsentCollectionRequestBuilder()->build(
            $this->tester->createRequest(),
            $consentData,
            ['constraintFieldsByPrincipal' => []],
            $this->createAuthorizationResponse(),
            (new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        // Assert
        $this->assertCount(0, $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->getConstraints());
    }

    protected function createConsentCollectionRequestBuilder(): ConsentCollectionRequestBuilderInterface
    {
        $storeClientMock = $this->createMock(StoreClientInterface::class);
        $storeClientMock->method('getCurrentStore')->willReturn((new StoreTransfer())->setName(static::STORE_NAME));

        return $this->tester->createFactory([
            'getStoreClient' => $storeClientMock,
            'getAgenticCommerceClient' => $this->createMock(AgenticCommerceClientInterface::class),
            'getCurrencyClient' => $this->createMock(CurrencyClientInterface::class),
        ])->createConsentCollectionRequestBuilder();
    }

    protected function createAuthorizationResponse(): AgenticCommerceAuthorizationResponseTransfer
    {
        return (new AgenticCommerceAuthorizationResponseTransfer())
            ->setAuthorizationRequest((new AgenticCommerceAuthorizationRequestTransfer())->setClientId(static::CLIENT_ID));
    }
}
