<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceCompanyAdminRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Permission\PermissionClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Controller\CompanyConnectedAppsController;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Controller
 * @group CompanyConnectedAppsControllerTest
 * Add your own group annotations below this line
 */
class CompanyConnectedAppsControllerTest extends Unit
{
    protected const string FORM_NAME = 'agenticCommerceDisconnectForm';

    protected const string PERMISSION_KEY = 'ManageCompanyAgenticCommercePermissionPlugin';

    protected const int ID_COMPANY = 7;

    protected const string ADMIN_COMPANY_USER_UUID = 'admin-company-user-uuid';

    protected const string CONSENT_UUID = 'consent-uuid';

    protected const string OTHER_CONSENT_UUID = 'other-consent-uuid';

    protected const string FOREIGN_CONSENT_UUID = 'foreign-consent-uuid';

    protected const string ERROR_CONSENT_NOT_IN_COMPANY = 'agentic_commerce.company.validation.consent_not_in_company';

    protected const string TEMPLATE_COMPANY_CONSENT_DETAILS = '@AgenticCommerce/views/company-connected-apps/company-consent-details.twig';

    protected const string GLOSSARY_KEY_SCOPE_CART_READ = 'agentic_commerce.scope.cart.read';

    protected const int LIMIT_IN_CENTS = 5000;

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected CustomerClientInterface&MockObject $customerClientMock;

    protected bool $canManageCompanyAgenticCommerce = true;

    protected AgenticCommerceFactory $agenticCommerceFactory;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $this->customerClientMock = $this->createMock(CustomerClientInterface::class);
        $this->customerClientMock->method('getCustomer')->willReturnCallback(fn (): ?CustomerTransfer => $this->createCompanyAdminCustomer());
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('can')->willReturnCallback(
            fn (string $permissionKey): bool => $permissionKey === static::PERMISSION_KEY && $this->canManageCompanyAgenticCommerce,
        );

        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $this->customerClientMock,
            'getPermissionClient' => $permissionClientMock,
        ]);
    }

    public function testGivenACompanyUserWithoutThePermissionWhenTheCompanyConnectedAppsAreShownThenAccessIsDenied(): void
    {
        // Arrange
        $this->canManageCompanyAgenticCommerce = false;

        // Assert
        $this->expectException(AccessDeniedHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('getAgenticCommerceConsentsByCompany');

        // Act
        $this->createController()->indexAction();
    }

    public function testGivenACustomerWithoutACompanyUserWhenTheCompanyConnectedAppsAreShownThenAccessIsDenied(): void
    {
        // Arrange
        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $this->createCustomerClientReturning(new CustomerTransfer()),
            'getPermissionClient' => $this->createMock(PermissionClientInterface::class),
        ]);

        // Assert
        $this->expectException(AccessDeniedHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('getAgenticCommerceConsentsByCompany');

        // Act
        $this->createController()->indexAction();
    }

    public function testGivenNoSignedInCustomerWhenTheCompanyConnectedAppsAreShownThenTheLoginPageIsShown(): void
    {
        // Arrange
        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $this->createCustomerClientReturning(null),
            'getPermissionClient' => $this->createMock(PermissionClientInterface::class),
        ]);

        // Act
        $response = $this->createController()->indexAction();

        // Assert
        $this->assertSame('/login', $response->getTargetUrl());
    }

    public function testGivenACompanyAdminWhenTheCompanyConnectedAppsAreShownThenTheConsentsOfTheSessionCompanyAndCompanyUserAreListedEachWithARevokeForm(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('getAgenticCommerceConsentsByCompany')
            ->with($this->callback(
                static fn (AgenticCommerceCompanyConsentCriteriaTransfer $agenticCommerceCompanyConsentCriteriaTransfer): bool => $agenticCommerceCompanyConsentCriteriaTransfer->getAgenticCommerceCompanyConsentConditions()?->getIdCompany() === static::ID_COMPANY
                    && $agenticCommerceCompanyConsentCriteriaTransfer->getAgenticCommerceCompanyConsentConditions()?->getCompanyUserUuid() === static::ADMIN_COMPANY_USER_UUID,
            ))
            ->willReturn($this->createConsentCollection([static::CONSENT_UUID, static::OTHER_CONSENT_UUID]));

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertSame(CompanyConnectedAppsController::TEMPLATE_COMPANY_CONNECTED_APPS, $view->getTemplate());
        $this->assertCount(2, $view->getData()['agenticCommerceConsents']);
        $this->assertSame(static::CONSENT_UUID, $view->getData()['revokeForms'][static::CONSENT_UUID]['consentUuid']->vars['value']);
        $this->assertSame(static::OTHER_CONSENT_UUID, $view->getData()['revokeForms'][static::OTHER_CONSENT_UUID]['consentUuid']->vars['value']);
    }

    public function testGivenAConsentOfACompanyUserWhenItIsListedThenItShowsTheUserNameEmailClientNameAtConsentScopesOrderLimitAndLastUsed(): void
    {
        // Arrange
        $agenticCommerceConsentTransfer = (new AgenticCommerceConsentTransfer())
            ->setUuid(static::CONSENT_UUID)
            ->setCustomer((new CustomerTransfer())->setFirstName('Sonia')->setLastName('Wagner')->setEmail('sonia@spryker.test'))
            ->setClientNameAtConsent('Procure Assist')
            ->setGrantedScopes(['cart:read'])
            ->setLastUsedAt('2026-10-20 10:15:00')
            ->addConstraint((new AgenticCommerceConsentConstraintTransfer())->setType('order_total_limit')->setValue(['amount' => static::LIMIT_IN_CENTS, 'currency' => 'EUR']));

        // Act
        $renderedDetails = $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_COMPANY_CONSENT_DETAILS, [
            'agenticCommerceConsent' => $agenticCommerceConsentTransfer,
            'scopeGlossaryKeys' => ['cart:read' => static::GLOSSARY_KEY_SCOPE_CART_READ],
        ]);

        // Assert
        $this->assertStringContainsString('Sonia Wagner', $renderedDetails);
        $this->assertStringContainsString('sonia@spryker.test', $renderedDetails);
        $this->assertStringContainsString('Procure Assist', $renderedDetails);
        $this->assertStringContainsString(static::GLOSSARY_KEY_SCOPE_CART_READ, $renderedDetails);
        $this->assertStringContainsString(sprintf('agentic_commerce.apps.constraint.order_total_limit %d EUR', static::LIMIT_IN_CENTS), $renderedDetails);
        $this->assertStringContainsString('2026-10-20 10:15', $renderedDetails);
    }

    public function testGivenAConsentNotUsedYetWhenItIsListedThenItShowsThatItWasNotUsedYet(): void
    {
        // Act
        $renderedDetails = $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_COMPANY_CONSENT_DETAILS, [
            'agenticCommerceConsent' => (new AgenticCommerceConsentTransfer())
                ->setUuid(static::CONSENT_UUID)
                ->setCustomer((new CustomerTransfer())->setFirstName('Sonia')->setLastName('Wagner')->setEmail('sonia@spryker.test'))
                ->setClientNameAtConsent('Procure Assist'),
            'scopeGlossaryKeys' => [],
        ]);

        // Assert
        $this->assertStringContainsString('agentic_commerce.apps.label.never_used', $renderedDetails);
    }

    public function testGivenACompanyAdminWhenAConsentIsRevokedThenTheCompanyAndTheCompanyUserOfTheSessionAreSentWithTheClientData(): void
    {
        // Arrange
        $request = $this->createRevokeRequest(static::CONSENT_UUID);
        $request->server->set('REMOTE_ADDR', '203.0.113.7');

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('revokeAgenticCommerceConsentByCompanyAdmin')
            ->with($this->callback(static fn (AgenticCommerceCompanyAdminRevocationRequestTransfer $agenticCommerceCompanyAdminRevocationRequestTransfer): bool => $agenticCommerceCompanyAdminRevocationRequestTransfer->getConsentUuid() === static::CONSENT_UUID
                && $agenticCommerceCompanyAdminRevocationRequestTransfer->getIdCompany() === static::ID_COMPANY
                && $agenticCommerceCompanyAdminRevocationRequestTransfer->getCompanyUserUuid() === static::ADMIN_COMPANY_USER_UUID
                && $agenticCommerceCompanyAdminRevocationRequestTransfer->getRequestClientDataOrFail()->getIpAddress() === '203.0.113.7'))
            ->willReturn(new AgenticCommerceConsentCollectionResponseTransfer());

        // Act
        $response = $this->createController()->revokeAction($request);

        // Assert
        $this->assertSame('/company/connected-apps', $response->getTargetUrl());
        $this->assertSame(['agentic_commerce.company_apps.revoked'], $this->tester->getSuccessMessages());
        $this->assertSame([], $this->tester->getErrorMessages());
    }

    public function testGivenAConsentOfAnotherCompanyWhenItIsRevokedThenTheRefusalIsShownAndTheSessionCompanyWasSent(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('revokeAgenticCommerceConsentByCompanyAdmin')
            ->with($this->callback(static fn (AgenticCommerceCompanyAdminRevocationRequestTransfer $agenticCommerceCompanyAdminRevocationRequestTransfer): bool => $agenticCommerceCompanyAdminRevocationRequestTransfer->getIdCompany() === static::ID_COMPANY))
            ->willReturn((new AgenticCommerceConsentCollectionResponseTransfer())->addError((new ErrorTransfer())->setMessage(static::ERROR_CONSENT_NOT_IN_COMPANY)));

        // Act
        $this->createController()->revokeAction($this->createRevokeRequest(static::FOREIGN_CONSENT_UUID));

        // Assert
        $this->assertSame([static::ERROR_CONSENT_NOT_IN_COMPANY], $this->tester->getErrorMessages());
        $this->assertSame([], $this->tester->getSuccessMessages());
    }

    public function testGivenThePermissionWasRemovedAfterThePageLoadedWhenAConsentIsRevokedThenAccessIsDeniedAndNothingIsRevoked(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentsByCompany')->willReturn($this->createConsentCollection([static::CONSENT_UUID]));
        $this->createController()->indexAction();
        $this->canManageCompanyAgenticCommerce = false;

        // Assert
        $this->expectException(AccessDeniedHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('revokeAgenticCommerceConsentByCompanyAdmin');

        // Act
        $this->createController()->revokeAction($this->createRevokeRequest(static::CONSENT_UUID));
    }

    public function testGivenNoCsrfTokenWhenAConsentIsRevokedThenNothingIsRevoked(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('revokeAgenticCommerceConsentByCompanyAdmin');

        // Act
        $this->createController()->revokeAction($this->tester->createRequest([], [static::FORM_NAME => ['consentUuid' => static::CONSENT_UUID]]));

        // Assert
        $this->assertSame(['agentic_commerce.company_apps.revoke_failed'], $this->tester->getErrorMessages());
    }

    public function testGivenNoIssuerIsConfiguredWhenTheCompanyConnectedAppsAreShownThenThePageIsNotFound(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);

        // Act
        $this->createController()->indexAction();
    }

    public function testGivenNoIssuerIsConfiguredWhenAConsentIsRevokedThenThePageIsNotFound(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('revokeAgenticCommerceConsentByCompanyAdmin');

        // Act
        $this->createController()->revokeAction($this->createRevokeRequest(static::CONSENT_UUID));
    }

    protected function createController(): CompanyConnectedAppsController
    {
        return $this->tester->createController(CompanyConnectedAppsController::class, $this->agenticCommerceFactory);
    }

    protected function createCompanyAdminCustomer(): CustomerTransfer
    {
        return (new CustomerTransfer())->setCompanyUserTransfer(
            (new CompanyUserTransfer())
                ->setFkCompany(static::ID_COMPANY)
                ->setUuid(static::ADMIN_COMPANY_USER_UUID),
        );
    }

    protected function createCustomerClientReturning(?CustomerTransfer $customerTransfer): CustomerClientInterface
    {
        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn($customerTransfer);

        return $customerClientMock;
    }

    protected function createRevokeRequest(string $consentUuid): Request
    {
        return $this->tester->createRequest([], [
            static::FORM_NAME => [
                'consentUuid' => $consentUuid,
                '_token' => $this->tester->getCsrfToken(static::FORM_NAME),
            ],
        ]);
    }

    /**
     * @param list<string> $consentUuids
     */
    protected function createConsentCollection(array $consentUuids): AgenticCommerceConsentCollectionTransfer
    {
        $agenticCommerceConsentCollectionTransfer = new AgenticCommerceConsentCollectionTransfer();

        foreach ($consentUuids as $consentUuid) {
            $agenticCommerceConsentCollectionTransfer->addAgenticCommerceConsent(
                (new AgenticCommerceConsentTransfer())
                    ->setUuid($consentUuid)
                    ->setStatus('active')
                    ->setPrincipalType('company_user')
                    ->setClientNameAtConsent('Procure Assist'),
            );
        }

        return $agenticCommerceConsentCollectionTransfer;
    }
}
