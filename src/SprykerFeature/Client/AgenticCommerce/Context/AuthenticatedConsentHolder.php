<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Context;

class AuthenticatedConsentHolder implements AuthenticatedConsentHolderInterface
{
    /**
     * Static, because Client plugins cannot reach the request; the Glue application clears it when a new main request starts.
     */
    protected static ?string $consentUuid = null;

    public function setConsentUuid(?string $consentUuid): void
    {
        static::$consentUuid = $consentUuid;
    }

    public function findConsentUuid(): ?string
    {
        return static::$consentUuid;
    }
}
