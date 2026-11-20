<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Context;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceContextTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\ApiPlatform\EventSubscriber\IdentityRequestSubscriber;
use Spryker\ApiPlatform\Request\RequestAttribute;
use Spryker\ApiPlatform\Security\ApiUser;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceContextReaderInterface;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceRequestAttributeSeeder;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceRequestAttributeSeederInterface;
use SprykerFeature\Glue\AgenticCommerce\Exception\AgenticCommerceContextMissingException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Context
 * @group AgenticCommerceRequestAttributeSeederTest
 * Add your own group annotations below this line
 */
class AgenticCommerceRequestAttributeSeederTest extends Unit
{
    protected const string FIREWALL_NAME = 'agentic_commerce';

    public function testGivenADelegatedCompanyUserContextWhenSeedingASubRequestThenTheStorefrontPrincipalAttributesAreSet(): void
    {
        // Arrange
        $customerTransfer = (new CustomerTransfer())->setCustomerReference('DE--1')->setIdCustomer(1)
            ->setCompanyUserTransfer((new CompanyUserTransfer())->setUuid('company-user-1')->setIdCompanyUser(2)->setFkCompany(3)->setFkCompanyBusinessUnit(4));
        $mainRequest = Request::create('/agentic-commerce/contract-probe');
        $mainRequest->attributes->set(RequestAttribute::STORE_TRANSFER, (new StoreTransfer())->setName('DE'));
        $mainRequest->attributes->set(RequestAttribute::LOCALE, 'de_DE');
        $seeder = $this->createSeeder($this->createContext($customerTransfer), $mainRequest, userIdentifier: '{"customer_reference":"DE--1","id_customer":1,"id_company_user":"company-user-1"}');
        $subRequest = Request::create('/carts');

        // Act
        $seeder->seedPrincipalAttributes($subRequest);

        // Assert
        $this->assertSame('DE--1', $subRequest->attributes->get(RequestAttribute::CUSTOMER_TRANSFER)->getCustomerReference());
        $this->assertSame(2, $subRequest->attributes->get(RequestAttribute::CUSTOMER_TRANSFER)->getCompanyUserTransferOrFail()->getIdCompanyUser());
        $this->assertSame('DE--1', $subRequest->attributes->get('customerReference'));
        $this->assertSame(1, $subRequest->attributes->get('idCustomer'));
        $this->assertSame('company-user-1', $subRequest->attributes->get(IdentityRequestSubscriber::ATTRIBUTE_OAUTH_IDENTITY_CLAIMS)['id_company_user']);
        $this->assertSame('DE', $subRequest->attributes->get(RequestAttribute::STORE_TRANSFER)->getName());
        $this->assertSame('de_DE', $subRequest->attributes->get(RequestAttribute::LOCALE));
    }

    public function testGivenTheContextCustomerWhenSeedingASubRequestThenTheCustomerTransferOnTheRequestIsACopy(): void
    {
        // Arrange
        $contextTransfer = $this->createContext();
        $seeder = $this->createSeeder($contextTransfer, Request::create('/agentic-commerce/contract-probe'));
        $subRequest = Request::create('/carts');

        // Act
        $seeder->seedPrincipalAttributes($subRequest);
        $subRequest->attributes->get(RequestAttribute::CUSTOMER_TRANSFER)->setCustomerReference('changed');

        // Assert
        $this->assertSame('DE--1', $contextTransfer->getCustomerOrFail()->getCustomerReference());
    }

    public function testGivenNoDelegatedContextWhenSeedingASubRequestThenAnExceptionIsThrown(): void
    {
        // Arrange
        $seeder = $this->createSeeder(null, Request::create('/carts'));

        // Assert
        $this->expectException(AgenticCommerceContextMissingException::class);

        // Act
        $seeder->seedPrincipalAttributes(Request::create('/carts'));
    }

    public function testGivenNoDelegatedContextWhenSeedingASubRequestThenNothingIsSet(): void
    {
        // Arrange
        $seeder = $this->createSeeder(null, Request::create('/carts'));
        $subRequest = Request::create('/carts');

        // Act
        try {
            $seeder->seedPrincipalAttributes($subRequest);
        } catch (AgenticCommerceContextMissingException) {
        }

        // Assert
        $this->assertCount(0, $subRequest->attributes);
    }

    protected function createSeeder(
        ?AgenticCommerceContextTransfer $contextTransfer,
        Request $mainRequest,
        string $userIdentifier = '{"customer_reference":"DE--1","id_customer":1}'
    ): AgenticCommerceRequestAttributeSeederInterface {
        $contextReaderMock = $this->createMock(AgenticCommerceContextReaderInterface::class);
        $contextReaderMock->method('findContext')->willReturn($contextTransfer);
        $tokenStorage = new TokenStorage();
        $user = new ApiUser($userIdentifier, ['ROLE_CUSTOMER'], 'client', 'jti');
        $tokenStorage->setToken(new UsernamePasswordToken($user, static::FIREWALL_NAME, $user->getRoles()));
        $requestStack = new RequestStack();
        $requestStack->push($mainRequest);

        return new AgenticCommerceRequestAttributeSeeder($contextReaderMock, $tokenStorage, $requestStack);
    }

    protected function createContext(?CustomerTransfer $customerTransfer = null): AgenticCommerceContextTransfer
    {
        return (new AgenticCommerceContextTransfer())
            ->setCustomer($customerTransfer ?? (new CustomerTransfer())->setCustomerReference('DE--1')->setIdCustomer(1));
    }
}
