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

interface AgenticCommerceStubInterface
{
    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer;

    public function getAgenticCommercePrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer;

    public function getAgenticCommercePrincipalCustomer(
        AgenticCommercePrincipalCustomerRequestTransfer $agenticCommercePrincipalCustomerRequestTransfer
    ): AgenticCommercePrincipalCustomerResponseTransfer;

    public function getAgenticCommerceGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer;

    public function getAgenticCommerceScopeCollection(
        AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer
    ): AgenticCommerceScopeCollectionTransfer;

    public function getAgenticCommerceAuthorizationDetailsTypeCollection(
        AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer
    ): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;

    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    public function getAgenticCommerceConsentsByCompany(
        AgenticCommerceCompanyConsentCriteriaTransfer $agenticCommerceCompanyConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    public function revokeAgenticCommerceConsentByCompanyAdmin(
        AgenticCommerceCompanyAdminRevocationRequestTransfer $agenticCommerceCompanyAdminRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer;

    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer;

    public function revokeAgenticCommerceRefreshToken(
        AgenticCommerceRefreshTokenRevocationRequestTransfer $agenticCommerceRefreshTokenRevocationRequestTransfer
    ): AgenticCommerceRefreshTokenRevocationResponseTransfer;

    public function registerAgenticCommerceClient(
        AgenticCommerceClientRegistrationRequestTransfer $agenticCommerceClientRegistrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer;

    public function getAgenticCommerceConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer;

    public function checkAgenticCommerceOrderPlacement(
        AgenticCommerceOrderPlacementCheckRequestTransfer $agenticCommerceOrderPlacementCheckRequestTransfer
    ): AgenticCommerceOrderPlacementCheckTransfer;

    public function createAgenticCommerceCartHandoff(
        AgenticCommerceCartHandoffRequestTransfer $agenticCommerceCartHandoffRequestTransfer
    ): AgenticCommerceCartHandoffResponseTransfer;

    public function redeemAgenticCommerceCartHandoff(
        AgenticCommerceCartHandoffRedeemRequestTransfer $agenticCommerceCartHandoffRedeemRequestTransfer
    ): AgenticCommerceCartHandoffRedeemResponseTransfer;
}
