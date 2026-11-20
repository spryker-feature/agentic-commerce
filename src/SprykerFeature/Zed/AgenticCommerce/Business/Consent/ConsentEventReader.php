<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentEventReader implements ConsentEventReaderInterface
{
    /**
     * @var list<string>
     */
    protected const array CLIENT_NAME_EVENTS = [
        SharedAgenticCommerceConfig::CONSENT_EVENT_GRANTED,
        SharedAgenticCommerceConfig::CONSENT_EVENT_RE_CONSENTED,
    ];

    public function __construct(protected AgenticCommerceRepositoryInterface $agenticCommerceRepository)
    {
    }

    public function findLatestConsentTermsEvent(string $consentUuid): ?AgenticCommerceConsentEventTransfer
    {
        return $this->agenticCommerceRepository->findLatestAgenticCommerceConsentEvent($consentUuid, SharedAgenticCommerceConfig::CONSENT_TERMS_EVENTS);
    }

    /**
     * A `scopes_narrowed` event stores no client name; it gets the one of the latest `granted` or `re_consented` event of its consent.
     */
    public function findConsentEventById(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer
    {
        $agenticCommerceConsentEventTransfer = $this->agenticCommerceRepository->findAgenticCommerceConsentEventById($idAgenticCommerceConsentEvent);

        if ($agenticCommerceConsentEventTransfer === null || $agenticCommerceConsentEventTransfer->getClientName() !== null) {
            return $agenticCommerceConsentEventTransfer;
        }

        $clientNameEventTransfer = $this->agenticCommerceRepository->findLatestAgenticCommerceConsentEvent(
            $agenticCommerceConsentEventTransfer->getConsentUuidOrFail(),
            static::CLIENT_NAME_EVENTS,
        );

        return $agenticCommerceConsentEventTransfer->setClientName($clientNameEventTransfer?->getClientName());
    }
}
