<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Symfony\Component\HttpFoundation\Request;

interface ConsentCollectionRequestBuilderInterface
{
    /**
     * @param array<string, mixed> $consentData The data of the submitted consent form.
     * @param array<string, mixed> $formOptions The options the consent form was built with.
     */
    public function build(
        Request $request,
        array $consentData,
        array $formOptions,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        CustomerTransfer $customerTransfer
    ): AgenticCommerceConsentCollectionRequestTransfer;
}
