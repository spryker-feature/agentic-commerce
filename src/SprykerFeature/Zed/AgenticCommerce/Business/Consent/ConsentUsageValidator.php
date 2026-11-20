<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentUsageValidator implements ConsentUsageValidatorInterface
{
    // One message for every failure, so a client cannot probe which part of its token no longer matches.
    protected const string ERROR_CONSENT_NOT_USABLE = 'agentic_commerce.consent.usage.not_usable';

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected PrincipalResolverInterface $principalResolver,
        protected ConsentRevokerInterface $consentRevoker,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer {
        $agenticCommerceConsentUsageResponseTransfer = (new AgenticCommerceConsentUsageResponseTransfer())->setIsValid(false);
        $consentUuid = (string)$agenticCommerceConsentUsageRequestTransfer->getConsentUuid();
        $agenticCommerceConsentTransfer = $consentUuid !== '' ? $this->agenticCommerceRepository->findAgenticCommerceConsentByUuid($consentUuid) : null;

        if ($agenticCommerceConsentTransfer === null || !$this->isUsableBy($agenticCommerceConsentTransfer, $agenticCommerceConsentUsageRequestTransfer)) {
            return $agenticCommerceConsentUsageResponseTransfer->addError($this->createNotUsableError());
        }

        $agenticCommercePrincipalTransfer = $agenticCommerceConsentTransfer->getPrincipalOrFail();

        // A missing resolver is a configuration fault, not an inactive principal: the call is refused, but the consent must survive the fix.
        if (!$this->principalResolver->hasPrincipalResolver($agenticCommercePrincipalTransfer)) {
            return $agenticCommerceConsentUsageResponseTransfer->addError($this->createNotUsableError());
        }

        if (!$this->principalResolver->isPrincipalActive($agenticCommercePrincipalTransfer)) {
            $this->revokeForInactivePrincipal($agenticCommerceConsentTransfer);

            return $agenticCommerceConsentUsageResponseTransfer->addError($this->createNotUsableError());
        }

        $this->agenticCommerceEntityManager->updateAgenticCommerceConsentLastUsedAt(
            $agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail(),
            $this->agenticCommerceConfig->getLastUsedAtWriteIntervalInSeconds(),
        );

        return $agenticCommerceConsentUsageResponseTransfer
            ->setIsValid(true)
            ->setAgenticCommerceConsent($agenticCommerceConsentTransfer);
    }

    protected function isUsableBy(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): bool {
        return $agenticCommerceConsentTransfer->getStatus() === SharedAgenticCommerceConfig::CONSENT_STATUS_ACTIVE
            && $agenticCommerceConsentTransfer->getClientIdentifier() === $agenticCommerceConsentUsageRequestTransfer->getClientIdentifier()
            && $agenticCommerceConsentTransfer->getCustomerReference() === $agenticCommerceConsentUsageRequestTransfer->getCustomerReference()
            && $agenticCommerceConsentTransfer->getStoreName() === $agenticCommerceConsentUsageRequestTransfer->getStoreName();
    }

    protected function revokeForInactivePrincipal(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): void
    {
        $this->consentRevoker->revokeAgenticCommerceConsentsBySystem(
            (new AgenticCommerceConsentRevocationRequestTransfer())
                ->addConsentUuid($agenticCommerceConsentTransfer->getUuidOrFail())
                ->setRevokedBy(SharedAgenticCommerceConfig::CONSENT_REVOKED_BY_SYSTEM_PRINCIPAL_INACTIVE),
        );
    }

    protected function createNotUsableError(): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage(static::ERROR_CONSENT_NOT_USABLE);
    }
}
