<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Spryker\ApiPlatform\Security\ApiUser;

class AgenticCommerceApiUser extends ApiUser
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        protected readonly AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer,
        protected readonly AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        array $roles,
        protected readonly string $resourceName,
        protected readonly string $customerReference
    ) {
        parent::__construct(
            $agenticCommerceAccessTokenTransfer->getSubjectOrFail(),
            $roles,
            $agenticCommerceAccessTokenTransfer->getClientIdOrFail(),
            $agenticCommerceAccessTokenTransfer->getTokenIdentifierOrFail(),
        );
    }

    public function getAgenticCommerceAccessToken(): AgenticCommerceAccessTokenTransfer
    {
        return $this->agenticCommerceAccessTokenTransfer;
    }

    public function getAgenticCommerceConsent(): AgenticCommerceConsentTransfer
    {
        return $this->agenticCommerceConsentTransfer;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function getCustomerReference(): string
    {
        return $this->customerReference;
    }
}
