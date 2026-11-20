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
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Consent
 * @group ConsentViewDataBuilderTest
 * Add your own group annotations below this line
 */
class ConsentViewDataBuilderTest extends Unit
{
    protected const string PRINCIPAL = 'customer:DE--7';

    protected const string OTHER_PRINCIPAL = 'company_user:company-user-uuid';

    protected const string SCOPE_CART_READ = 'cart:read';

    protected const string SCOPE_ORDER_PLACE = 'order:place';

    protected const string CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT = 'order_total_limit';

    protected const string TRUST_LEVEL_DOMAIN_VERIFIED = 'domain_verified';

    protected const string TRUST_LEVEL_UNVERIFIED = 'unverified';

    protected AgenticCommerceYvesTester $tester;

    public function testGivenADomainVerifiedClientWhenTheViewDataIsBuiltThenItNamesTheClientHostsShowsTheLogoAndIsNotVerified(): void
    {
        // Arrange
        $agenticCommerceAuthorizationResponseTransfer = $this->createAuthorizationResponse(
            (new AgenticCommerceClientTransfer())
                ->setTrustLevel(static::TRUST_LEVEL_DOMAIN_VERIFIED)
                ->setClientUri('https://www.procure-assist.example/about')
                ->setClientIdentifier('https://id.procure-assist.example/client.json'),
        );

        // Act
        $viewData = $this->tester->createFactory([])->createConsentViewDataBuilder()->build(
            $this->createConsentForm(static::PRINCIPAL),
            $agenticCommerceAuthorizationResponseTransfer,
            $this->createFormOptions(),
        );

        // Assert
        $this->assertSame('www.procure-assist.example', $viewData['clientHost']);
        $this->assertSame('id.procure-assist.example', $viewData['clientIdHost']);
        $this->assertTrue($viewData['showLogo']);
        $this->assertFalse($viewData['isVerifiedClient']);
    }

    public function testGivenALoopbackRedirectUriWhenTheViewDataIsBuiltThenItNamesTheRedirectHostAndFlagsTheLoopback(): void
    {
        // Arrange
        $agenticCommerceAuthorizationResponseTransfer = $this->createAuthorizationResponse((new AgenticCommerceClientTransfer())->setTrustLevel(static::TRUST_LEVEL_UNVERIFIED));

        // Act
        $viewData = $this->tester->createFactory([])->createConsentViewDataBuilder()->build(
            $this->createConsentForm(static::PRINCIPAL),
            $agenticCommerceAuthorizationResponseTransfer,
            $this->createFormOptions(),
        );

        // Assert
        $this->assertSame('app.example', $viewData['redirectHost']);
        $this->assertTrue($viewData['isLoopbackRedirect']);
    }

    public function testGivenAnUnverifiedClientWhenTheViewDataIsBuiltThenTheLogoIsHiddenAndNoClientIdentifierHostIsNamed(): void
    {
        // Arrange
        $agenticCommerceAuthorizationResponseTransfer = $this->createAuthorizationResponse(
            (new AgenticCommerceClientTransfer())
                ->setTrustLevel(static::TRUST_LEVEL_UNVERIFIED)
                ->setClientIdentifier('https://id.procure-assist.example/client.json'),
        );

        // Act
        $viewData = $this->tester->createFactory([])->createConsentViewDataBuilder()->build(
            $this->createConsentForm(static::PRINCIPAL),
            $agenticCommerceAuthorizationResponseTransfer,
            $this->createFormOptions(),
        );

        // Assert
        $this->assertFalse($viewData['showLogo']);
        $this->assertNull($viewData['clientIdHost']);
        $this->assertNull($viewData['clientHost']);
    }

    public function testGivenTheSelectedPrincipalWhenTheViewDataIsBuiltThenTheScopesAndConstraintFieldsAreThoseOfThatPrincipal(): void
    {
        // Arrange
        $agenticCommerceAuthorizationResponseTransfer = $this->createAuthorizationResponse((new AgenticCommerceClientTransfer())->setTrustLevel(static::TRUST_LEVEL_UNVERIFIED));

        // Act
        $viewDataOfPrincipal = $this->tester->createFactory([])->createConsentViewDataBuilder()->build(
            $this->createConsentForm(static::PRINCIPAL),
            $agenticCommerceAuthorizationResponseTransfer,
            $this->createFormOptions(),
        );
        $viewDataOfOtherPrincipal = $this->tester->createFactory([])->createConsentViewDataBuilder()->build(
            $this->createConsentForm(static::OTHER_PRINCIPAL),
            $agenticCommerceAuthorizationResponseTransfer,
            $this->createFormOptions(),
        );

        // Assert
        $this->assertTrue($viewDataOfPrincipal['hasGrantableScopes']);
        $this->assertTrue($viewDataOfPrincipal['isOrderPlacementOffered']);
        $this->assertSame([static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT], array_keys($viewDataOfPrincipal['constraintFields']));
        $this->assertFalse($viewDataOfOtherPrincipal['isOrderPlacementOffered']);
        $this->assertSame([], $viewDataOfOtherPrincipal['constraintFields']);
    }

    protected function createConsentForm(string $principal): FormInterface
    {
        return Forms::createFormFactory()
            ->createBuilder()
            ->add('principal', TextType::class)
            ->setData(['principal' => $principal])
            ->getForm();
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function createFormOptions(): array
    {
        return [
            'scopeChoicesByPrincipal' => [
                static::PRINCIPAL => ['agentic_commerce.scope.cart.read' => static::SCOPE_CART_READ, 'agentic_commerce.scope.order.place' => static::SCOPE_ORDER_PLACE],
                static::OTHER_PRINCIPAL => ['agentic_commerce.scope.cart.read' => static::SCOPE_CART_READ],
            ],
            'constraintFieldsByPrincipal' => [
                static::PRINCIPAL => [static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => (new AgenticCommerceConstraintFieldTransfer())->setType(static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT)],
            ],
            'constraintScopes' => [static::CONSTRAINT_TYPE_ORDER_TOTAL_LIMIT => static::SCOPE_ORDER_PLACE],
        ];
    }

    protected function createAuthorizationResponse(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): AgenticCommerceAuthorizationResponseTransfer
    {
        return (new AgenticCommerceAuthorizationResponseTransfer())
            ->setAgenticCommerceClient($agenticCommerceClientTransfer)
            ->setAuthorizationRequest(
                (new AgenticCommerceAuthorizationRequestTransfer())
                    ->setRedirectUri('https://app.example/callback')
                    ->setIsLoopbackRedirect(true),
            );
    }
}
