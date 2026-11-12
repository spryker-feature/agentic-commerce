<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SortTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
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
 * @group GetAgenticCommerceConsentCollectionTest
 * Add your own group annotations below this line
 */
class GetAgenticCommerceConsentCollectionTest extends Unit
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string PAST = '-1 minute';

    protected const string SOON = '+1 day';

    protected const string LATER = '+20 days';

    protected const string SORT_FIELD_EXPIRES_AT = 'expiresAt';

    protected const string SORT_FIELD_UNKNOWN = '1; DROP TABLE spy_agentic_commerce_consent';

    protected const string PRINCIPAL_TYPE_COMPANY_USER = 'company_user';

    protected const string PRINCIPAL_REFERENCE_SELECTED = 'company-user-selected';

    protected const string PRINCIPAL_REFERENCE_OTHER = 'company-user-other';

    protected const int SECONDS_BETWEEN_CREATIONS = 60;

    protected const int PAGE_SIZE = 2;

    protected const int SECOND_PAGE = 2;

    protected const int CONSENT_COUNT = 3;

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenConsentsOfTwoCustomersWhenFilteringByCustomerReferenceThenOnlyThatCustomersConsentsAreReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $seed = [AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()];
        $consentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, $seed);
        $this->tester->haveAgenticCommerceConsent($seed);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())),
        );

        // Assert
        $this->assertCount(1, $collectionTransfer->getAgenticCommerceConsents());
        $foundConsentTransfer = $collectionTransfer->getAgenticCommerceConsents()->offsetGet(0);
        $this->assertSame($consentTransfer->getUuid(), $foundConsentTransfer->getUuid());
        $this->assertSame($clientTransfer->getClientIdentifier(), $foundConsentTransfer->getAgenticCommerceClientOrFail()->getClientIdentifier());
        $this->assertSame(AgenticCommerceHelper::STORE_NAME, $foundConsentTransfer->getStoreName());
    }

    public function testGivenTwoConsentsWhenFilteringByUuidThenOnlyThatConsentIsReturned(): void
    {
        // Arrange
        $consentTransfer = $this->tester->haveAgenticCommerceConsent();
        $this->tester->haveAgenticCommerceConsent();

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addUuid($consentTransfer->getUuidOrFail())),
        );

        // Assert
        $this->assertSame([$consentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
    }

    public function testGivenConsentsOfTwoPrincipalsWhenFilteringByPrincipalTypeAndReferenceThenOnlyThatPrincipalsConsentsAreReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $selectedConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => static::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE_SELECTED,
        ]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => static::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE_OTHER,
        ]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addPrincipalType(static::PRINCIPAL_TYPE_COMPANY_USER)
                ->addPrincipalReference(static::PRINCIPAL_REFERENCE_SELECTED)),
        );

        // Assert
        $this->assertSame([$selectedConsentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
    }

    public function testGivenConsentsOfTwoPrincipalTypesWhenFilteringByPrincipalTypeOnlyThenOnlyConsentsOfThatTypeAreReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $customerConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::PRINCIPAL_TYPE => static::PRINCIPAL_TYPE_COMPANY_USER,
            AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE => static::PRINCIPAL_REFERENCE_SELECTED,
        ]);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addPrincipalType(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)),
        );

        // Assert
        $this->assertSame([$customerConsentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
    }

    public function testGivenConsentsOfTwoClientsWhenFilteringByClientIdentifierThenOnlyThatClientsConsentsAreReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $selectedConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addClientIdentifier($selectedConsentTransfer->getClientIdentifierOrFail())),
        );

        // Assert
        $this->assertSame([$selectedConsentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
    }

    public function testGivenAConsentPastItsExpiryWhenFilteringByActiveThenItIsNotReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $activeConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [AgenticCommerceConsentTransfer::EXPIRES_AT => $this->formatDate(static::PAST)]);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)),
        );

        // Assert
        $this->assertSame([$activeConsentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
    }

    public function testGivenAStoredActiveConsentPastItsExpiryWhenFilteringByExpiredThenItIsReturnedAsExpired(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]);
        $expiredConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::EXPIRES_AT => $this->formatDate(static::PAST),
        ]);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addStatus(AgenticCommerceConfig::CONSENT_STATUS_EXPIRED)),
        );

        // Assert
        $this->assertSame([$expiredConsentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_EXPIRED, $collectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->getStatus());
    }

    public function testGivenActiveRevokedAndExpiredConsentsWhenFilteringByActiveAndRevokedThenTheExpiredOneIsNotReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED]);
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [AgenticCommerceConsentTransfer::EXPIRES_AT => $this->formatDate(static::PAST)]);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
                ->addStatus(AgenticCommerceConfig::CONSENT_STATUS_REVOKED)),
        );

        // Assert
        $statuses = array_map(
            static fn (AgenticCommerceConsentTransfer $consentTransfer): string => $consentTransfer->getStatusOrFail(),
            $collectionTransfer->getAgenticCommerceConsents()->getArrayCopy(),
        );
        sort($statuses);
        $this->assertSame([AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, AgenticCommerceConfig::CONSENT_STATUS_REVOKED], $statuses);
    }

    public function testGivenSortByExpiresAtAscendingWhenGettingThenTheSoonestExpiringConsentComesFirst(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [AgenticCommerceConsentTransfer::EXPIRES_AT => $this->formatDate(static::LATER)]);
        $soonestConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer, [
            AgenticCommerceConsentTransfer::EXPIRES_AT => $this->formatDate(static::SOON),
        ]);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addCustomerReference($customerTransfer->getCustomerReferenceOrFail()))
                ->addSort((new SortTransfer())->setField(static::SORT_FIELD_EXPIRES_AT)->setIsAscending(true)),
        );

        // Assert
        $this->assertSame($soonestConsentTransfer->getUuid(), $collectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuid());
    }

    public function testGivenNoSortWhenGettingThenTheNewestConsentComesFirst(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $olderConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $newerConsentTransfer = $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        $this->tester->moveCreatedAtBackBySeconds($olderConsentTransfer, static::SECONDS_BETWEEN_CREATIONS);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())),
        );

        // Assert
        $this->assertSame([$newerConsentTransfer->getUuid(), $olderConsentTransfer->getUuid()], $this->extractUuids($collectionTransfer));
    }

    public function testGivenAnUnknownSortFieldWhenGettingThenTheConsentIsStillReturned(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addCustomerReference($customerTransfer->getCustomerReferenceOrFail()))
                ->addSort((new SortTransfer())->setField(static::SORT_FIELD_UNKNOWN)->setIsAscending(true)),
        );

        // Assert
        $this->assertCount(1, $collectionTransfer->getAgenticCommerceConsents());
    }

    public function testGivenOffsetAndLimitWhenGettingThenOnePageIsReturnedWithTheTotalCount(): void
    {
        // Arrange
        $customerReference = $this->haveCustomerWithConsents(static::CONSENT_COUNT);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addCustomerReference($customerReference))
                ->setPagination((new PaginationTransfer())->setOffset(0)->setLimit(static::PAGE_SIZE)),
        );

        // Assert
        $this->assertCount(static::PAGE_SIZE, $collectionTransfer->getAgenticCommerceConsents());
        $this->assertSame(static::CONSENT_COUNT, $collectionTransfer->getPaginationOrFail()->getNbResults());
    }

    public function testGivenPageAndMaxPerPageWhenGettingThenTheRequestedPageIsReturned(): void
    {
        // Arrange
        $customerReference = $this->haveCustomerWithConsents(static::CONSENT_COUNT);

        // Act
        $collectionTransfer = $this->tester->getFacade()->getAgenticCommerceConsentCollection(
            $this->createCriteria((new AgenticCommerceConsentConditionsTransfer())->addCustomerReference($customerReference))
                ->setPagination((new PaginationTransfer())->setPage(static::SECOND_PAGE)->setMaxPerPage(static::PAGE_SIZE)),
        );

        // Assert
        $this->assertCount(static::CONSENT_COUNT - static::PAGE_SIZE, $collectionTransfer->getAgenticCommerceConsents());
        $this->assertSame(static::CONSENT_COUNT, $collectionTransfer->getPaginationOrFail()->getNbResults());
        $this->assertSame(static::SECOND_PAGE, $collectionTransfer->getPaginationOrFail()->getLastPage());
    }

    protected function haveCustomerWithConsents(int $consentCount): string
    {
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();

        for ($consentIndex = 0; $consentIndex < $consentCount; $consentIndex++) {
            $this->tester->haveAgenticCommerceConsentForCustomer($customerTransfer);
        }

        return $customerTransfer->getCustomerReferenceOrFail();
    }

    protected function createCriteria(AgenticCommerceConsentConditionsTransfer $conditionsTransfer): AgenticCommerceConsentCriteriaTransfer
    {
        return (new AgenticCommerceConsentCriteriaTransfer())->setAgenticCommerceConsentConditions($conditionsTransfer);
    }

    /**
     * @return list<string|null>
     */
    protected function extractUuids(AgenticCommerceConsentCollectionTransfer $collectionTransfer): array
    {
        return array_values(array_map(
            static fn (AgenticCommerceConsentTransfer $consentTransfer): ?string => $consentTransfer->getUuid(),
            $collectionTransfer->getAgenticCommerceConsents()->getArrayCopy(),
        ));
    }

    protected function formatDate(string $relativeDate): string
    {
        return (new DateTime($relativeDate))->format(static::DATE_TIME_FORMAT);
    }
}
