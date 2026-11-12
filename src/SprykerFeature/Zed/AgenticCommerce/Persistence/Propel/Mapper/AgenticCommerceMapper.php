<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence\Propel\Mapper;

use DateTime;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use JsonException;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClient;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEvent;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException;

class AgenticCommerceMapper
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public function mapAgenticCommerceClientEntityToAgenticCommerceClientTransfer(
        SpyAgenticCommerceClient $agenticCommerceClientEntity,
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer
    ): AgenticCommerceClientTransfer {
        return $agenticCommerceClientTransfer
            ->setIdAgenticCommerceClient($agenticCommerceClientEntity->getIdAgenticCommerceClient())
            ->setClientIdentifier($agenticCommerceClientEntity->getFkOauthClient())
            ->setName($agenticCommerceClientEntity->getOauthClient()->getName())
            ->setClientUri($agenticCommerceClientEntity->getClientUri())
            ->setLogoUri($agenticCommerceClientEntity->getLogoUri())
            ->setRedirectUris($this->decodeList($agenticCommerceClientEntity->getRedirectUris()))
            ->setScopeCeiling($this->decodeList($agenticCommerceClientEntity->getScopeCeiling()))
            ->setRegistrationType($agenticCommerceClientEntity->getRegistrationType())
            ->setTrustLevel($agenticCommerceClientEntity->getTrustLevel())
            ->setStatus($agenticCommerceClientEntity->getStatus());
    }

    public function mapAgenticCommerceClientTransferToAgenticCommerceClientEntity(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer,
        SpyAgenticCommerceClient $agenticCommerceClientEntity
    ): SpyAgenticCommerceClient {
        return $agenticCommerceClientEntity
            ->setFkOauthClient($agenticCommerceClientTransfer->getClientIdentifierOrFail())
            ->setClientUri($agenticCommerceClientTransfer->getClientUri())
            ->setLogoUri($agenticCommerceClientTransfer->getLogoUri())
            ->setRedirectUris($this->encodeList($agenticCommerceClientTransfer->getRedirectUris()))
            ->setScopeCeiling($this->encodeList($agenticCommerceClientTransfer->getScopeCeiling()))
            ->setRegistrationType($agenticCommerceClientTransfer->getRegistrationTypeOrFail())
            ->setTrustLevel($agenticCommerceClientTransfer->getTrustLevelOrFail())
            ->setStatus($agenticCommerceClientTransfer->getStatusOrFail());
    }

    public function mapAgenticCommerceConsentEntityToAgenticCommerceConsentTransfer(
        SpyAgenticCommerceConsent $agenticCommerceConsentEntity,
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): AgenticCommerceConsentTransfer {
        $agenticCommerceClientEntity = $agenticCommerceConsentEntity->getAgenticCommerceClient();

        return $agenticCommerceConsentTransfer
            ->setIdAgenticCommerceConsent($agenticCommerceConsentEntity->getIdAgenticCommerceConsent())
            ->setUuid($agenticCommerceConsentEntity->getUuid())
            ->setClientIdentifier($agenticCommerceClientEntity->getFkOauthClient())
            ->setCustomerReference($agenticCommerceConsentEntity->getCustomerReference())
            ->setIdCustomer($agenticCommerceConsentEntity->getFkCustomer())
            ->setPrincipalType($agenticCommerceConsentEntity->getPrincipalType())
            ->setPrincipalReference($agenticCommerceConsentEntity->getPrincipalReference())
            ->setPrincipal(
                (new AgenticCommercePrincipalTransfer())
                    ->setPrincipalType($agenticCommerceConsentEntity->getPrincipalType())
                    ->setPrincipalReference($agenticCommerceConsentEntity->getPrincipalReference())
                    ->setCustomerReference($agenticCommerceConsentEntity->getCustomerReference()),
            )
            ->setIdStore($agenticCommerceConsentEntity->getFkStore())
            ->setStoreName($agenticCommerceConsentEntity->getStore()->getName())
            ->setGrantedScopes($this->decodeList($agenticCommerceConsentEntity->getGrantedScopes()))
            ->setStatus($this->resolveEffectiveStatus($agenticCommerceConsentEntity))
            ->setExpiresAt($agenticCommerceConsentEntity->getExpiresAt(static::DATE_TIME_FORMAT))
            ->setLastUsedAt($agenticCommerceConsentEntity->getLastUsedAt(static::DATE_TIME_FORMAT))
            ->setRevokedAt($agenticCommerceConsentEntity->getRevokedAt(static::DATE_TIME_FORMAT))
            ->setRevokedBy($agenticCommerceConsentEntity->getRevokedBy())
            ->setCreatedAt($agenticCommerceConsentEntity->getCreatedAt(static::DATE_TIME_FORMAT))
            ->setRefreshTokenIdentifier($agenticCommerceConsentEntity->getRefreshTokenIdentifier())
            ->setAgenticCommerceClient(
                $this->mapAgenticCommerceClientEntityToAgenticCommerceClientTransfer($agenticCommerceClientEntity, new AgenticCommerceClientTransfer()),
            );
    }

    public function mapAgenticCommerceConsentTransferToAgenticCommerceConsentEntity(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        SpyAgenticCommerceConsent $agenticCommerceConsentEntity
    ): SpyAgenticCommerceConsent {
        return $agenticCommerceConsentEntity
            ->setFkAgenticCommerceClient($agenticCommerceConsentTransfer->getAgenticCommerceClientOrFail()->getIdAgenticCommerceClientOrFail())
            ->setFkCustomer($agenticCommerceConsentTransfer->getIdCustomerOrFail())
            ->setCustomerReference($agenticCommerceConsentTransfer->getCustomerReferenceOrFail())
            ->setPrincipalType($agenticCommerceConsentTransfer->getPrincipalTypeOrFail())
            ->setPrincipalReference($agenticCommerceConsentTransfer->getPrincipalReferenceOrFail())
            ->setFkStore($agenticCommerceConsentTransfer->getIdStoreOrFail())
            ->setGrantedScopes($this->encodeList($agenticCommerceConsentTransfer->getGrantedScopes()))
            ->setStatus($agenticCommerceConsentTransfer->getStatusOrFail())
            ->setExpiresAt($agenticCommerceConsentTransfer->getExpiresAtOrFail());
    }

    public function mapAgenticCommerceConsentEventTransferToAgenticCommerceConsentEventEntity(
        AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer,
        SpyAgenticCommerceConsentEvent $agenticCommerceConsentEventEntity
    ): SpyAgenticCommerceConsentEvent {
        return $agenticCommerceConsentEventEntity
            ->setFkAgenticCommerceConsent($agenticCommerceConsentEventTransfer->getIdAgenticCommerceConsentOrFail())
            ->setEvent($agenticCommerceConsentEventTransfer->getEventOrFail())
            ->setActor($agenticCommerceConsentEventTransfer->getActorOrFail())
            ->setScopes($this->encodeList($agenticCommerceConsentEventTransfer->getScopes()))
            ->setClientName($agenticCommerceConsentEventTransfer->getClientName());
    }

    public function mapAgenticCommerceConsentEventEntityToAgenticCommerceConsentEventTransfer(
        SpyAgenticCommerceConsentEvent $agenticCommerceConsentEventEntity,
        AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer
    ): AgenticCommerceConsentEventTransfer {
        return $agenticCommerceConsentEventTransfer
            ->setIdAgenticCommerceConsentEvent((int)$agenticCommerceConsentEventEntity->getIdAgenticCommerceConsentEvent())
            ->setIdAgenticCommerceConsent($agenticCommerceConsentEventEntity->getFkAgenticCommerceConsent())
            ->setEvent($agenticCommerceConsentEventEntity->getEvent())
            ->setActor($agenticCommerceConsentEventEntity->getActor())
            ->setScopes($this->decodeList($agenticCommerceConsentEventEntity->getScopes()))
            ->setClientName($agenticCommerceConsentEventEntity->getClientName())
            ->setCreatedAt($agenticCommerceConsentEventEntity->getCreatedAt(static::DATE_TIME_FORMAT));
    }

    /**
     * @param array<string> $values
     */
    public function encodeList(array $values): string
    {
        return json_encode(array_values($values), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @throws \SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException
     *
     * @return list<string>
     */
    public function decodeList(?string $encodedValues): array
    {
        if ($encodedValues === null || $encodedValues === '') {
            return [];
        }

        try {
            $decodedValues = json_decode($encodedValues, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new MalformedStoredListException(sprintf('The stored value is no JSON list: %s', $jsonException->getMessage()), previous: $jsonException);
        }

        if (!is_array($decodedValues) || !array_is_list($decodedValues)) {
            throw new MalformedStoredListException('The stored value is no JSON list.');
        }

        $values = [];

        foreach ($decodedValues as $decodedValue) {
            if (!is_string($decodedValue)) {
                throw new MalformedStoredListException(sprintf('The stored list holds a %s instead of a string.', get_debug_type($decodedValue)));
            }

            $values[] = $decodedValue;
        }

        return $values;
    }

    protected function resolveEffectiveStatus(SpyAgenticCommerceConsent $agenticCommerceConsentEntity): string
    {
        // A consent stays `active` in storage after its end date; it must read as `expired` so no caller treats it as usable.
        if (
            $agenticCommerceConsentEntity->getStatus() === AgenticCommerceConfig::CONSENT_STATUS_ACTIVE
            && $agenticCommerceConsentEntity->getExpiresAt() <= new DateTime()
        ) {
            return AgenticCommerceConfig::CONSENT_STATUS_EXPIRED;
        }

        return $agenticCommerceConsentEntity->getStatus();
    }
}
