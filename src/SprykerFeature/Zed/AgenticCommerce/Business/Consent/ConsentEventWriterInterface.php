<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

interface ConsentEventWriterInterface
{
    /**
     * @param list<string> $scopes
     */
    public function write(int $idAgenticCommerceConsent, string $event, string $actor, array $scopes, ?string $clientName = null): void;
}
