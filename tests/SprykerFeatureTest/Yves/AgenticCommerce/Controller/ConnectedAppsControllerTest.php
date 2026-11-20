<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Controller;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use PHPUnit\Framework\MockObject\MockObject;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory;
use SprykerFeature\Yves\AgenticCommerce\Controller\ConnectedAppsController;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Controller
 * @group ConnectedAppsControllerTest
 * Add your own group annotations below this line
 */
class ConnectedAppsControllerTest extends Unit
{
    protected const string FORM_NAME = 'agenticCommerceDisconnectForm';

    protected const string CUSTOMER_REFERENCE = 'DE--7';

    protected const string CONSENT_UUID = 'consent-uuid';

    protected const string OTHER_CONSENT_UUID = 'other-consent-uuid';

    protected const string COMPANY_USER_UUID = 'company-user-uuid';

    protected const string FORMER_COMPANY_USER_UUID = 'former-company-user-uuid';

    protected const string GLOSSARY_KEY_PROVIDED_CATALOG_READ = 'catalog.consent.scope.read';

    protected const string TEMPLATE_CONSENT_CONSTRAINTS = '@AgenticCommerce/views/connected-apps/consent-constraints.twig';

    protected const string GLOSSARY_KEY_ORDER_TOTAL_LIMIT = 'agentic_commerce.apps.constraint.order_total_limit';

    protected const string CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT = 'order_total_limit';

    protected const int LIMIT_IN_CENTS = 5000;

    protected const string CURRENCY_CODE = 'EUR';

    protected AgenticCommerceYvesTester $tester;

    protected AgenticCommerceClientInterface&MockObject $agenticCommerceClientMock;

    protected AgenticCommerceFactory $agenticCommerceFactory;

    protected function _before(): void
    {
        $this->agenticCommerceClientMock = $this->createMock(AgenticCommerceClientInterface::class);
        $customerClientMock = $this->createMock(CustomerClientInterface::class);
        $customerClientMock->method('getCustomer')->willReturn((new CustomerTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE));
        $glossaryStorageClientMock = $this->createMock(GlossaryStorageClientInterface::class);
        $glossaryStorageClientMock->method('translate')->willReturnCallback(
            static fn (string $key, string $localeName, array $parameters): string => sprintf('%s:%s', $key, implode(',', $parameters)),
        );

        $this->agenticCommerceFactory = $this->tester->createFactory([
            'getAgenticCommerceClient' => $this->agenticCommerceClientMock,
            'getCustomerClient' => $customerClientMock,
            'getGlossaryStorageClient' => $glossaryStorageClientMock,
        ]);
    }

    public function testGivenASignedInCustomerWhenConnectedAppsAreShownThenOnlyTheActiveConsentsOfTheSessionCustomerAreRequested(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('getAgenticCommerceConsentCollection')
            ->with($this->callback(static fn (AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer): bool => $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditionsOrFail()->getCustomerReferences() === [static::CUSTOMER_REFERENCE]
                && $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditionsOrFail()->getStatuses() === ['active']
                && $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditionsOrFail()->getUuids() === []))
            ->willReturn($this->createConsentCollection());

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertSame(ConnectedAppsController::TEMPLATE_CONNECTED_APPS, $view->getTemplate());
        $this->assertSame(static::CONSENT_UUID, $view->getData()['disconnectForms'][static::CONSENT_UUID]['consentUuid']->vars['value']);
    }

    public function testGivenConsentsForTwoPrincipalsWhenConnectedAppsAreShownThenEachConsentNamesItsPrincipal(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn(
            $this->createConsentCollection()->addAgenticCommerceConsent(
                (new AgenticCommerceConsentTransfer())
                    ->setUuid(static::OTHER_CONSENT_UUID)
                    ->setStatus('active')
                    ->setPrincipalType('company_user')
                    ->setPrincipalReference(static::FORMER_COMPANY_USER_UUID),
            ),
        );
        $this->agenticCommerceClientMock->method('getAgenticCommercePrincipalCandidates')->willReturn(
            (new AgenticCommercePrincipalCollectionTransfer())
                ->addPrincipal((new AgenticCommercePrincipalTransfer())
                    ->setPrincipalType('customer')
                    ->setPrincipalReference(static::COMPANY_USER_UUID)
                    ->setDisplayName('Sonia Wagner'))
                ->addPrincipal((new AgenticCommercePrincipalTransfer())
                    ->setPrincipalType('company_user')
                    ->setPrincipalReference(static::COMPANY_USER_UUID)
                    ->setDisplayName('ACME Corp / Sales')),
        );

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertSame([
            static::CONSENT_UUID => 'ACME Corp / Sales',
            static::OTHER_CONSENT_UUID => static::FORMER_COMPANY_USER_UUID,
        ], $view->getData()['principalLabels']);
    }

    public function testGivenAConsentForAPrincipalThatIsNoLongerACandidateWhenConnectedAppsAreShownThenItsReferenceIsShown(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($this->createConsentCollection());
        $this->agenticCommerceClientMock->method('getAgenticCommercePrincipalCandidates')->willReturn(
            (new AgenticCommercePrincipalCollectionTransfer())
                ->addPrincipal((new AgenticCommercePrincipalTransfer())
                    ->setPrincipalType('customer')
                    ->setPrincipalReference(static::CUSTOMER_REFERENCE)
                    ->setDisplayName('Sonia Wagner')),
        );

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertSame([static::CONSENT_UUID => static::COMPANY_USER_UUID], $view->getData()['principalLabels']);
    }

    public function testGivenConsentsWithGrantedScopesWhenConnectedAppsAreShownThenTheScopesAreLabelledWithTheGlossaryKeysOfOneLookup(): void
    {
        // Arrange
        $agenticCommerceConsentCollectionTransfer = $this->createConsentCollection()->addAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())
                ->setUuid(static::OTHER_CONSENT_UUID)
                ->setStatus('active')
                ->setPrincipalType('customer')
                ->setPrincipalReference(static::CUSTOMER_REFERENCE)
                ->setGrantedScopes(['catalog:read']),
        );
        $agenticCommerceConsentCollectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->setGrantedScopes(['catalog:read', 'cart:read']);
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($agenticCommerceConsentCollectionTransfer);

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('getAgenticCommerceScopeCollection')
            ->with($this->callback(static fn (AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer): bool => $agenticCommerceScopeCriteriaTransfer->getAgenticCommerceScopeConditionsOrFail()->getNames() === ['catalog:read', 'cart:read']))
            ->willReturn((new AgenticCommerceScopeCollectionTransfer())
                ->addScope((new AgenticCommerceScopeTransfer())->setName('catalog:read')->setGlossaryKey(static::GLOSSARY_KEY_PROVIDED_CATALOG_READ))
                ->addScope((new AgenticCommerceScopeTransfer())->setName('cart:read')->setGlossaryKey('agentic_commerce.scope.cart.read')));

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertSame([
            'catalog:read' => static::GLOSSARY_KEY_PROVIDED_CATALOG_READ,
            'cart:read' => 'agentic_commerce.scope.cart.read',
        ], $view->getData()['scopeGlossaryKeys']);
    }

    public function testGivenNoConsentsWhenConnectedAppsAreShownThenNoScopesAreLookedUp(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn(new AgenticCommerceConsentCollectionTransfer());

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('getAgenticCommerceScopeCollection');

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertSame([], $view->getData()['scopeGlossaryKeys']);
    }

    public function testGivenNoCsrfTokenWhenAnAppIsDisconnectedThenNothingIsRevoked(): void
    {
        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('updateAgenticCommerceConsentCollection');

        // Act
        $response = $this->createController()->disconnectAction($this->tester->createRequest([], [static::FORM_NAME => ['consentUuid' => static::CONSENT_UUID]]));

        // Assert
        $this->assertSame('/customer/connected-apps', $response->getTargetUrl());
        $this->assertSame(['agentic_commerce.apps.disconnect_failed'], $this->tester->getErrorMessages());
    }

    public function testGivenAnActiveConsentWhenItIsDisconnectedThenItIsRevokedAsTheSessionCustomer(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($this->createConsentCollection());

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('updateAgenticCommerceConsentCollection')
            ->with($this->callback(static function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer): bool {
                $agenticCommerceConsentTransfer = $agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0);

                return $agenticCommerceConsentCollectionRequestTransfer->getIsTransactional()
                    && $agenticCommerceConsentTransfer->getUuid() === static::CONSENT_UUID
                    && $agenticCommerceConsentTransfer->getCustomerReference() === static::CUSTOMER_REFERENCE
                    && $agenticCommerceConsentTransfer->getStatus() === 'revoked'
                    && $agenticCommerceConsentTransfer->getRevokedBy() === 'customer';
            }))
            ->willReturn(new AgenticCommerceConsentCollectionResponseTransfer());

        // Act
        $this->createController()->disconnectAction($this->createDisconnectRequest());

        // Assert
        $this->assertSame(['agentic_commerce.apps.disconnected:Procure Assist'], $this->tester->getSuccessMessages());
        $this->assertSame([], $this->tester->getErrorMessages());
    }

    public function testGivenARequestWithIpAddressAndUserAgentWhenAnAppIsDisconnectedThenTheClientDataIsPassedToTheRevocation(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($this->createConsentCollection());
        $request = $this->createDisconnectRequest();
        $request->server->set('REMOTE_ADDR', '203.0.113.7');
        $request->headers->set('User-Agent', 'Agent/1.0');

        // Assert
        $this->agenticCommerceClientMock->expects($this->once())
            ->method('updateAgenticCommerceConsentCollection')
            ->with($this->callback(static function (AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer): bool {
                $requestClientDataTransfer = $agenticCommerceConsentCollectionRequestTransfer->getRequestClientDataOrFail();

                return $requestClientDataTransfer->getIpAddress() === '203.0.113.7'
                    && $requestClientDataTransfer->getUserAgent() === 'Agent/1.0';
            }))
            ->willReturn(new AgenticCommerceConsentCollectionResponseTransfer());

        // Act
        $this->createController()->disconnectAction($request);
    }

    public function testGivenTheConsentOfAnotherCustomerWhenItIsDisconnectedThenNothingIsRevoked(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')
            ->with($this->callback(static fn (AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer): bool => $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditionsOrFail()->getUuids() === [static::CONSENT_UUID]
                && $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditionsOrFail()->getCustomerReferences() === [static::CUSTOMER_REFERENCE]))
            ->willReturn(new AgenticCommerceConsentCollectionTransfer());

        // Assert
        $this->agenticCommerceClientMock->expects($this->never())->method('updateAgenticCommerceConsentCollection');

        // Act
        $this->createController()->disconnectAction($this->createDisconnectRequest());

        // Assert
        $this->assertSame(['agentic_commerce.apps.disconnect_failed'], $this->tester->getErrorMessages());
    }

    public function testGivenARefusedRevocationWhenAnAppIsDisconnectedThenAnErrorIsShown(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($this->createConsentCollection());
        $this->agenticCommerceClientMock->method('updateAgenticCommerceConsentCollection')->willReturn(
            (new AgenticCommerceConsentCollectionResponseTransfer())->addError((new ErrorTransfer())->setMessage('The consent is not active.')),
        );

        // Act
        $this->createController()->disconnectAction($this->createDisconnectRequest());

        // Assert
        $this->assertSame(['agentic_commerce.apps.disconnect_failed'], $this->tester->getErrorMessages());
        $this->assertSame([], $this->tester->getSuccessMessages());
    }

    public function testGivenNoIssuerIsConfiguredWhenConnectedAppsAreShownThenThePageIsNotFound(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('getAgenticCommerceConsentCollection');

        // Act
        $this->createController()->indexAction();
    }

    public function testGivenNoIssuerIsConfiguredWhenAnAppIsDisconnectedThenThePageIsNotFound(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Assert
        $this->expectException(NotFoundHttpException::class);
        $this->agenticCommerceClientMock->expects($this->never())->method('updateAgenticCommerceConsentCollection');

        // Act
        $this->createController()->disconnectAction($this->createDisconnectRequest());
    }

    public function testGivenAConsentWithALimitWhenConnectedAppsIsShownThenTheLimitIsListed(): void
    {
        // Arrange
        $agenticCommerceConsentCollectionTransfer = $this->createConsentCollection();
        $agenticCommerceConsentCollectionTransfer->getAgenticCommerceConsents()->offsetGet(0)->addConstraint(
            (new AgenticCommerceConsentConstraintTransfer())
                ->setType(static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT)
                ->setValue(['amount' => static::LIMIT_IN_CENTS, 'currency' => static::CURRENCY_CODE]),
        );
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($agenticCommerceConsentCollectionTransfer);

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $agenticCommerceConsentTransfer = $view->getData()['agenticCommerceConsents']->offsetGet(0);
        $this->assertSame(
            ['amount' => static::LIMIT_IN_CENTS, 'currency' => static::CURRENCY_CODE],
            $agenticCommerceConsentTransfer->getConstraints()->offsetGet(0)->getValue(),
        );
        $this->assertStringContainsString(
            sprintf('%s %d %s', static::GLOSSARY_KEY_ORDER_TOTAL_LIMIT, static::LIMIT_IN_CENTS, static::CURRENCY_CODE),
            $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CONSENT_CONSTRAINTS, ['agenticCommerceConsent' => $agenticCommerceConsentTransfer]),
        );
    }

    public function testGivenAConsentWithABudgetASellerListAndACategoryListWhenItsConstraintsAreRenderedThenEachLimitIsListedWithItsValues(): void
    {
        // Arrange
        $agenticCommerceConsentTransfer = $this->createConsentCollection()->getAgenticCommerceConsents()->offsetGet(0)
            ->addConstraint(
                (new AgenticCommerceConsentConstraintTransfer())
                    ->setType('order_budget')
                    ->setValue(['amount' => static::LIMIT_IN_CENTS, 'currency' => static::CURRENCY_CODE, 'period' => 'week']),
            )
            ->addConstraint(
                (new AgenticCommerceConsentConstraintTransfer())
                    ->setType('merchant_allowlist')
                    ->setValue(['merchantReferences' => ['MER000001', 'MER000002']])
                    ->setValueLabels(['MER000001' => 'Spryker', 'MER000002' => 'Video King']),
            )
            ->addConstraint(
                (new AgenticCommerceConsentConstraintTransfer())
                    ->setType('category_allowlist')
                    ->setValue(['categoryKeys' => ['computer']])
                    ->setValueLabels(['computer' => 'Computer']),
            );

        // Act
        $renderedConstraints = $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CONSENT_CONSTRAINTS, ['agenticCommerceConsent' => $agenticCommerceConsentTransfer]);

        // Assert
        $this->assertStringContainsString(sprintf('agentic_commerce.apps.constraint.order_budget %d %s agentic_commerce.constraint.order_budget.period.week', static::LIMIT_IN_CENTS, static::CURRENCY_CODE), $renderedConstraints);
        $this->assertStringContainsString('agentic_commerce.apps.constraint.merchant_allowlist Spryker, Video King', $renderedConstraints);
        $this->assertStringContainsString('agentic_commerce.apps.constraint.category_allowlist Computer', $renderedConstraints);
    }

    public function testGivenAConsentWithoutALimitWhenConnectedAppsIsShownThenNoLimitIsListed(): void
    {
        // Arrange
        $this->agenticCommerceClientMock->method('getAgenticCommerceConsentCollection')->willReturn($this->createConsentCollection());

        // Act
        $view = $this->createController()->indexAction();

        // Assert
        $this->assertStringNotContainsString(
            static::GLOSSARY_KEY_ORDER_TOTAL_LIMIT,
            $this->tester->renderTemplateWithGlossaryKeys(static::TEMPLATE_CONSENT_CONSTRAINTS, ['agenticCommerceConsent' => $view->getData()['agenticCommerceConsents']->offsetGet(0)]),
        );
    }

    protected function createController(): ConnectedAppsController
    {
        return $this->tester->createController(ConnectedAppsController::class, $this->agenticCommerceFactory);
    }

    protected function createDisconnectRequest(): Request
    {
        return $this->tester->createRequest([], [
            static::FORM_NAME => [
                'consentUuid' => static::CONSENT_UUID,
                '_token' => $this->tester->getCsrfToken(static::FORM_NAME),
            ],
        ]);
    }

    protected function createConsentCollection(): AgenticCommerceConsentCollectionTransfer
    {
        return (new AgenticCommerceConsentCollectionTransfer())->addAgenticCommerceConsent(
            (new AgenticCommerceConsentTransfer())
                ->setUuid(static::CONSENT_UUID)
                ->setStatus('active')
                ->setPrincipalType('company_user')
                ->setPrincipalReference(static::COMPANY_USER_UUID)
                ->setAgenticCommerceClient((new AgenticCommerceClientTransfer())->setName('Procure Assist')),
        );
    }
}
