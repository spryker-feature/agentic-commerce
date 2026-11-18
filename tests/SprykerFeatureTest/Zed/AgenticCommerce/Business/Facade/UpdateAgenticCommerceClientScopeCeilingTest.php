<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
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
 * @group UpdateAgenticCommerceClientScopeCeilingTest
 * Add your own group annotations below this line
 */
class UpdateAgenticCommerceClientScopeCeilingTest extends Unit
{
    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_NOT_FOUND = 'agentic_commerce.client.validation.not_found';

    protected const string ERROR_SCOPE_UNSUPPORTED = 'agentic_commerce.client.validation.scope_unsupported';

    protected const string SCOPE_CART_READ = 'cart:read';

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([
            AgenticCommerceHelper::SCOPE => 'agentic_commerce.scope.agentic-commerce.principal-read',
            static::SCOPE_CART_READ => 'agentic_commerce.scope.cart.read',
        ])]);
    }

    public function testGivenADynamicClientWithAnEmptyCeilingWhenTheCeilingIsSetThenItIsStored(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => []]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), [AgenticCommerceHelper::SCOPE]);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $responseTransfer->getAgenticCommerceClients()->offsetGet(0)->getScopeCeiling());
        $this->assertSame([AgenticCommerceHelper::SCOPE], $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getScopeCeiling());
    }

    public function testGivenAClientWithAScopeCeilingWhenTheCeilingIsSetToNoScopesThenTheCeilingIsEmpty(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), []);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertSame([], $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getScopeCeiling());
    }

    public function testGivenTheCeilingAlreadyHasThoseScopesWhenTheCeilingIsSetAgainThenNoErrorIsReturned(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => [AgenticCommerceHelper::SCOPE]]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), [AgenticCommerceHelper::SCOPE]);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getAgenticCommerceClients());
    }

    public function testGivenRepeatedScopesWhenTheCeilingIsSetThenEachScopeIsStoredOnce(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => []]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), [AgenticCommerceHelper::SCOPE, static::SCOPE_CART_READ, AgenticCommerceHelper::SCOPE]);

        // Assert
        $this->assertSame([AgenticCommerceHelper::SCOPE, static::SCOPE_CART_READ], $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getScopeCeiling());
    }

    public function testGivenAnUnsupportedScopeWhenTheCeilingIsSetThenAnErrorIsReturnedAndTheCeilingIsUnchanged(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([AgenticCommerceClientTransfer::SCOPE_CEILING => []]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), ['made-up-scope']);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_SCOPE_UNSUPPORTED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame([], $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getScopeCeiling());
    }

    public function testGivenAnUnknownClientWhenTheCeilingIsSetThenANotFoundErrorIsReturned(): void
    {
        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling('unknown-delegated-access-client', [AgenticCommerceHelper::SCOPE]);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertSame(static::ERROR_CLIENT_NOT_FOUND, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenAnEmptyClientIdentifierWhenTheCeilingIsSetThenAClientIdentifierRequiredErrorIsReturned(): void
    {
        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling('', [AgenticCommerceHelper::SCOPE]);

        // Assert
        $this->assertSame(static::ERROR_CLIENT_IDENTIFIER_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testGivenAnActiveConsentWhenTheCeilingIsNarrowedThenTheConsentIsUntouched(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient();
        $consentTransfer = $this->tester->haveAgenticCommerceConsent([AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER => $clientTransfer->getClientIdentifierOrFail()]);

        // Act
        $this->tester->getFacade()->updateAgenticCommerceClientScopeCeiling($clientTransfer->getClientIdentifierOrFail(), []);

        // Assert
        $this->assertSame([], $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getScopeCeiling());
        $storedConsentTransfer = $this->tester->findConsentByUuid($consentTransfer->getUuidOrFail());
        $this->assertSame(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE, $storedConsentTransfer->getStatus());
        $this->assertSame($consentTransfer->getGrantedScopes(), $storedConsentTransfer->getGrantedScopes());
    }
}
