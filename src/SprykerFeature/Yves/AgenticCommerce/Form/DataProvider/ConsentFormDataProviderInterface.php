<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Form\DataProvider;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;

interface ConsentFormDataProviderInterface
{
    /**
     * @param array<string, mixed> $formOptions The options {@link getOptions()} built for the same request.
     *
     * @return array<string, mixed>
     */
    public function getData(
        CustomerTransfer $customerTransfer,
        array $formOptions,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        CustomerTransfer $customerTransfer
    ): array;

    public function mapPrincipalValueToPrincipal(string $principal): AgenticCommercePrincipalTransfer;

    /**
     * @param array<string, mixed> $constraintValues Constraint type to the submitted value.
     * @param array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer> $constraintFieldTransfers The fields the form offered for the submitted principal.
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function mapConstraintValuesToConsentConstraints(array $constraintValues, array $constraintFieldTransfers): ArrayObject;
}
