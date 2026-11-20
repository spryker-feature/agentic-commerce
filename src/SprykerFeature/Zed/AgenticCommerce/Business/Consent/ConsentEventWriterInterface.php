<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceRequestClientDataTransfer;

interface ConsentEventWriterInterface
{
    /**
     * `$constraintTransfers` are the constraints in force after the event; only the terms events store them.
     * `$requestClientDataTransfer` is stored only while the capture of client data is switched on.
     *
     * @param list<string> $scopes
     * @param list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $constraintTransfers
     */
    public function write(
        int $idAgenticCommerceConsent,
        string $event,
        string $actor,
        array $scopes,
        ?string $clientName = null,
        ?string $actorReference = null,
        array $constraintTransfers = [],
        ?AgenticCommerceRequestClientDataTransfer $requestClientDataTransfer = null
    ): void;
}
