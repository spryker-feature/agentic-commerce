<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Context;

interface AuthenticatedConsentHolderInterface
{
    public function setConsentUuid(?string $consentUuid): void;

    public function findConsentUuid(): ?string;
}
