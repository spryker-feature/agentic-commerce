<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

/**
 * Use this plugin to let a client ask for a consent constraint up front through the RFC 9396 `authorization_details` parameter, so the consent page shows the requested value and the customer can only keep or narrow it.
 */
interface AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface extends AgenticCommerceConsentConstraintPluginInterface
{
    /**
     * Specification:
     * - Returns the `type` an authorization details object carries for this constraint: a URI that starts with `AgenticCommerceConfig::AUTHORIZATION_DETAILS_TYPE_BASE`.
     *
     * @api
     */
    public function getAuthorizationDetailsType(): string;

    /**
     * Specification:
     * - Returns true when the submitted value grants nothing beyond the requested value.
     * - Returns false for a malformed value.
     * - Is executed when a consent is created (consent submission), once per applicable constraint plugin whose type the client requested in `authorization_details` and the customer submitted, after the plugin's `validate()` returned no errors; it is not executed on the authorization request or at checkout.
     * - Is meant to ensure the customer can only keep or narrow a constraint the client requested.
     *
     * @api
     *
     * @param array<string, mixed> $requestedValue
     * @param array<string, mixed> $submittedValue
     */
    public function isNarrowerOrEqual(array $requestedValue, array $submittedValue): bool;
}
