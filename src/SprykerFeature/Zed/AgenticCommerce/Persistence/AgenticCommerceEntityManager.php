<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceClientTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentTableMap;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceCartHandoff;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClient;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientRedirectHost;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentConstraint;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEvent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentLock;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceSalesOrder;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Propel;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommercePersistenceFactory getFactory()
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AgenticCommerceEntityManager extends AbstractEntityManager implements AgenticCommerceEntityManagerInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string CONDITION_NEVER_USED = 'never_used';

    protected const string CONDITION_USED_BEFORE_WRITE_INTERVAL = 'used_before_write_interval';

    public function createAgenticCommerceClient(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): AgenticCommerceClientTransfer
    {
        $agenticCommerceClientEntity = $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceClientTransferToAgenticCommerceClientEntity($agenticCommerceClientTransfer, new SpyAgenticCommerceClient());

        foreach ($this->normalizeRedirectHosts($agenticCommerceClientTransfer->getRedirectUris()) as $redirectHost) {
            $agenticCommerceClientEntity->addAgenticCommerceClientRedirectHost((new SpyAgenticCommerceClientRedirectHost())->setHost($redirectHost));
        }

        $agenticCommerceClientEntity->save();

        return $agenticCommerceClientTransfer->setIdAgenticCommerceClient($agenticCommerceClientEntity->getIdAgenticCommerceClient());
    }

    public function updateAgenticCommerceClientStatus(string $clientIdentifier, string $status): bool
    {
        $values = ['Status' => $status, 'UpdatedAt' => $this->formatNow()];

        if ($status === AgenticCommerceConfig::CLIENT_STATUS_ACTIVE) {
            $values['BlockedBy'] = null;
        }

        $affectedRows = $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->filterByFkOauthClient($clientIdentifier)
            ->filterByStatus($status, Criteria::NOT_EQUAL)
            ->update($values);

        return $affectedRows === 1;
    }

    public function updateAgenticCommerceClientBlockedBy(string $clientIdentifier, string $blockedBy): void
    {
        $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->filterByFkOauthClient($clientIdentifier)
            ->filterByStatus(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED)
            ->filterByBlockedBy(null, Criteria::ISNULL)
            ->update(['BlockedBy' => $blockedBy, 'UpdatedAt' => $this->formatNow()]);
    }

    public function updateAgenticCommerceClientMetadata(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): AgenticCommerceClientTransfer
    {
        Propel::getWriteConnection(SpyAgenticCommerceClientTableMap::DATABASE_NAME)->transaction(function () use ($agenticCommerceClientTransfer): void {
            $agenticCommerceClientQuery = $this->getFactory()
                ->createAgenticCommerceClientQuery()
                ->filterByFkOauthClient($agenticCommerceClientTransfer->getClientIdentifierOrFail());

            $affectedRows = $agenticCommerceClientQuery->update([
                'ClientUri' => $agenticCommerceClientTransfer->getClientUri(),
                'LogoUri' => $agenticCommerceClientTransfer->getLogoUri(),
                'RedirectUris' => $this->getFactory()->createAgenticCommerceMapper()->encodeList($agenticCommerceClientTransfer->getRedirectUris()),
                'MetadataFetchedAt' => $agenticCommerceClientTransfer->getMetadataFetchedAt(),
                'MetadataExpiresAt' => $agenticCommerceClientTransfer->getMetadataExpiresAt(),
                'UpdatedAt' => $this->formatNow(),
            ]);

            if ($affectedRows === 0) {
                return;
            }

            /** @var string|int|null $idAgenticCommerceClient */
            $idAgenticCommerceClient = $agenticCommerceClientQuery->select([SpyAgenticCommerceClientTableMap::COL_ID_AGENTIC_COMMERCE_CLIENT])->findOne();

            if ($idAgenticCommerceClient !== null) {
                $this->replaceAgenticCommerceClientRedirectHosts((int)$idAgenticCommerceClient, $agenticCommerceClientTransfer->getRedirectUris());
            }
        });

        return $agenticCommerceClientTransfer;
    }

    /**
     * @param array<string> $redirectUris
     */
    protected function replaceAgenticCommerceClientRedirectHosts(int $idAgenticCommerceClient, array $redirectUris): void
    {
        $this->getFactory()
            ->createAgenticCommerceClientRedirectHostQuery()
            ->filterByFkAgenticCommerceClient($idAgenticCommerceClient)
            ->delete();

        foreach ($this->normalizeRedirectHosts($redirectUris) as $redirectHost) {
            (new SpyAgenticCommerceClientRedirectHost())
                ->setFkAgenticCommerceClient($idAgenticCommerceClient)
                ->setHost($redirectHost)
                ->save();
        }
    }

    /**
     * @param array<string> $redirectUris
     *
     * @return list<string>
     */
    protected function normalizeRedirectHosts(array $redirectUris): array
    {
        return array_values(array_unique($this->getFactory()->getAgenticCommerceService()->normalizeHosts(array_map(
            static fn (string $redirectUri): string => (string)parse_url($redirectUri, PHP_URL_HOST),
            $redirectUris,
        ))));
    }

    /**
     * @param list<string> $scopeCeiling
     */
    public function updateAgenticCommerceClientScopeCeiling(string $clientIdentifier, array $scopeCeiling): bool
    {
        $encodedScopeCeiling = $this->getFactory()->createAgenticCommerceMapper()->encodeList($scopeCeiling);

        $affectedRows = $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->filterByFkOauthClient($clientIdentifier)
            ->filterByScopeCeiling($encodedScopeCeiling, Criteria::NOT_EQUAL)
            ->update(['ScopeCeiling' => $encodedScopeCeiling, 'UpdatedAt' => $this->formatNow()]);

        return $affectedRows === 1;
    }

    public function createAgenticCommerceConsentLock(int $idCustomer): void
    {
        if ($this->hasAgenticCommerceConsentLock($idCustomer)) {
            return;
        }

        try {
            (new SpyAgenticCommerceConsentLock())
                ->setFkCustomer($idCustomer)
                ->save();
        } catch (PropelException $propelException) {
            if (!$this->hasAgenticCommerceConsentLock($idCustomer)) {
                throw $propelException;
            }
        }
    }

    public function createAgenticCommerceConsent(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer
    ): AgenticCommerceConsentTransfer {
        $agenticCommerceConsentEntity = $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceConsentTransferToAgenticCommerceConsentEntity($agenticCommerceConsentTransfer, new SpyAgenticCommerceConsent());

        $agenticCommerceConsentEntity->save();

        return $agenticCommerceConsentTransfer
            ->setIdAgenticCommerceConsent($agenticCommerceConsentEntity->getIdAgenticCommerceConsent())
            ->setUuid($agenticCommerceConsentEntity->getUuid());
    }

    public function revokeAgenticCommerceConsent(int $idAgenticCommerceConsent, string $revokedBy): bool
    {
        $now = $this->formatNow();

        $affectedRows = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->update([
                'Status' => AgenticCommerceConfig::CONSENT_STATUS_REVOKED,
                'RevokedAt' => $now,
                'RevokedBy' => $revokedBy,
                'RefreshTokenIdentifier' => null,
                'UpdatedAt' => $now,
            ]);

        return $affectedRows === 1;
    }

    public function createAgenticCommerceCartHandoff(
        AgenticCommerceCartHandoffTransfer $agenticCommerceCartHandoffTransfer
    ): AgenticCommerceCartHandoffTransfer {
        $agenticCommerceCartHandoffEntity = $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceCartHandoffTransferToAgenticCommerceCartHandoffEntity($agenticCommerceCartHandoffTransfer, new SpyAgenticCommerceCartHandoff());

        $agenticCommerceCartHandoffEntity->save();

        return $agenticCommerceCartHandoffTransfer->setIdAgenticCommerceCartHandoff($agenticCommerceCartHandoffEntity->getIdAgenticCommerceCartHandoff());
    }

    public function markAgenticCommerceCartHandoffUsed(int $idAgenticCommerceCartHandoff): bool
    {
        $now = $this->formatNow();

        $affectedRows = $this->getFactory()
            ->createAgenticCommerceCartHandoffQuery()
            ->filterByIdAgenticCommerceCartHandoff($idAgenticCommerceCartHandoff)
            ->filterByUsedAt(null, Criteria::ISNULL)
            ->filterByExpiresAt($now, Criteria::GREATER_THAN)
            ->update(['UsedAt' => $now, 'UpdatedAt' => $now]);

        return $affectedRows === 1;
    }

    public function deleteOpenAgenticCommerceCartHandoffs(int $idAgenticCommerceConsent): int
    {
        return $this->getFactory()
            ->createAgenticCommerceCartHandoffQuery()
            ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByUsedAt(null, Criteria::ISNULL)
            ->delete();
    }

    public function updateAgenticCommerceConsentLastUsedAt(int $idAgenticCommerceConsent, int $writeIntervalInSeconds): void
    {
        $now = new DateTime();
        $formattedNow = $now->format(static::DATE_TIME_FORMAT);
        $writeIntervalStart = (clone $now)
            ->modify(sprintf('-%d seconds', $writeIntervalInSeconds))
            ->format(static::DATE_TIME_FORMAT);

        $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->condition(static::CONDITION_NEVER_USED, sprintf('%s IS NULL', SpyAgenticCommerceConsentTableMap::COL_LAST_USED_AT))
            ->condition(static::CONDITION_USED_BEFORE_WRITE_INTERVAL, sprintf('%s <= ?', SpyAgenticCommerceConsentTableMap::COL_LAST_USED_AT), $writeIntervalStart)
            ->where([static::CONDITION_NEVER_USED, static::CONDITION_USED_BEFORE_WRITE_INTERVAL], Criteria::LOGICAL_OR)
            ->update(['LastUsedAt' => $formattedNow, 'UpdatedAt' => $formattedNow]);
    }

    public function updateAgenticCommerceConsentRefreshTokenIdentifier(
        int $idAgenticCommerceConsent,
        ?string $currentRefreshTokenIdentifier,
        string $newRefreshTokenIdentifier
    ): bool {
        $now = $this->formatNow();

        $affectedRows = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->filterByExpiresAt($now, Criteria::GREATER_THAN)
            ->filterByRefreshTokenIdentifier($currentRefreshTokenIdentifier, $currentRefreshTokenIdentifier === null ? Criteria::ISNULL : Criteria::EQUAL)
            ->update(['RefreshTokenIdentifier' => $newRefreshTokenIdentifier, 'UpdatedAt' => $now]);

        return $affectedRows === 1;
    }

    /**
     * @param list<string> $grantedScopes
     */
    public function updateAgenticCommerceConsentGrantedScopes(int $idAgenticCommerceConsent, array $grantedScopes): void
    {
        $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->update([
                'GrantedScopes' => $this->getFactory()->createAgenticCommerceMapper()->encodeList($grantedScopes),
                'UpdatedAt' => $this->formatNow(),
            ]);
    }

    public function createAgenticCommerceConsentEvent(
        AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer
    ): AgenticCommerceConsentEventTransfer {
        $agenticCommerceConsentEventEntity = $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceConsentEventTransferToAgenticCommerceConsentEventEntity(
                $agenticCommerceConsentEventTransfer,
                new SpyAgenticCommerceConsentEvent(),
            );

        $agenticCommerceConsentEventEntity->save();

        return $agenticCommerceConsentEventTransfer->setIdAgenticCommerceConsentEvent(
            (int)$agenticCommerceConsentEventEntity->getIdAgenticCommerceConsentEvent(),
        );
    }

    public function createAgenticCommerceConsentConstraint(
        int $idAgenticCommerceConsent,
        AgenticCommerceConsentConstraintTransfer $agenticCommerceConsentConstraintTransfer
    ): void {
        $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceConsentConstraintTransferToAgenticCommerceConsentConstraintEntity(
                $agenticCommerceConsentConstraintTransfer,
                (new SpyAgenticCommerceConsentConstraint())->setFkAgenticCommerceConsent($idAgenticCommerceConsent),
            )
            ->save();
    }

    public function deleteAgenticCommerceConsentConstraints(int $idAgenticCommerceConsent): void
    {
        $this->getFactory()
            ->createAgenticCommerceConsentConstraintQuery()
            ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent)
            ->delete();
    }

    /**
     * @param list<string> $types
     */
    public function deleteAgenticCommerceConsentConstraintsByTypes(int $idAgenticCommerceConsent, array $types): void
    {
        $this->getFactory()
            ->createAgenticCommerceConsentConstraintQuery()
            ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByType_In($types)
            ->delete();
    }

    public function createAgenticCommerceSalesOrderAttribution(int $idSalesOrder, int $idAgenticCommerceConsentEvent): void
    {
        (new SpyAgenticCommerceSalesOrder())
            ->setFkSalesOrder($idSalesOrder)
            ->setFkAgenticCommerceConsentEvent((string)$idAgenticCommerceConsentEvent)
            ->save();
    }

    public function expireAgenticCommerceConsent(int $idAgenticCommerceConsent): bool
    {
        $now = $this->formatNow();

        $affectedRows = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->filterByExpiresAt($now, Criteria::LESS_THAN)
            ->update([
                'Status' => AgenticCommerceConfig::CONSENT_STATUS_EXPIRED,
                'UpdatedAt' => $now,
            ]);

        return $affectedRows === 1;
    }

    public function deleteAgenticCommerceClient(int $idAgenticCommerceClient): void
    {
        $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->filterByIdAgenticCommerceClient($idAgenticCommerceClient)
            ->delete();
    }

    /**
     * @module Oauth
     */
    public function deleteOauthClientByIdentifier(string $clientIdentifier): void
    {
        $this->getFactory()
            ->getOauthClientPropelQuery()
            ->filterByIdentifier($clientIdentifier)
            ->delete();
    }

    /**
     * @param list<int> $idAgenticCommerceConsentEvents
     */
    public function deleteAgenticCommerceConsentEvents(array $idAgenticCommerceConsentEvents): int
    {
        if ($idAgenticCommerceConsentEvents === []) {
            return 0;
        }

        return $this->getFactory()
            ->createAgenticCommerceConsentEventQuery()
            ->filterByIdAgenticCommerceConsentEvent_In($idAgenticCommerceConsentEvents)
            ->delete();
    }

    /**
     * @param list<int> $idAgenticCommerceConsentEvents
     */
    public function deleteAgenticCommerceConsentEventIpAddressesAndUserAgents(array $idAgenticCommerceConsentEvents): int
    {
        if ($idAgenticCommerceConsentEvents === []) {
            return 0;
        }

        return $this->getFactory()
            ->createAgenticCommerceConsentEventQuery()
            ->filterByIdAgenticCommerceConsentEvent_In($idAgenticCommerceConsentEvents)
            ->update(['IpAddress' => null, 'UserAgent' => null]);
    }

    /**
     * @param list<int> $idAgenticCommerceConsents
     */
    public function deleteInactiveAgenticCommerceConsents(array $idAgenticCommerceConsents, DateTimeInterface $updatedBefore): int
    {
        if ($idAgenticCommerceConsents === []) {
            return 0;
        }

        return $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent_In($idAgenticCommerceConsents)
            ->filterByStatus_In([AgenticCommerceConfig::CONSENT_STATUS_REVOKED, AgenticCommerceConfig::CONSENT_STATUS_EXPIRED])
            ->filterByUpdatedAt(
                DateTimeImmutable::createFromInterface($updatedBefore)
                    ->setTimezone(new DateTimeZone(date_default_timezone_get()))
                    ->format(static::DATE_TIME_FORMAT),
                Criteria::LESS_THAN,
            )
            ->delete();
    }

    /**
     * @phpstan-impure
     */
    protected function hasAgenticCommerceConsentLock(int $idCustomer): bool
    {
        return $this->getFactory()
            ->createAgenticCommerceConsentLockQuery()
            ->filterByFkCustomer($idCustomer)
            ->exists();
    }

    protected function formatNow(): string
    {
        return (new DateTime())->format(static::DATE_TIME_FORMAT);
    }
}
