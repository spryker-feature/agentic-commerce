<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence\Propel\Mapper;

use ArrayObject;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\AgenticCommerceSalesOrderAttributionTransfer;
use JsonException;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceCartHandoff;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClient;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentConstraint;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEvent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceSalesOrder;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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
            ->setStatus($agenticCommerceClientEntity->getStatus())
            ->setBlockedBy($agenticCommerceClientEntity->getBlockedBy())
            ->setSoftwareId($agenticCommerceClientEntity->getSoftwareId())
            ->setSoftwareVersion($agenticCommerceClientEntity->getSoftwareVersion())
            ->setRegistrationMetadata($agenticCommerceClientEntity->getRegistrationMetadata())
            ->setMetadataFetchedAt($agenticCommerceClientEntity->getMetadataFetchedAt(static::DATE_TIME_FORMAT))
            ->setMetadataExpiresAt($agenticCommerceClientEntity->getMetadataExpiresAt(static::DATE_TIME_FORMAT))
            ->setCreatedAt($agenticCommerceClientEntity->getCreatedAt(static::DATE_TIME_FORMAT));
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
            ->setStatus($agenticCommerceClientTransfer->getStatusOrFail())
            ->setSoftwareId($agenticCommerceClientTransfer->getSoftwareId())
            ->setSoftwareVersion($agenticCommerceClientTransfer->getSoftwareVersion())
            ->setRegistrationMetadata($agenticCommerceClientTransfer->getRegistrationMetadata())
            ->setMetadataFetchedAt($agenticCommerceClientTransfer->getMetadataFetchedAt())
            ->setMetadataExpiresAt($agenticCommerceClientTransfer->getMetadataExpiresAt());
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

    public function mapAgenticCommerceCartHandoffTransferToAgenticCommerceCartHandoffEntity(
        AgenticCommerceCartHandoffTransfer $agenticCommerceCartHandoffTransfer,
        SpyAgenticCommerceCartHandoff $agenticCommerceCartHandoffEntity
    ): SpyAgenticCommerceCartHandoff {
        return $agenticCommerceCartHandoffEntity
            ->setTokenHash($agenticCommerceCartHandoffTransfer->getTokenHashOrFail())
            ->setFkAgenticCommerceConsent($agenticCommerceCartHandoffTransfer->getIdAgenticCommerceConsentOrFail())
            ->setQuoteUuid($agenticCommerceCartHandoffTransfer->getQuoteUuidOrFail())
            ->setPrincipalType($agenticCommerceCartHandoffTransfer->getPrincipalTypeOrFail())
            ->setPrincipalReference($agenticCommerceCartHandoffTransfer->getPrincipalReferenceOrFail())
            ->setFkStore($agenticCommerceCartHandoffTransfer->getIdStoreOrFail())
            ->setExpiresAt($agenticCommerceCartHandoffTransfer->getExpiresAtOrFail());
    }

    public function mapAgenticCommerceCartHandoffEntityToAgenticCommerceCartHandoffTransfer(
        SpyAgenticCommerceCartHandoff $agenticCommerceCartHandoffEntity,
        AgenticCommerceCartHandoffTransfer $agenticCommerceCartHandoffTransfer
    ): AgenticCommerceCartHandoffTransfer {
        return $agenticCommerceCartHandoffTransfer
            ->setIdAgenticCommerceCartHandoff($agenticCommerceCartHandoffEntity->getIdAgenticCommerceCartHandoff())
            ->setTokenHash($agenticCommerceCartHandoffEntity->getTokenHash())
            ->setIdAgenticCommerceConsent($agenticCommerceCartHandoffEntity->getFkAgenticCommerceConsent())
            ->setConsentUuid($agenticCommerceCartHandoffEntity->getAgenticCommerceConsent()->getUuid())
            ->setQuoteUuid($agenticCommerceCartHandoffEntity->getQuoteUuid())
            ->setPrincipalType($agenticCommerceCartHandoffEntity->getPrincipalType())
            ->setPrincipalReference($agenticCommerceCartHandoffEntity->getPrincipalReference())
            ->setIdStore($agenticCommerceCartHandoffEntity->getFkStore())
            ->setStoreName($agenticCommerceCartHandoffEntity->getStore()->getName())
            ->setExpiresAt($agenticCommerceCartHandoffEntity->getExpiresAt(static::DATE_TIME_FORMAT))
            ->setUsedAt($agenticCommerceCartHandoffEntity->getUsedAt(static::DATE_TIME_FORMAT));
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
            ->setClientName($agenticCommerceConsentEventTransfer->getClientName())
            ->setActorReference($agenticCommerceConsentEventTransfer->getActorReference())
            ->setIpAddress($agenticCommerceConsentEventTransfer->getIpAddress())
            ->setUserAgent($agenticCommerceConsentEventTransfer->getUserAgent())
            ->setConstraints($this->encodeConstraints($agenticCommerceConsentEventTransfer->getConstraints()));
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
            ->setActorReference($agenticCommerceConsentEventEntity->getActorReference())
            ->setIpAddress($agenticCommerceConsentEventEntity->getIpAddress())
            ->setUserAgent($agenticCommerceConsentEventEntity->getUserAgent())
            ->setCreatedAt($agenticCommerceConsentEventEntity->getCreatedAt(static::DATE_TIME_FORMAT))
            ->setConstraints(new ArrayObject($this->decodeConstraints($agenticCommerceConsentEventEntity->getConstraints())));
    }

    public function mapAgenticCommerceSalesOrderEntityToAgenticCommerceSalesOrderAttributionTransfer(
        SpyAgenticCommerceSalesOrder $agenticCommerceSalesOrderEntity,
        AgenticCommerceSalesOrderAttributionTransfer $agenticCommerceSalesOrderAttributionTransfer
    ): AgenticCommerceSalesOrderAttributionTransfer {
        $idAgenticCommerceConsentEvent = $agenticCommerceSalesOrderEntity->getFkAgenticCommerceConsentEvent();

        return $agenticCommerceSalesOrderAttributionTransfer
            ->setIdSalesOrder($agenticCommerceSalesOrderEntity->getFkSalesOrder())
            ->setConsentEvent(
                $idAgenticCommerceConsentEvent === null
                    ? null
                    : (new AgenticCommerceConsentEventTransfer())->setIdAgenticCommerceConsentEvent((int)$idAgenticCommerceConsentEvent),
            )
            ->setCreatedAt($agenticCommerceSalesOrderEntity->getCreatedAt(static::DATE_TIME_FORMAT));
    }

    /**
     * Adds what the event's consent is now: its status, the revocation time, the client and the customer.
     */
    public function expandAgenticCommerceConsentEventTransferWithConsent(
        SpyAgenticCommerceConsent $agenticCommerceConsentEntity,
        AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer
    ): AgenticCommerceConsentEventTransfer {
        $agenticCommerceClientEntity = $agenticCommerceConsentEntity->getAgenticCommerceClient();

        return $agenticCommerceConsentEventTransfer
            ->setConsentUuid($agenticCommerceConsentEntity->getUuid())
            ->setConsentStatus($this->resolveEffectiveStatus($agenticCommerceConsentEntity))
            ->setConsentRevokedAt($agenticCommerceConsentEntity->getRevokedAt(static::DATE_TIME_FORMAT))
            ->setClientIdentifier($agenticCommerceClientEntity->getFkOauthClient())
            ->setClientUri($agenticCommerceClientEntity->getClientUri())
            ->setIdCustomer($agenticCommerceConsentEntity->getFkCustomer());
    }

    public function mapAgenticCommerceConsentConstraintEntityToAgenticCommerceConsentConstraintTransfer(
        SpyAgenticCommerceConsentConstraint $agenticCommerceConsentConstraintEntity,
        AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer
    ): AgenticCommerceConsentConstraintTransfer {
        return $agenticCommerceConsentConstraintTransfer
            ->setType($agenticCommerceConsentConstraintEntity->getType())
            ->setValue($this->decodeObject($agenticCommerceConsentConstraintEntity->getValue()));
    }

    public function mapAgenticCommerceConsentConstraintTransferToAgenticCommerceConsentConstraintEntity(
        AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer,
        SpyAgenticCommerceConsentConstraint $agenticCommerceConsentConstraintEntity
    ): SpyAgenticCommerceConsentConstraint {
        return $agenticCommerceConsentConstraintEntity
            ->setType($agenticCommerceConsentConstraintTransfer->getTypeOrFail())
            ->setValue(json_encode($agenticCommerceConsentConstraintTransfer->getValue(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer> $agenticCommerceConsentConstraintTransfers
     */
    protected function encodeConstraints(ArrayObject $agenticCommerceConsentConstraintTransfers): string
    {
        $constraints = [];

        foreach ($agenticCommerceConsentConstraintTransfers as $agenticCommerceConsentConstraintTransfer) {
            $constraints[] = [
                AgenticCommerceConsentConstraintTransfer::TYPE => $agenticCommerceConsentConstraintTransfer->getTypeOrFail(),
                AgenticCommerceConsentConstraintTransfer::VALUE => $agenticCommerceConsentConstraintTransfer->getValue(),
            ];
        }

        return json_encode($constraints, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Events other than the terms events carry no constraints.
     *
     * @throws \SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    protected function decodeConstraints(?string $encodedConstraints): array
    {
        if ($encodedConstraints === null || $encodedConstraints === '') {
            return [];
        }

        $decodedConstraints = $this->decodeJson($encodedConstraints);

        if (!is_array($decodedConstraints) || !array_is_list($decodedConstraints)) {
            throw new MalformedStoredListException('The stored constraints are no JSON list.');
        }

        $agenticCommerceConsentConstraintTransfers = [];

        foreach ($decodedConstraints as $decodedConstraint) {
            if (
                !is_array($decodedConstraint)
                || !is_string($decodedConstraint[AgenticCommerceConsentConstraintTransfer::TYPE] ?? null)
                || !is_array($decodedConstraint[AgenticCommerceConsentConstraintTransfer::VALUE] ?? null)
            ) {
                throw new MalformedStoredListException('A stored constraint has no type or no value.');
            }

            $agenticCommerceConsentConstraintTransfers[] = (new AgenticCommerceConsentConstraintTransfer())
                ->setType($decodedConstraint[AgenticCommerceConsentConstraintTransfer::TYPE])
                ->setValue($decodedConstraint[AgenticCommerceConsentConstraintTransfer::VALUE]);
        }

        return $agenticCommerceConsentConstraintTransfers;
    }

    /**
     * @throws \SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException
     *
     * @return array<string, mixed>
     */
    protected function decodeObject(string $encodedValue): array
    {
        $decodedValue = $this->decodeJson($encodedValue);

        if (!is_array($decodedValue)) {
            throw new MalformedStoredListException('The stored constraint value is no JSON object.');
        }

        return $decodedValue;
    }

    /**
     * @throws \SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException
     */
    protected function decodeJson(string $encodedValue): mixed
    {
        try {
            return json_decode($encodedValue, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new MalformedStoredListException(sprintf('The stored value is no JSON: %s', $jsonException->getMessage()), previous: $jsonException);
        }
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
