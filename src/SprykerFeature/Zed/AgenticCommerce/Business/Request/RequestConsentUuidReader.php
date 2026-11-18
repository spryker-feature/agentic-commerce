<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Request;

class RequestConsentUuidReader implements RequestConsentUuidReaderInterface
{
    public function __construct(protected ?string $requestConsentUuid)
    {
    }

    public function findRequestConsentUuid(): ?string
    {
        return $this->requestConsentUuid;
    }
}
