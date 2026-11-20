<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Symfony\Component\Form\FormInterface;

interface ConsentViewDataBuilderInterface
{
    /**
     * @param array<string, mixed> $formOptions The options the consent form was built with.
     *
     * @return array<string, mixed>
     */
    public function build(
        FormInterface $consentForm,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        array $formOptions
    ): array;
}
