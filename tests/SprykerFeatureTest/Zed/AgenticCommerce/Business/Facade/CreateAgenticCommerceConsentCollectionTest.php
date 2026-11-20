<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\OauthAuthorizationCodeCollectionResponseTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\OauthCodeFlow\Business\OauthCodeFlowFacadeInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceScopeFilterPluginInterface;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakePrincipalResolverPlugin;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakeScopeFilterPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group CreateAgenticCommerceConsentCollectionTest
 * Add your own group annotations below this line
 */
class CreateAgenticCommerceConsentCollectionTest extends Unit
{
    protected const string ERROR_AUTHORIZATION_REQUEST_INVALID = 'agentic_commerce.consent.validation.authorization_request_invalid';

    protected const string ERROR_SCOPE_REQUIRED = 'agentic_commerce.consent.validation.scope_required';

    protected const string ERROR_SCOPE_NOT_GRANTABLE = 'agentic_commerce.consent.validation.scope_not_grantable';

    protected const string ERROR_DURATION_UNSUPPORTED = 'agentic_commerce.consent.validation.duration_unsupported';

    protected const string ERROR_CUSTOMER_NOT_FOUND = 'agentic_commerce.consent.validation.customer_not_found';

    protected const string ERROR_STORE_NOT_FOUND = 'agentic_commerce.consent.validation.store_not_found';

    protected const string ERROR_PRINCIPAL_NOT_FOUND = 'agentic_commerce.consent.validation.principal_not_found';

    protected const string ERROR_AUTHORIZATION_CODE_NOT_ISSUED = 'agentic_commerce.consent.authorization_code_not_issued';

    protected const string UNKNOWN_PRINCIPAL_REFERENCE = 'unknown-principal-reference';

    protected const string COMPANY_USER_REFERENCE = 'company-user-uuid-1';

    protected const string OTHER_COMPANY_USER_REFERENCE = 'company-user-uuid-2';

    protected const string UNKNOWN_STORE_NAME = 'NOWHERE';

    protected const string UNKNOWN_CUSTOMER_REFERENCE = 'DE--unknown-customer';

    protected const string FOREIGN_RESOURCE_URI = 'https://other.shop.example/api';

    protected const string FORGED_DISPLAY_NAME = 'Forged display name';

    protected const int UNSUPPORTED_DURATION_IN_DAYS = 14;

    protected const int EXPIRY_TOLERANCE_IN_SECONDS = 60;

    /**
     * @uses \Spryker\Zed\Store\StoreDependencyProvider::STORE_CURRENT
     */
    protected const string STORE_CURRENT = 'STORE_CURRENT';

    /**
     * @uses \Spryker\Zed\Store\StoreDependencyProvider::CURRENT_STORE_PROVIDED_FLAG
     */
    protected const string CURRENT_STORE_PROVIDED_FLAG = 'CURRENT_STORE_PROVIDED_FLAG';

    protected AgenticCommerceBusinessTester $tester;

    protected AgenticCommerceClientTransfer $clientTransfer;

    protected CustomerTransfer $customerTransfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setCurrentStore(AgenticCommerceHelper::STORE_NAME);
        $this->tester->setSupportedScopes([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B]);
        $this->clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SCOPE_CEILING => [AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B],
        ]);
        $this->customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [
                $this->customerTransfer->getCustomerReferenceOrFail() => $this->buildCustomerUserIdentifier(),
            ]),
        ]);
    }

    public function testGivenACustomerConsentForAScopeTheClientRequestedWhenCreatingThenTheConsentIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $consentTransfer = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $storedConsentTransfer->getStatus());
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $storedConsentTransfer->getGrantedScopes());
        $this->assertSame(AgenticCommerceHelper::STORE_NAME, $storedConsentTransfer->getStoreName());
        $this->assertSame(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, $storedConsentTransfer->getPrincipalType());
        $this->assertSame($this->customerTransfer->getCustomerReference(), $storedConsentTransfer->getPrincipalReference());
        $this->assertSame($this->customerTransfer->getIdCustomer(), $storedConsentTransfer->getIdCustomer());
        $this->assertEqualsWithDelta(
            (new DateTime(sprintf('+%d days', AgenticCommerceHelper::CONSENT_DURATION_IN_DAYS)))->getTimestamp(),
            (new DateTime($storedConsentTransfer->getExpiresAtOrFail()))->getTimestamp(),
            static::EXPIRY_TOLERANCE_IN_SECONDS,
        );
    }

    public function testGivenACustomerConsentForAScopeTheClientRequestedWhenCreatingThenTheIssuedAuthorizationCodeIsBoundToTheConsent(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $consentTransfer = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $payload = $this->tester->decryptAuthorizationCode($consentTransfer->getAuthorizationCodeOrFail());
        $this->assertSame($this->clientTransfer->getClientIdentifier(), $payload['client_id']);
        $this->assertSame(AgenticCommerceHelper::REDIRECT_URI, $payload['redirect_uri']);
        $this->assertSame([AgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER, AgenticCommerceHelper::TEST_SCOPE_A], $payload['scopes']);
        $this->assertSame(AgenticCommerceHelper::CODE_CHALLENGE, $payload['code_challenge']);
        $this->assertSame(AgenticCommerceConfig::CODE_CHALLENGE_METHOD_S256, $payload['code_challenge_method']);
        $this->assertSame(AgenticCommerceConfig::REQUEST_APPLICATION_AGENTIC_COMMERCE, $payload['applicationContext']);
        $this->assertSame(AgenticCommerceHelper::RESOURCE_URI, $payload[AgenticCommerceConfig::AUTHORIZATION_CODE_PAYLOAD_KEY_RESOURCE]);
        $this->assertSame($consentTransfer->getUuid(), $payload[AgenticCommerceConfig::CLAIM_CONSENT_UUID]);
        $this->assertSame($this->buildCustomerUserIdentifier(), $payload['user_id']);
    }

    public function testGivenACompanyUserPrincipalWhenCreatingThenTheCodeCarriesBothIdentityScopesAndTheCompanyUserIdentifier(): void
    {
        // Arrange
        $companyUserIdentifier = $this->buildCompanyUserIdentifier();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, [static::COMPANY_USER_REFERENCE => $companyUserIdentifier]),
        ]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest(
                [AgenticCommerceHelper::TEST_SCOPE_A],
                AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
                static::COMPANY_USER_REFERENCE,
            ),
        );

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $consentTransfer = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, $storedConsentTransfer->getPrincipalType());
        $this->assertSame(static::COMPANY_USER_REFERENCE, $storedConsentTransfer->getPrincipalReference());

        $payload = $this->tester->decryptAuthorizationCode($consentTransfer->getAuthorizationCodeOrFail());
        $this->assertSame(
            [AgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER, AgenticCommerceConfig::SCOPE_IDENTITY_COMPANY_USER, AgenticCommerceHelper::TEST_SCOPE_A],
            $payload['scopes'],
        );
        $this->assertSame($companyUserIdentifier, $payload['user_id']);
    }

    public function testGivenAnAuthorizationRequestForAForeignResourceWhenCreatingThenAnAuthorizationRequestInvalidErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);
        $consentCollectionRequestTransfer->getAuthorizationRequestOrFail()->setResource(static::FOREIGN_RESOURCE_URI);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_AUTHORIZATION_REQUEST_INVALID], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    /**
     * @dataProvider provideInvalidConsents
     *
     * @param array<string, mixed> $override
     */
    public function testGivenAConsentWithAMissingOrUngrantableScopeAnUnsupportedDurationOrAnUnknownStoreWhenCreatingThenTheMatchingValidationErrorIsReturnedForItsIndexAndNothingIsPersisted(
        array $override,
        string $expectedErrorMessage
    ): void {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);
        $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->fromArray($override);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertContains($expectedErrorMessage, $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame('0', $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidConsents(): array
    {
        return [
            'no scope' => [[AgenticCommerceConsentTransfer::GRANTED_SCOPES => []], static::ERROR_SCOPE_REQUIRED],
            'scope the client did not request' => [[AgenticCommerceConsentTransfer::GRANTED_SCOPES => [AgenticCommerceHelper::TEST_SCOPE_B]], static::ERROR_SCOPE_NOT_GRANTABLE],
            'unsupported duration' => [[AgenticCommerceConsentTransfer::DURATION_IN_DAYS => static::UNSUPPORTED_DURATION_IN_DAYS], static::ERROR_DURATION_UNSUPPORTED],
            'unknown store' => [[AgenticCommerceConsentTransfer::STORE_NAME => static::UNKNOWN_STORE_NAME], static::ERROR_STORE_NOT_FOUND],
        ];
    }

    public function testGivenAClientWithAnEmptyScopeCeilingWhenCreatingAConsentWithoutScopesThenNoConsentAndNoAuthorizationCodeIsIssued(): void
    {
        // Arrange
        $this->clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => []]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_SCOPE_REQUIRED], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertCount(0, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
        $this->assertNull($consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->getAuthorizationCode());
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAnUnknownCustomerWhenCreatingThenACustomerNotFoundErrorIsReturned(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);
        $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->setCustomerReference(static::UNKNOWN_CUSTOMER_REFERENCE);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_CUSTOMER_NOT_FOUND], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertCount(0, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
    }

    public function testGivenAScopeFilterRemovingAScopeWhenCreatingAConsentWithThatScopeThenAScopeNotGrantableErrorIsReturned(): void
    {
        // Arrange
        $this->tester->setScopeFilterPlugins([new FakeScopeFilterPlugin([AgenticCommerceHelper::TEST_SCOPE_B])]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_SCOPE_NOT_GRANTABLE], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAnUnknownPrincipalReferenceWhenCreatingAConsentThenAPrincipalNotFoundErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest(
            [AgenticCommerceHelper::TEST_SCOPE_A],
            AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER,
            static::UNKNOWN_PRINCIPAL_REFERENCE,
        );

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_PRINCIPAL_NOT_FOUND], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAPrincipalTypeWithoutAResolverWhenCreatingAConsentThenAPrincipalNotFoundErrorIsReturned(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest(
            [AgenticCommerceHelper::TEST_SCOPE_A],
            AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
            static::COMPANY_USER_REFERENCE,
        );

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_PRINCIPAL_NOT_FOUND], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAPrincipalOfALaterResolverWhenAnEarlierResolverSuppliesTheCandidatesThenAPrincipalNotFoundErrorIsReturned(): void
    {
        // Arrange
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, [static::COMPANY_USER_REFERENCE => $this->buildCompanyUserIdentifier()]),
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [
                $this->customerTransfer->getCustomerReferenceOrFail() => $this->buildCustomerUserIdentifier(),
            ]),
        ]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]),
        );

        // Assert
        $this->assertSame([static::ERROR_PRINCIPAL_NOT_FOUND], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAStoreNameOtherThanTheCurrentStoreWhenCreatingThenAStoreNotFoundErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest(
            [AgenticCommerceHelper::TEST_SCOPE_A],
            storeName: AgenticCommerceHelper::OTHER_STORE_NAME,
        );

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_STORE_NOT_FOUND], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAPrincipalReferenceOfAnotherCustomerWhenCreatingAConsentThenAPrincipalNotFoundErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $otherCustomerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $customerReference = $this->customerTransfer->getCustomerReferenceOrFail();
        $otherCustomerReference = $otherCustomerTransfer->getCustomerReferenceOrFail();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(
                AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER,
                [
                    $customerReference => $this->buildCustomerUserIdentifier(),
                    $otherCustomerReference => json_encode(['customer_reference' => $otherCustomerReference], JSON_THROW_ON_ERROR),
                ],
                ownerCustomerReferencesByPrincipalReference: [
                    $customerReference => $customerReference,
                    $otherCustomerReference => $otherCustomerReference,
                ],
            ),
        ]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest(
            [AgenticCommerceHelper::TEST_SCOPE_A],
            AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER,
            $otherCustomerReference,
        );

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_PRINCIPAL_NOT_FOUND], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
        $this->assertSame(0, $this->tester->countConsentsByCustomerReference($otherCustomerReference));
    }

    public function testGivenARequestWithoutTheTransactionalFlagWithAConsentOfAnUnsupportedDurationWhenCreatingThenNothingIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A])->setIsTransactional(null);
        $consentCollectionRequestTransfer->addAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())
                ->fromArray($consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->toArray())
                ->setDurationInDays(static::UNSUPPORTED_DURATION_IN_DAYS),
        );

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
        $this->assertSame([static::ERROR_DURATION_UNSUPPORTED], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenATransactionalRequestWithAConsentOfAnUnsupportedDurationWhenCreatingThenNothingIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);
        $consentCollectionRequestTransfer->addAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())
                ->fromArray($consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->toArray())
                ->setDurationInDays(static::UNSUPPORTED_DURATION_IN_DAYS),
        );

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
        $this->assertSame('1', $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenANonTransactionalRequestWithAConsentOfAnUnsupportedDurationWhenCreatingThenOnlyTheConsentOfASupportedDurationIsPersisted(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A])->setIsTransactional(false);
        $validConsentTransfer = $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetSet(
            0,
            (new AgenticCommerceConsentTransfer())->fromArray($validConsentTransfer->toArray())->setDurationInDays(static::UNSUPPORTED_DURATION_IN_DAYS),
        );
        $consentCollectionRequestTransfer->addAgenticCommerceConsent($validConsentTransfer);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
        $this->assertSame('0', $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(1, $this->countConsentsOfCustomer());
    }

    public function testGivenAFirstConsentWhenCreatedThenOneGrantedEventByTheCustomerIsWritten(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);

        // Act
        $consentTransfer = $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer)
            ->getAgenticCommerceConsents()
            ->offsetGet(0);

        // Assert
        $eventTransfers = $this->tester->getConsentEvents($consentTransfer->getUuidOrFail());
        $this->assertCount(1, $eventTransfers);
        $this->assertSame(AgenticCommerceConfig::CONSENT_EVENT_GRANTED, $eventTransfers[0]->getEvent());
        $this->assertSame(AgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER, $eventTransfers[0]->getActor());
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $eventTransfers[0]->getScopes());
        $this->assertSame(AgenticCommerceHelper::CLIENT_NAME, $eventTransfers[0]->getClientName());
    }

    public function testGivenAnActiveConsentForTheSameClientPrincipalAndStoreWhenConsentingAgainThenTheOldConsentIsRevokedAndTheNewOneRecordsReConsented(): void
    {
        // Arrange
        $firstConsentUuid = $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]))
            ->getAgenticCommerceConsents()
            ->offsetGet(0)
            ->getUuidOrFail();

        // Act
        $secondConsentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_B]),
        );

        // Assert
        $this->assertCount(0, $secondConsentCollectionResponseTransfer->getErrors());
        $firstConsentTransfer = $this->tester->findConsentByUuid($firstConsentUuid);
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $firstConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, $firstConsentTransfer->getRevokedBy());

        $secondConsentUuid = $secondConsentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($secondConsentUuid)->getStatus());
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_RE_CONSENTED], $this->tester->getConsentEventNames($secondConsentUuid));
        $this->assertSame(
            [AgenticCommerceConfig::CONSENT_EVENT_GRANTED, AgenticCommerceConfig::CONSENT_EVENT_REVOKED],
            $this->tester->getConsentEventNames($firstConsentUuid),
        );
    }

    public function testGivenAReConsentWhenCreatedThenOnlyTheReConsentedEventCarriesTheClientName(): void
    {
        // Arrange
        $firstConsentUuid = $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]))
            ->getAgenticCommerceConsents()
            ->offsetGet(0)
            ->getUuidOrFail();

        // Act
        $secondConsentUuid = $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_B]))
            ->getAgenticCommerceConsents()
            ->offsetGet(0)
            ->getUuidOrFail();

        // Assert
        $reConsentedEventTransfer = $this->tester->getConsentEvents($secondConsentUuid)[0];
        $this->assertSame(AgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER, $reConsentedEventTransfer->getActor());
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_B], $reConsentedEventTransfer->getScopes());
        $this->assertSame(AgenticCommerceHelper::CLIENT_NAME, $reConsentedEventTransfer->getClientName());

        $revokedEventTransfer = $this->tester->getConsentEvents($firstConsentUuid)[1];
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, $revokedEventTransfer->getActor());
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $revokedEventTransfer->getScopes());
        $this->assertNull($revokedEventTransfer->getClientName());
    }

    public function testGivenAPostRevokePluginWhenConsentingAgainThenThePluginReceivesTheReplacedConsentOnce(): void
    {
        // Arrange
        $firstConsentUuid = $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]))
            ->getAgenticCommerceConsents()
            ->offsetGet(0)
            ->getUuidOrFail();
        $postRevokePluginMock = $this->createMock(AgenticCommerceConsentPostRevokePluginInterface::class);
        $postRevokePluginMock->expects($this->once())->method('postRevoke')
            ->with($this->callback(fn (AgenticCommerceConsentCollectionTransfer $consentCollectionTransfer): bool => $consentCollectionTransfer->getAgenticCommerceConsents()->count() === 1
                && $consentCollectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuid() === $firstConsentUuid
                && $consentCollectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->getStatus() === AgenticCommerceConfig::CONSENT_STATUS_REVOKED));
        $this->tester->setDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, [$postRevokePluginMock]);

        // Act
        $this->tester->getFacade()->createAgenticCommerceConsentCollection($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]));
    }

    public function testGivenAnActiveConsentWhenConsentingAgainAndNoAuthorizationCodeIsIssuedThenTheOldConsentIsNotRevoked(): void
    {
        // Arrange
        $firstConsentUuid = $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]))
            ->getAgenticCommerceConsents()
            ->offsetGet(0)
            ->getUuidOrFail();
        $oauthCodeFlowFacadeMock = $this->createMock(OauthCodeFlowFacadeInterface::class);
        $oauthCodeFlowFacadeMock->method('createOauthAuthorizationCodeCollection')->willReturn(new OauthAuthorizationCodeCollectionResponseTransfer());
        $this->tester->setDependency(AgenticCommerceDependencyProvider::FACADE_OAUTH_CODE_FLOW, $oauthCodeFlowFacadeMock);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]),
        );

        // Assert
        $this->assertSame([static::ERROR_AUTHORIZATION_CODE_NOT_ISSUED], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertCount(0, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($firstConsentUuid)->getStatus());
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_GRANTED], $this->tester->getConsentEventNames($firstConsentUuid));
    }

    public function testGivenANonTransactionalRequestWithAConsentOfAnUnsupportedDurationWhenNoAuthorizationCodeIsIssuedThenADurationUnsupportedAndAnAuthorizationCodeNotIssuedErrorAreReturnedForTheirIndexesAndNoConsentIsReturned(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A])->setIsTransactional(false);
        $validConsentTransfer = $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetSet(
            0,
            (new AgenticCommerceConsentTransfer())->fromArray($validConsentTransfer->toArray())->setDurationInDays(static::UNSUPPORTED_DURATION_IN_DAYS),
        );
        $consentCollectionRequestTransfer->addAgenticCommerceConsent($validConsentTransfer);
        $oauthCodeFlowFacadeMock = $this->createMock(OauthCodeFlowFacadeInterface::class);
        $oauthCodeFlowFacadeMock->method('createOauthAuthorizationCodeCollection')->willReturn(new OauthAuthorizationCodeCollectionResponseTransfer());
        $this->tester->setDependency(AgenticCommerceDependencyProvider::FACADE_OAUTH_CODE_FLOW, $oauthCodeFlowFacadeMock);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $errors = array_map(
            static fn ($errorTransfer): array => [$errorTransfer->getEntityIdentifier(), $errorTransfer->getMessage()],
            $consentCollectionResponseTransfer->getErrors()->getArrayCopy(),
        );
        $this->assertSame([['0', static::ERROR_DURATION_UNSUPPORTED], ['1', static::ERROR_AUTHORIZATION_CODE_NOT_ISSUED]], $errors);
        $this->assertCount(0, $consentCollectionResponseTransfer->getAgenticCommerceConsents());
    }

    public function testGivenAnActiveConsentForAnotherClientWhenConsentingThenThatConsentStaysActive(): void
    {
        // Arrange
        $otherClientConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($this->customerTransfer);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]),
        );

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(
            AgenticCommerceConfig::CONSENT_STATUS_ACTIVE,
            $this->tester->findConsentByUuid($otherClientConsentTransfer->getUuidOrFail())->getStatus(),
        );
    }

    public function testGivenAnActiveConsentInAnotherStoreWhenTheCustomerConsentsTheSameClientThenThatConsentStaysActiveAndTheNewOneRecordsGranted(): void
    {
        // Arrange
        $otherStoreConsentUuid = $this->createOtherStoreConsentUuid([AgenticCommerceHelper::TEST_SCOPE_A]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]),
        );

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($otherStoreConsentUuid)->getStatus());
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_GRANTED], $this->tester->getConsentEventNames($otherStoreConsentUuid));
        $newConsentUuid = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_GRANTED], $this->tester->getConsentEventNames($newConsentUuid));
    }

    public function testGivenAnActiveConsentOfOneCompanyUserWhenTheCustomerConsentsTheSameClientAsAnotherCompanyUserThenThatConsentStaysActiveAndTheNewOneRecordsGranted(): void
    {
        // Arrange
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, [
                static::COMPANY_USER_REFERENCE => $this->buildCompanyUserIdentifier(),
                static::OTHER_COMPANY_USER_REFERENCE => json_encode(['id_company_user' => static::OTHER_COMPANY_USER_REFERENCE], JSON_THROW_ON_ERROR),
            ]),
        ]);
        $firstCompanyUserConsentUuid = $this->createConsentUuid($this->createConsentCollectionRequest(
            [AgenticCommerceHelper::TEST_SCOPE_A],
            AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
            static::COMPANY_USER_REFERENCE,
        ));

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest(
                [AgenticCommerceHelper::TEST_SCOPE_A],
                AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER,
                static::OTHER_COMPANY_USER_REFERENCE,
            ),
        );

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($firstCompanyUserConsentUuid)->getStatus());
        $newConsentUuid = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_GRANTED], $this->tester->getConsentEventNames($newConsentUuid));
    }

    public function testGivenAnActiveConsentOfTheSamePrincipalInTheSameStoreWhenConsentingAgainThenOnlyThatConsentIsRevokedAndTheNewOneRecordsReConsented(): void
    {
        // Arrange
        $otherStoreConsentUuid = $this->createOtherStoreConsentUuid([AgenticCommerceHelper::TEST_SCOPE_A]);
        $sameStoreConsentUuid = $this->createConsentUuid($this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]));

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_B]),
        );

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $sameStoreConsentTransfer = $this->tester->findConsentByUuid($sameStoreConsentUuid);
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $sameStoreConsentTransfer->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER, $sameStoreConsentTransfer->getRevokedBy());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $this->tester->findConsentByUuid($otherStoreConsentUuid)->getStatus());
        $newConsentUuid = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_RE_CONSENTED], $this->tester->getConsentEventNames($newConsentUuid));
    }

    public function testGivenTwoActiveConsentsForTheSameClientPrincipalAndStoreWhenConsentingAgainThenBothAreRevokedAndOnlyTheNewOneIsActive(): void
    {
        // Arrange
        $seed = [AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $this->clientTransfer->getClientIdentifierOrFail()];
        $firstConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($this->customerTransfer, $seed);
        $secondConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($this->customerTransfer, $seed);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection(
            $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]),
        );

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($firstConsentTransfer->getUuidOrFail())->getStatus());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_REVOKED, $this->tester->findConsentByUuid($secondConsentTransfer->getUuidOrFail())->getStatus());
        $newConsentUuid = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
        $this->assertSame([$newConsentUuid], $this->tester->getActiveConsentUuidsOfTheSameClientPrincipalAndStore(
            $this->tester->findConsentByUuid($newConsentUuid),
        ));
        $this->assertSame([AgenticCommerceConfig::CONSENT_EVENT_RE_CONSENTED], $this->tester->getConsentEventNames($newConsentUuid));
    }

    public function testGivenAPrincipalWithAForgedDisplayNameWhenCreatingThenOnlyTheServerBuiltPrincipalIsUsedAndReturned(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);
        $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->getPrincipalOrFail()->setDisplayName(static::FORGED_DISPLAY_NAME);
        $serverDisplayName = $this->customerTransfer->getCustomerReferenceOrFail();
        $scopeFilterPluginMock = $this->createMock(AgenticCommerceScopeFilterPluginInterface::class);
        $scopeFilterPluginMock->expects($this->atLeastOnce())->method('filterScopes')
            ->with($this->callback(fn (AgenticCommercePrincipalTransfer $principalTransfer): bool => $principalTransfer->getDisplayName() === $serverDisplayName))
            ->willReturnArgument(1);
        $this->tester->setScopeFilterPlugins([$scopeFilterPluginMock]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(
            $serverDisplayName,
            $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getPrincipalOrFail()->getDisplayName(),
        );
    }

    public function testGivenARepeatedGrantedScopeWhenCreatingThenItIsStoredOnce(): void
    {
        // Arrange
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([AgenticCommerceHelper::TEST_SCOPE_A]);
        $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)
            ->setGrantedScopes([AgenticCommerceHelper::TEST_SCOPE_A, AgenticCommerceHelper::TEST_SCOPE_A]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $consentUuid = $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $this->tester->findConsentByUuid($consentUuid)->getGrantedScopes());
        $this->assertSame([AgenticCommerceHelper::TEST_SCOPE_A], $this->tester->getConsentEvents($consentUuid)[0]->getScopes());
    }

    /**
     * @param list<string> $scopes
     */
    protected function createConsentCollectionRequest(
        array $scopes,
        ?string $principalType = null,
        ?string $principalReference = null,
        ?string $storeName = null
    ): AgenticCommerceConsentCollectionRequestTransfer {
        return $this->tester->createConsentCollectionRequest($this->clientTransfer, $this->customerTransfer, $scopes, $principalType, $principalReference, $storeName);
    }

    /**
     * @param list<string> $scopes
     */
    protected function createOtherStoreConsentUuid(array $scopes): string
    {
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest($scopes, storeName: AgenticCommerceHelper::OTHER_STORE_NAME);
        $this->setCurrentStore(AgenticCommerceHelper::OTHER_STORE_NAME);

        try {
            return $this->createConsentUuid($consentCollectionRequestTransfer);
        } finally {
            $this->setCurrentStore(AgenticCommerceHelper::STORE_NAME);
        }
    }

    /**
     * Without a store in the request context, dynamic store mode falls back to the first store of the database, so the test sets it.
     * The Store factory keeps the container it already built, so the cache is cleared for the new store to take effect.
     */
    protected function setCurrentStore(string $storeName): void
    {
        $this->tester->haveStore([StoreTransfer::NAME => $storeName]);
        $this->tester->setDependency(static::STORE_CURRENT, $storeName);
        $this->tester->setDependency(static::CURRENT_STORE_PROVIDED_FLAG, true);
        $this->tester->clearFactoryContainerCache();
    }

    protected function createConsentUuid(AgenticCommerceConsentCollectionRequestTransfer $consentCollectionRequestTransfer): string
    {
        return $this->tester->getFacade()
            ->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer)
            ->getAgenticCommerceConsents()
            ->offsetGet(0)
            ->getUuidOrFail();
    }

    protected function buildCustomerUserIdentifier(): string
    {
        return json_encode(['customer_reference' => $this->customerTransfer->getCustomerReferenceOrFail()], JSON_THROW_ON_ERROR);
    }

    protected function buildCompanyUserIdentifier(): string
    {
        return json_encode(['id_company_user' => static::COMPANY_USER_REFERENCE], JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    protected function getErrorMessages(AgenticCommerceConsentCollectionResponseTransfer $consentCollectionResponseTransfer): array
    {
        return array_values(array_map(
            fn (ErrorTransfer $errorTransfer): string => (string)$errorTransfer->getMessage(),
            $consentCollectionResponseTransfer->getErrors()->getArrayCopy(),
        ));
    }

    protected function countConsentsOfCustomer(): int
    {
        return $this->tester->countConsentsByCustomerReference($this->customerTransfer->getCustomerReferenceOrFail());
    }
}
