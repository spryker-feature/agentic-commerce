<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakeAuthorizationDetailsAwareConstraintPlugin;
use SprykerFeatureTest\Zed\AgenticCommerce\Plugin\FakePrincipalResolverPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group CreateAgenticCommerceConsentCollectionConstraintTest
 * Add your own group annotations below this line
 */
class CreateAgenticCommerceConsentCollectionConstraintTest extends Unit
{
    protected const string ERROR_AUTHORIZATION_REQUEST_INVALID = 'agentic_commerce.consent.validation.authorization_request_invalid';

    protected const string ERROR_LIMIT_INVALID = 'agentic_commerce.consent.validation.limit_invalid';

    protected const int REQUESTED_LIMIT = 500;

    protected const int NARROWER_LIMIT = 400;

    protected const int WIDER_LIMIT = 501;

    protected const int OTHER_LIMIT = 300;

    protected AgenticCommerceBusinessTester $tester;

    protected AgenticCommerceClientTransfer $clientTransfer;

    protected CustomerTransfer $customerTransfer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setSupportedScopes([AgenticCommerceHelper::TEST_SCOPE_A]);
        $this->clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::SCOPE_CEILING => [AgenticCommerceHelper::TEST_SCOPE_A],
        ]);
        $this->customerTransfer = $this->tester->haveAgenticCommerceCustomer();
        $this->tester->setPrincipalResolverPlugins([
            new FakePrincipalResolverPlugin(AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER, [
                $this->customerTransfer->getCustomerReferenceOrFail() => json_encode(['customer_reference' => $this->customerTransfer->getCustomerReferenceOrFail()], JSON_THROW_ON_ERROR),
            ]),
        ]);
    }

    public function testGivenARequestedLimitAndAWiderSubmittedLimitWhenCreatingTheConsentThenTheWiderThanRequestedErrorNamesTheConstraintTypeAndNothingIsPersisted(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin()]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequestWithRequestedLimit([$this->createLimitConstraint(static::WIDER_LIMIT)]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_WIDER_THAN_REQUESTED, $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(FakeAuthorizationDetailsAwareConstraintPlugin::TYPE, $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenARequestedLimitAndANarrowerSubmittedLimitWhenCreatingTheConsentThenTheNarrowerLimitIsStored(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin()]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequestWithRequestedLimit([$this->createLimitConstraint(static::NARROWER_LIMIT)]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $constraintTransfers = $this->tester->getFacade()->getAgenticCommerceConsentConstraints($this->getCreatedConsentUuid($consentCollectionResponseTransfer));
        $this->assertCount(1, $constraintTransfers);
        $this->assertSame([FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::NARROWER_LIMIT], $constraintTransfers[0]->getValue());
    }

    public function testGivenARequestedLimitThatTheCustomerDoesNotSubmitWhenCreatingTheConsentThenTheRequestedRemovedErrorNamesTheConstraintTypeAndNothingIsPersisted(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin()]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequestWithRequestedLimit();

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(1, $consentCollectionResponseTransfer->getErrors());
        $this->assertSame(AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUESTED_REMOVED, $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(FakeAuthorizationDetailsAwareConstraintPlugin::TYPE, $consentCollectionResponseTransfer->getErrors()->offsetGet(0)->getEntityIdentifier());
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenARequiredApplicableConstraintThatIsNotSubmittedWhenCreatingTheConsentThenTheConstraintRequiredErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin(isRequired: true)]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest();

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUIRED], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAPluginRejectingTheSubmittedValueWhenCreatingTheConsentThenItsErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin(
            validationErrorTransfers: [(new ErrorTransfer())->setMessage(static::ERROR_LIMIT_INVALID)],
        )]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([$this->createLimitConstraint(static::REQUESTED_LIMIT)]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_LIMIT_INVALID], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenAConstraintOfAPluginThatIsNotApplicableWhenCreatingTheConsentThenTheConsentIsCreatedWithoutIt(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin(isApplicable: false, isRequired: true)]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([$this->createLimitConstraint(static::REQUESTED_LIMIT)]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $consentTransfer = $this->tester->findConsentByUuid($this->getCreatedConsentUuid($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->tester->countConsentConstraintRows($consentTransfer->getIdAgenticCommerceConsentOrFail()));
    }

    public function testGivenTwoSubmittedConstraintsOfOneTypeWhenCreatingTheConsentThenOnlyTheFirstIsStored(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin()]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([
            $this->createLimitConstraint(static::NARROWER_LIMIT),
            $this->createLimitConstraint(static::OTHER_LIMIT),
        ]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertCount(0, $consentCollectionResponseTransfer->getErrors());
        $constraintTransfers = $this->tester->getFacade()->getAgenticCommerceConsentConstraints($this->getCreatedConsentUuid($consentCollectionResponseTransfer));
        $this->assertCount(1, $constraintTransfers);
        $this->assertSame([FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::NARROWER_LIMIT], $constraintTransfers[0]->getValue());
    }

    public function testGivenMalformedAuthorizationDetailsAtSubmitWhenCreatingTheConsentThenTheAuthorizationRequestIsInvalidAndNothingIsPersisted(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin()]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest([$this->createLimitConstraint(static::REQUESTED_LIMIT)]);
        $consentCollectionRequestTransfer->getAuthorizationRequestOrFail()->setAuthorizationDetails('{not json');

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([static::ERROR_AUTHORIZATION_REQUEST_INVALID], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    public function testGivenARequestedLimitThatThePluginRejectsOnlyForThePrincipalWhenCreatingTheConsentThenTheInvalidAuthorizationDetailsErrorIsReturnedAndNothingIsPersisted(): void
    {
        // Arrange
        $this->tester->setConsentConstraintPlugins([new FakeAuthorizationDetailsAwareConstraintPlugin(
            validationErrorTransfersWithPrincipal: [(new ErrorTransfer())->setMessage(static::ERROR_LIMIT_INVALID)],
        )]);
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequestWithRequestedLimit([$this->createLimitConstraint(static::REQUESTED_LIMIT)]);

        // Act
        $consentCollectionResponseTransfer = $this->tester->getFacade()->createAgenticCommerceConsentCollection($consentCollectionRequestTransfer);

        // Assert
        $this->assertSame([AgenticCommerceConfig::ERROR_INVALID_AUTHORIZATION_DETAILS], $this->getErrorMessages($consentCollectionResponseTransfer));
        $this->assertSame(0, $this->countConsentsOfCustomer());
    }

    protected function createLimitConstraint(int $limit): AgenticCommerceConsentConstraintTransfer
    {
        return $this->tester->createConsentConstraint(
            FakeAuthorizationDetailsAwareConstraintPlugin::TYPE,
            [FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => $limit],
        );
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $constraintTransfers
     */
    protected function createConsentCollectionRequestWithRequestedLimit(array $constraintTransfers = []): AgenticCommerceConsentCollectionRequestTransfer
    {
        $consentCollectionRequestTransfer = $this->createConsentCollectionRequest($constraintTransfers);
        $consentCollectionRequestTransfer->getAuthorizationRequestOrFail()->setAuthorizationDetails(json_encode([[
            'type' => FakeAuthorizationDetailsAwareConstraintPlugin::AUTHORIZATION_DETAILS_TYPE,
            FakeAuthorizationDetailsAwareConstraintPlugin::VALUE_KEY_LIMIT => static::REQUESTED_LIMIT,
        ]], JSON_THROW_ON_ERROR));

        return $consentCollectionRequestTransfer;
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $constraintTransfers
     */
    protected function createConsentCollectionRequest(array $constraintTransfers = []): AgenticCommerceConsentCollectionRequestTransfer
    {
        $consentCollectionRequestTransfer = $this->tester->createConsentCollectionRequest($this->clientTransfer, $this->customerTransfer, [AgenticCommerceHelper::TEST_SCOPE_A]);

        foreach ($constraintTransfers as $constraintTransfer) {
            $consentCollectionRequestTransfer->getAgenticCommerceConsents()->offsetGet(0)->addConstraint($constraintTransfer);
        }

        return $consentCollectionRequestTransfer;
    }

    protected function getCreatedConsentUuid(AgenticCommerceConsentCollectionResponseTransfer $consentCollectionResponseTransfer): string
    {
        return $consentCollectionResponseTransfer->getAgenticCommerceConsents()->offsetGet(0)->getUuidOrFail();
    }

    /**
     * @return list<string>
     */
    protected function getErrorMessages(AgenticCommerceConsentCollectionResponseTransfer $consentCollectionResponseTransfer): array
    {
        return array_values(array_map(
            static fn (ErrorTransfer $errorTransfer): string => (string)$errorTransfer->getMessage(),
            $consentCollectionResponseTransfer->getErrors()->getArrayCopy(),
        ));
    }

    protected function countConsentsOfCustomer(): int
    {
        return $this->tester->countConsentsByCustomerReference($this->customerTransfer->getCustomerReferenceOrFail());
    }
}
