<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Context;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCustomerResponseTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\ApiPlatform\Security\ApiUser;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceContextReader;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceContextReaderInterface;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Context
 * @group AgenticCommerceContextReaderTest
 * Add your own group annotations below this line
 */
class AgenticCommerceContextReaderTest extends Unit
{
    protected const string FIREWALL_NAME = 'agentic_commerce';

    public function testGivenADelegatedUserWhenReadingTheContextThenEveryFieldIsFilledFromTokenAndPrincipal(): void
    {
        // Arrange
        $contextReader = $this->createContextReader(
            $this->createDelegatedApiUser(scopes: ['catalog:read'], consentUuid: 'consent-1', principalType: 'customer', principalReference: 'DE--1', storeName: 'DE', resourceName: 'contract-probe'),
            (new AgenticCommercePrincipalCustomerResponseTransfer())->setIsActive(true)->setCustomer((new CustomerTransfer())->setCustomerReference('DE--1')),
        );

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertSame('consent-1', $contextTransfer->getConsentUuid());
        $this->assertSame(['catalog:read'], $contextTransfer->getScopes());
        $this->assertSame('DE', $contextTransfer->getStoreName());
        $this->assertSame('contract-probe', $contextTransfer->getResourceName());
        $this->assertSame('DE--1', $contextTransfer->getCustomerOrFail()->getCustomerReference());
        $this->assertNull($contextTransfer->getCompanyUserUuid());
    }

    public function testGivenAnAccessTokenCarryingAuthorizationDetailsWhenReadingTheContextThenTheContextCarriesTheSameList(): void
    {
        // Arrange
        $authorizationDetails = [['type' => 'https://spryker.com/oauth/authorization-details/order-budget', 'amount' => 50000, 'currency' => 'EUR', 'period' => 'month']];
        $contextReader = $this->createContextReader(
            $this->createDelegatedApiUser(authorizationDetails: $authorizationDetails),
            (new AgenticCommercePrincipalCustomerResponseTransfer())->setIsActive(true)->setCustomer(new CustomerTransfer()),
        );

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertSame($authorizationDetails, $contextTransfer->getAuthorizationDetails());
    }

    public function testGivenAnAccessTokenWithoutAuthorizationDetailsWhenReadingTheContextThenTheContextCarriesAnEmptyList(): void
    {
        // Arrange
        $contextReader = $this->createContextReader(
            $this->createDelegatedApiUser(),
            (new AgenticCommercePrincipalCustomerResponseTransfer())->setIsActive(true)->setCustomer(new CustomerTransfer()),
        );

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertSame([], $contextTransfer->getAuthorizationDetails());
    }

    public function testGivenACompanyUserWhenReadingTheContextThenTheCompanyUserAndBusinessUnitUuidsAreSet(): void
    {
        // Arrange
        $companyUserTransfer = (new CompanyUserTransfer())->setUuid('company-user-1')->setCompanyBusinessUnit((new CompanyBusinessUnitTransfer())->setUuid('business-unit-1'));
        $contextReader = $this->createContextReader(
            $this->createDelegatedApiUser(principalType: 'company_user', principalReference: 'company-user-1'),
            (new AgenticCommercePrincipalCustomerResponseTransfer())->setIsActive(true)->setCustomer((new CustomerTransfer())->setCompanyUserTransfer($companyUserTransfer)),
        );

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertSame('company-user-1', $contextTransfer->getCompanyUserUuid());
        $this->assertSame('business-unit-1', $contextTransfer->getCompanyBusinessUnitUuid());
    }

    public function testGivenAStorefrontUserWhenReadingTheContextThenNullIsReturned(): void
    {
        // Arrange
        $contextReader = $this->createContextReader(new ApiUser('{"customer_reference":"DE--1"}', ['ROLE_CUSTOMER'], 'storefront-client', 'jti'), null);

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertNull($contextTransfer);
    }

    public function testGivenNoAuthenticatedUserWhenReadingTheContextThenNullIsReturned(): void
    {
        // Arrange
        $contextReader = $this->createContextReader(null, null);

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertNull($contextTransfer);
    }

    public function testGivenAnInactivePrincipalWhenReadingTheContextThenNullIsReturned(): void
    {
        // Arrange
        $contextReader = $this->createContextReader(
            $this->createDelegatedApiUser(),
            (new AgenticCommercePrincipalCustomerResponseTransfer())->setIsActive(false),
        );

        // Act
        $contextTransfer = $contextReader->findContext();

        // Assert
        $this->assertNull($contextTransfer);
    }

    public function testGivenTheContextWasReadOnceWhenReadingAgainInTheSameRequestThenZedIsCalledOnce(): void
    {
        // Arrange
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $clientMock->expects($this->once())->method('getAgenticCommercePrincipalCustomer')
            ->willReturn((new AgenticCommercePrincipalCustomerResponseTransfer())->setIsActive(true)->setCustomer(new CustomerTransfer()));
        $contextReader = new AgenticCommerceContextReader($this->createTokenStorageWith($this->createDelegatedApiUser()), $clientMock);

        // Act
        $contextReader->findContext();
        $contextReader->findContext();

        // Assert
    }

    protected function createContextReader(
        ?UserInterface $user,
        ?AgenticCommercePrincipalCustomerResponseTransfer $responseTransfer
    ): AgenticCommerceContextReaderInterface {
        $clientMock = $this->createMock(AgenticCommerceClientInterface::class);

        if ($responseTransfer !== null) {
            $clientMock->method('getAgenticCommercePrincipalCustomer')->willReturn($responseTransfer);
        }

        return new AgenticCommerceContextReader($this->createTokenStorageWith($user), $clientMock);
    }

    protected function createTokenStorageWith(?UserInterface $user): TokenStorageInterface
    {
        $tokenStorage = new TokenStorage();

        if ($user !== null) {
            $tokenStorage->setToken(new UsernamePasswordToken($user, static::FIREWALL_NAME, $user->getRoles()));
        }

        return $tokenStorage;
    }

    /**
     * @param list<string> $scopes
     * @param list<array<string, mixed>> $authorizationDetails
     */
    protected function createDelegatedApiUser(
        array $scopes = ['catalog:read'],
        string $consentUuid = 'consent-1',
        string $principalType = 'customer',
        string $principalReference = 'DE--1',
        string $storeName = 'DE',
        string $resourceName = 'contract-probe',
        array $authorizationDetails = []
    ): AgenticCommerceApiUser {
        $agenticCommerceAccessTokenTransfer = (new AgenticCommerceAccessTokenTransfer())
            ->setClientId('procurement-assistant')
            ->setSubject('{"customer_reference":"DE--1"}')
            ->setTokenIdentifier('token-identifier')
            ->setStoreName($storeName)
            ->setScopes($scopes)
            ->setAuthorizationDetails($authorizationDetails);
        $agenticCommerceConsentTransfer = (new AgenticCommerceConsentTransfer())
            ->setUuid($consentUuid)
            ->setPrincipalType($principalType)
            ->setPrincipalReference($principalReference)
            ->setGrantedScopes($scopes);

        return new AgenticCommerceApiUser($agenticCommerceAccessTokenTransfer, $agenticCommerceConsentTransfer, ['ROLE_AGENTIC_COMMERCE'], $resourceName, 'DE--1');
    }
}
