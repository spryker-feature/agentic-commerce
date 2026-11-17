<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Helper;

use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use LogicException;

class AgenticCommerceOrderPlacementSetup
{
    public function __construct(
        protected AgenticCommercePrincipalTransfer $principalTransfer,
        protected ?CompanyUserTransfer $companyUserTransfer,
        protected string $storeName,
        protected string $currencyCode,
        protected ?AgenticCommerceConsentTransfer $consentTransfer = null,
        protected ?CustomerTransfer $customerTransfer = null,
        protected ?CompanyUserTransfer $otherCompanyUserTransfer = null,
        protected ?int $latestTermsEventId = null
    ) {
    }

    public function getPrincipal(): AgenticCommercePrincipalTransfer
    {
        return $this->principalTransfer;
    }

    public function getCompanyUser(): CompanyUserTransfer
    {
        return $this->companyUserTransfer ?? throw new LogicException('The setup has no company user.');
    }

    public function getCustomer(): CustomerTransfer
    {
        return $this->customerTransfer ?? $this->getCompanyUser()->getCustomerOrFail();
    }

    public function findConsent(): ?AgenticCommerceConsentTransfer
    {
        return $this->consentTransfer;
    }

    public function getConsentUuid(): string
    {
        return $this->consentTransfer?->getUuidOrFail() ?? throw new LogicException('The setup has no consent.');
    }

    public function getLatestTermsEventId(): int
    {
        return $this->latestTermsEventId ?? throw new LogicException('The setup has no terms event.');
    }

    public function getCustomerReference(): string
    {
        return $this->getCustomer()->getCustomerReferenceOrFail();
    }

    public function getOtherCompanyUser(): CompanyUserTransfer
    {
        return $this->otherCompanyUserTransfer ?? throw new LogicException('The setup has no other company user.');
    }

    public function getStoreName(): string
    {
        return $this->storeName;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }
}
