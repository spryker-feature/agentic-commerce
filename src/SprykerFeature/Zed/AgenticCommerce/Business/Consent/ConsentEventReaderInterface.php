<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;

interface ConsentEventReaderInterface
{
    public function findLatestConsentTermsEvent(string $consentUuid): ?AgenticCommerceConsentEventTransfer;

    public function findConsentEventById(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer;
}
