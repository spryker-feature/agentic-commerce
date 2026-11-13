<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClient;

class AgenticCommerceAuthenticatedConsentHelper extends Module
{
    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        $this->clearAuthenticatedConsentUuid();
    }

    public function _after(TestInterface $test): void
    {
        $this->clearAuthenticatedConsentUuid();

        parent::_after($test);
    }

    protected function clearAuthenticatedConsentUuid(): void
    {
        (new AgenticCommerceClient())->setAuthenticatedConsentUuid(null);
    }
}
