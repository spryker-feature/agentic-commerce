<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceRequestClientDataTransfer;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;

class ConsentEventWriter implements ConsentEventWriterInterface
{
    /**
     * The sizes of `spy_agentic_commerce_consent_event.ip_address` and `.user_agent`.
     */
    protected const int IP_ADDRESS_MAX_LENGTH = 45;

    protected const int USER_AGENT_MAX_LENGTH = 512;

    protected const string ENCODING_UTF_8 = 'UTF-8';

    public function __construct(
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
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
    ): void {
        $agenticCommerceConsentEventTransfer = (new AgenticCommerceConsentEventTransfer())
            ->setEvent($event)
            ->setActor($actor)
            ->setScopes($scopes)
            ->setClientName($clientName)
            ->setActorReference($actorReference)
            ->setConstraints(new ArrayObject($constraintTransfers));

        if ($requestClientDataTransfer !== null && $this->agenticCommerceConfig->isConsentEventClientDataCaptured()) {
            $agenticCommerceConsentEventTransfer
                ->setIpAddress($this->capIpAddress($requestClientDataTransfer->getIpAddress()))
                ->setUserAgent($this->capUserAgent($requestClientDataTransfer->getUserAgent()));
        }

        $this->agenticCommerceEntityManager->createAgenticCommerceConsentEvent($agenticCommerceConsentEventTransfer->setIdAgenticCommerceConsent($idAgenticCommerceConsent));
    }

    protected function capIpAddress(?string $ipAddress): ?string
    {
        return $ipAddress === null ? null : substr($ipAddress, 0, static::IP_ADDRESS_MAX_LENGTH);
    }

    /**
     * Replaces invalid UTF-8 byte sequences before truncating, so the stored value is always valid UTF-8.
     */
    protected function capUserAgent(?string $userAgent): ?string
    {
        return $userAgent === null ? null : mb_substr(mb_scrub($userAgent, static::ENCODING_UTF_8), 0, static::USER_AGENT_MAX_LENGTH, static::ENCODING_UTF_8);
    }
}
