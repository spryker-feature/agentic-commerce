<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Zed;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyAdminRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCustomerRequestTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCustomerResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AgenticCommerceStub implements AgenticCommerceStubInterface
{
    public function __construct(protected ZedRequestClientInterface $zedRequestClient)
    {
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::validateAgenticCommerceAuthorizationRequestAction()
     */
    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer */
        $agenticCommerceAuthorizationResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/validate-agentic-commerce-authorization-request',
            $agenticCommerceAuthorizationRequestTransfer,
        );

        return $agenticCommerceAuthorizationResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommercePrincipalCandidatesAction()
     */
    public function getAgenticCommercePrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer
    {
        /** @var \Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer $agenticCommercePrincipalCollectionTransfer */
        $agenticCommercePrincipalCollectionTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-principal-candidates',
            $customerTransfer,
        );

        return $agenticCommercePrincipalCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommercePrincipalCustomerAction()
     */
    public function getAgenticCommercePrincipalCustomer(
        AgenticCommercePrincipalCustomerRequestTransfer $agenticCommercePrincipalCustomerRequestTransfer
    ): AgenticCommercePrincipalCustomerResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommercePrincipalCustomerResponseTransfer $agenticCommercePrincipalCustomerResponseTransfer */
        $agenticCommercePrincipalCustomerResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-principal-customer',
            $agenticCommercePrincipalCustomerRequestTransfer,
        );

        return $agenticCommercePrincipalCustomerResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommerceGrantableScopesAction()
     */
    public function getAgenticCommerceGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer $agenticCommerceGrantableScopesResponseTransfer */
        $agenticCommerceGrantableScopesResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-grantable-scopes',
            $agenticCommerceGrantableScopesRequestTransfer,
        );

        return $agenticCommerceGrantableScopesResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommerceScopeCollectionAction()
     */
    public function getAgenticCommerceScopeCollection(
        AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer
    ): AgenticCommerceScopeCollectionTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer $agenticCommerceScopeCollectionTransfer */
        $agenticCommerceScopeCollectionTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-scope-collection',
            $agenticCommerceScopeCriteriaTransfer,
        );

        return $agenticCommerceScopeCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommerceAuthorizationDetailsTypeCollectionAction()
     */
    public function getAgenticCommerceAuthorizationDetailsTypeCollection(
        AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer
    ): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer $agenticCommerceAuthorizationDetailsTypeCollectionTransfer */
        $agenticCommerceAuthorizationDetailsTypeCollectionTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-authorization-details-type-collection',
            $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer,
        );

        return $agenticCommerceAuthorizationDetailsTypeCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::createAgenticCommerceConsentCollectionAction()
     */
    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer */
        $agenticCommerceConsentCollectionResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/create-agentic-commerce-consent-collection',
            $agenticCommerceConsentCollectionRequestTransfer,
        );

        return $agenticCommerceConsentCollectionResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommerceConsentCollectionAction()
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer $agenticCommerceConsentCollectionTransfer */
        $agenticCommerceConsentCollectionTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-consent-collection',
            $agenticCommerceConsentCriteriaTransfer,
        );

        return $agenticCommerceConsentCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::updateAgenticCommerceConsentCollectionAction()
     */
    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer */
        $agenticCommerceConsentCollectionResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/update-agentic-commerce-consent-collection',
            $agenticCommerceConsentCollectionRequestTransfer,
        );

        return $agenticCommerceConsentCollectionResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommerceConsentsByCompanyAction()
     */
    public function getAgenticCommerceConsentsByCompany(
        AgenticCommerceCompanyConsentCriteriaTransfer $agenticCommerceCompanyConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer $agenticCommerceConsentCollectionTransfer */
        $agenticCommerceConsentCollectionTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-consents-by-company',
            $agenticCommerceCompanyConsentCriteriaTransfer,
        );

        return $agenticCommerceConsentCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::revokeAgenticCommerceConsentByCompanyAdminAction()
     */
    public function revokeAgenticCommerceConsentByCompanyAdmin(
        AgenticCommerceCompanyAdminRevocationRequestTransfer $agenticCommerceCompanyAdminRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer */
        $agenticCommerceConsentCollectionResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/revoke-agentic-commerce-consent-by-company-admin',
            $agenticCommerceCompanyAdminRevocationRequestTransfer,
        );

        return $agenticCommerceConsentCollectionResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::validateAgenticCommerceConsentUsageAction()
     */
    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer $agenticCommerceConsentUsageResponseTransfer */
        $agenticCommerceConsentUsageResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/validate-agentic-commerce-consent-usage',
            $agenticCommerceConsentUsageRequestTransfer,
        );

        return $agenticCommerceConsentUsageResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::revokeAgenticCommerceRefreshTokenAction()
     */
    public function revokeAgenticCommerceRefreshToken(
        AgenticCommerceRefreshTokenRevocationRequestTransfer $agenticCommerceRefreshTokenRevocationRequestTransfer
    ): AgenticCommerceRefreshTokenRevocationResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceRefreshTokenRevocationResponseTransfer $agenticCommerceRefreshTokenRevocationResponseTransfer */
        $agenticCommerceRefreshTokenRevocationResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/revoke-agentic-commerce-refresh-token',
            $agenticCommerceRefreshTokenRevocationRequestTransfer,
        );

        return $agenticCommerceRefreshTokenRevocationResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::registerAgenticCommerceClientAction()
     */
    public function registerAgenticCommerceClient(
        AgenticCommerceClientRegistrationRequestTransfer $agenticCommerceClientRegistrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer $agenticCommerceClientRegistrationResponseTransfer */
        $agenticCommerceClientRegistrationResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/register-agentic-commerce-client',
            $agenticCommerceClientRegistrationRequestTransfer,
        );

        return $agenticCommerceClientRegistrationResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::getAgenticCommerceConsentConstraintFieldsAction()
     */
    public function getAgenticCommerceConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer $agenticCommerceConstraintFieldCollectionTransfer */
        $agenticCommerceConstraintFieldCollectionTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/get-agentic-commerce-consent-constraint-fields',
            $agenticCommerceConstraintFieldRequestTransfer,
        );

        return $agenticCommerceConstraintFieldCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::checkAgenticCommerceOrderPlacementAction()
     */
    public function checkAgenticCommerceOrderPlacement(
        AgenticCommerceOrderPlacementCheckRequestTransfer $agenticCommerceOrderPlacementCheckRequestTransfer
    ): AgenticCommerceOrderPlacementCheckTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer $agenticCommerceOrderPlacementCheckTransfer */
        $agenticCommerceOrderPlacementCheckTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/check-agentic-commerce-order-placement',
            $agenticCommerceOrderPlacementCheckRequestTransfer,
        );

        return $agenticCommerceOrderPlacementCheckTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::createAgenticCommerceCartHandoffAction()
     */
    public function createAgenticCommerceCartHandoff(
        AgenticCommerceCartHandoffRequestTransfer $agenticCommerceCartHandoffRequestTransfer
    ): AgenticCommerceCartHandoffResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceCartHandoffResponseTransfer $agenticCommerceCartHandoffResponseTransfer */
        $agenticCommerceCartHandoffResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/create-agentic-commerce-cart-handoff',
            $agenticCommerceCartHandoffRequestTransfer,
        );

        return $agenticCommerceCartHandoffResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Controller\GatewayController::redeemAgenticCommerceCartHandoffAction()
     */
    public function redeemAgenticCommerceCartHandoff(
        AgenticCommerceCartHandoffRedeemRequestTransfer $agenticCommerceCartHandoffRedeemRequestTransfer
    ): AgenticCommerceCartHandoffRedeemResponseTransfer {
        /** @var \Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemResponseTransfer $agenticCommerceCartHandoffRedeemResponseTransfer */
        $agenticCommerceCartHandoffRedeemResponseTransfer = $this->zedRequestClient->call(
            '/agentic-commerce/gateway/redeem-agentic-commerce-cart-handoff',
            $agenticCommerceCartHandoffRedeemRequestTransfer,
        );

        return $agenticCommerceCartHandoffRedeemResponseTransfer;
    }
}
