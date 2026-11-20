<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
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
 * @group GetAgenticCommercePrincipalCandidatesTest
 * Add your own group annotations below this line
 */
class GetAgenticCommercePrincipalCandidatesTest extends Unit
{
    protected const string COMPANY_USER_REFERENCE = 'company-user-uuid-1';

    protected const string SECOND_COMPANY_USER_REFERENCE = 'company-user-uuid-2';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenTwoApplicableResolversWhenGettingCandidatesThenOnlyTheFirstResolverInStackOrderSuppliesThem(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, [
                static::COMPANY_USER_REFERENCE => $this->buildCompanyUserIdentifier(static::COMPANY_USER_REFERENCE),
                static::SECOND_COMPANY_USER_REFERENCE => $this->buildCompanyUserIdentifier(static::SECOND_COMPANY_USER_REFERENCE),
            ]),
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [
                $customerTransfer->getCustomerReferenceOrFail() => $this->buildCustomerUserIdentifier($customerTransfer->getCustomerReferenceOrFail()),
            ]),
        ]);

        // Act
        $principalCollectionTransfer = $this->tester->getFacade()->getAgenticCommercePrincipalCandidates($customerTransfer);

        // Assert
        $this->assertSame(
            [AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER],
            $this->getPrincipalTypes($principalCollectionTransfer->getPrincipals()->getArrayCopy()),
        );
    }

    public function testGivenAFirstResolverWithoutCandidatesWhenGettingCandidatesThenTheNextResolverSuppliesThem(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, []),
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [
                $customerTransfer->getCustomerReferenceOrFail() => $this->buildCustomerUserIdentifier($customerTransfer->getCustomerReferenceOrFail()),
            ]),
        ]);

        // Act
        $principalCollectionTransfer = $this->tester->getFacade()->getAgenticCommercePrincipalCandidates($customerTransfer);

        // Assert
        $this->assertCount(1, $principalCollectionTransfer->getPrincipals());
        $principalTransfer = $principalCollectionTransfer->getPrincipals()->offsetGet(0);
        $this->assertSame(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, $principalTransfer->getPrincipalType());
        $this->assertSame($customerTransfer->getCustomerReference(), $principalTransfer->getPrincipalReference());
        $this->assertSame($customerTransfer->getCustomerReference(), $principalTransfer->getCustomerReference());
    }

    public function testGivenNoResolverWithCandidatesWhenGettingCandidatesThenTheCollectionIsEmpty(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER, []),
        ]);

        // Act
        $principalCollectionTransfer = $this->tester->getFacade()->getAgenticCommercePrincipalCandidates($customerTransfer);

        // Assert
        $this->assertCount(0, $principalCollectionTransfer->getPrincipals());
    }

    /**
     * @param array<\Generated\Shared\Transfer\AgenticCommercePrincipalTransfer> $principalTransfers
     *
     * @return list<string>
     */
    protected function getPrincipalTypes(array $principalTransfers): array
    {
        return array_values(array_map(
            fn (AgenticCommercePrincipalTransfer $principalTransfer): string => (string)$principalTransfer->getPrincipalType(),
            $principalTransfers,
        ));
    }

    protected function buildCompanyUserIdentifier(string $companyUserReference): string
    {
        return json_encode(['id_company_user' => $companyUserReference], JSON_THROW_ON_ERROR);
    }

    protected function buildCustomerUserIdentifier(string $customerReference): string
    {
        return json_encode(['customer_reference' => $customerReference], JSON_THROW_ON_ERROR);
    }
}
