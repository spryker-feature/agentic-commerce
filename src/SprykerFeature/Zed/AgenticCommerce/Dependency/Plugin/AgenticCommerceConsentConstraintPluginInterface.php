<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;

/**
 * Use this plugin to offer one per-consent constraint on the consent page, for example an order budget: its field, its validation and the type it is stored under.
 */
interface AgenticCommerceConsentConstraintPluginInterface
{
    /**
     * Specification:
     * - Returns the unique constraint type stored with the consent; only `[a-z0-9_]`.
     *
     * @api
     */
    public function getType(): string;

    /**
     * Specification:
     * - Returns true when the consent page offers this constraint for the given principal and the scopes left after scope narrowing.
     *
     * @api
     *
     * @param list<string> $scopes
     */
    public function isApplicable(AgenticCommercePrincipalTransfer $principalTransfer, array $scopes): bool;

    /**
     * Specification:
     * - Returns true when consent cannot be given without a valid value for this constraint. Only called when applicable.
     *
     * @api
     *
     * @param list<string> $scopes
     */
    public function isRequired(AgenticCommercePrincipalTransfer $principalTransfer, array $scopes): bool;

    /**
     * Specification:
     * - Describes the consent-page field: label, input kind, currency, optional hint.
     *
     * @api
     */
    public function getFieldDefinition(AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer): AgenticCommerceConstraintFieldTransfer;

    /**
     * Specification:
     * - Validates the submitted value; returns no errors for a valid value.
     * - `AgenticCommerceConstraintFieldRequestTransfer.principal` is not set when the value comes from the `authorization_details` of an authorization request, before the customer chose a principal; it is set when a consent is created. A plugin must not require it here.
     *
     * @api
     *
     * @param array<string, mixed> $value
     *
     * @return list<\Generated\Shared\Transfer\ErrorTransfer>
     */
    public function validate(array $value, AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer): array;
}
