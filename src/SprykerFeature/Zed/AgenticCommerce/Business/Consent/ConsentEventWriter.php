<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;

class ConsentEventWriter implements ConsentEventWriterInterface
{
    public function __construct(protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager)
    {
    }

    public function write(int $idAgenticCommerceConsent, string $event, string $actor, array $scopes, ?string $clientName = null): void
    {
        $this->agenticCommerceEntityManager->createAgenticCommerceConsentEvent(
            (new AgenticCommerceConsentEventTransfer())
                ->setIdAgenticCommerceConsent($idAgenticCommerceConsent)
                ->setEvent($event)
                ->setActor($actor)
                ->setScopes($scopes)
                ->setClientName($clientName),
        );
    }
}
