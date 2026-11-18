<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use ArrayObject;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\AgenticCommerceSalesOrderAttributionTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceClientRedirectHostTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceClientTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentEventTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceSalesOrderTableMap;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentConstraintQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ArrayCollection;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommercePersistenceFactory getFactory()
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AgenticCommerceRepository extends AbstractRepository implements AgenticCommerceRepositoryInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * The events that carry the client name; the Back Office block of an order falls back to the latest of them.
     *
     * @var list<string>
     */
    protected const array CLIENT_NAME_EVENTS = AgenticCommerceRetentionReader::CLIENT_NAME_EVENTS;

    /**
     * @var array<string, string>
     */
    protected const array SORTABLE_FIELDS = [
        'createdAt' => SpyAgenticCommerceConsentTableMap::COL_CREATED_AT,
        'expiresAt' => SpyAgenticCommerceConsentTableMap::COL_EXPIRES_AT,
        'lastUsedAt' => SpyAgenticCommerceConsentTableMap::COL_LAST_USED_AT,
    ];

    /**
     * @module Oauth
     */
    public function findAgenticCommerceClientByClientIdentifier(string $clientIdentifier): ?AgenticCommerceClientTransfer
    {
        $agenticCommerceClientEntity = $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->joinWithOauthClient()
            ->filterByFkOauthClient($clientIdentifier)
            ->findOne();

        if ($agenticCommerceClientEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceClientEntityToAgenticCommerceClientTransfer($agenticCommerceClientEntity, new AgenticCommerceClientTransfer());
    }

    /**
     * @module Oauth
     */
    public function getAgenticCommerceClientCollection(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer
    ): AgenticCommerceClientCollectionTransfer {
        $agenticCommerceClientQuery = $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->joinWithOauthClient()
            ->orderByIdAgenticCommerceClient(Criteria::DESC);
        $agenticCommerceClientConditionsTransfer = $agenticCommerceClientCriteriaTransfer->getAgenticCommerceClientConditions();

        if ($agenticCommerceClientConditionsTransfer !== null) {
            $agenticCommerceClientQuery = $this->applyClientConditions($agenticCommerceClientQuery, $agenticCommerceClientConditionsTransfer);
        }

        $agenticCommerceClientCollectionTransfer = new AgenticCommerceClientCollectionTransfer();
        $paginationTransfer = $agenticCommerceClientCriteriaTransfer->getPagination();

        if ($paginationTransfer !== null) {
            /** @var \Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery $agenticCommerceClientQuery */
            $agenticCommerceClientQuery = $this->applyPagination($agenticCommerceClientQuery, $paginationTransfer);
            $agenticCommerceClientCollectionTransfer->setPagination($paginationTransfer);
        }

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();

        foreach ($agenticCommerceClientQuery->find() as $agenticCommerceClientEntity) {
            $agenticCommerceClientCollectionTransfer->addAgenticCommerceClient(
                $agenticCommerceMapper->mapAgenticCommerceClientEntityToAgenticCommerceClientTransfer($agenticCommerceClientEntity, new AgenticCommerceClientTransfer()),
            );
        }

        return $agenticCommerceClientCollectionTransfer;
    }

    /**
     * @module Oauth
     * @module Store
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        $agenticCommerceConsentQuery = $this->createAgenticCommerceConsentQueryWithRelations();
        $agenticCommerceConsentConditionsTransfer = $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditions();

        if ($agenticCommerceConsentConditionsTransfer !== null) {
            $agenticCommerceConsentQuery = $this->applyConditions($agenticCommerceConsentQuery, $agenticCommerceConsentConditionsTransfer);
        }

        $agenticCommerceConsentQuery = $this->applySorting($agenticCommerceConsentQuery, $agenticCommerceConsentCriteriaTransfer->getSortCollection());

        $agenticCommerceConsentCollectionTransfer = new AgenticCommerceConsentCollectionTransfer();
        $paginationTransfer = $agenticCommerceConsentCriteriaTransfer->getPagination();

        if ($paginationTransfer !== null) {
            /** @var \Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery */
            $agenticCommerceConsentQuery = $this->applyPagination($agenticCommerceConsentQuery, $paginationTransfer);
            $agenticCommerceConsentCollectionTransfer->setPagination($paginationTransfer);
        }

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();

        foreach ($agenticCommerceConsentQuery->find() as $agenticCommerceConsentEntity) {
            $agenticCommerceConsentCollectionTransfer->addAgenticCommerceConsent(
                $agenticCommerceMapper->mapAgenticCommerceConsentEntityToAgenticCommerceConsentTransfer($agenticCommerceConsentEntity, new AgenticCommerceConsentTransfer()),
            );
        }

        return $agenticCommerceConsentCollectionTransfer;
    }

    public function countAgenticCommerceConsents(AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer): int
    {
        $agenticCommerceConsentQuery = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->joinAgenticCommerceClient();
        $agenticCommerceConsentConditionsTransfer = $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditions();

        if ($agenticCommerceConsentConditionsTransfer !== null) {
            $agenticCommerceConsentQuery = $this->applyConditions($agenticCommerceConsentQuery, $agenticCommerceConsentConditionsTransfer);
        }

        return $agenticCommerceConsentQuery->count();
    }

    /**
     * @module Oauth
     * @module Store
     */
    public function findAgenticCommerceConsentByUuid(string $uuid): ?AgenticCommerceConsentTransfer
    {
        $agenticCommerceConsentEntity = $this->createAgenticCommerceConsentQueryWithRelations()
            ->filterByUuid($uuid)
            ->findOne();

        if ($agenticCommerceConsentEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceConsentEntityToAgenticCommerceConsentTransfer($agenticCommerceConsentEntity, new AgenticCommerceConsentTransfer());
    }

    /**
     * @module Store
     */
    public function findAgenticCommerceCartHandoffByTokenHash(string $tokenHash): ?AgenticCommerceCartHandoffTransfer
    {
        $agenticCommerceCartHandoffEntity = $this->getFactory()
            ->createAgenticCommerceCartHandoffQuery()
            ->joinWith('AgenticCommerceConsent')
            ->joinWith('Store')
            ->filterByTokenHash($tokenHash)
            ->findOne();

        if ($agenticCommerceCartHandoffEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceCartHandoffEntityToAgenticCommerceCartHandoffTransfer($agenticCommerceCartHandoffEntity, new AgenticCommerceCartHandoffTransfer());
    }

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByPrincipal(string $principalType, string $principalReference): array
    {
        $agenticCommerceConsentQuery = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByPrincipalType($principalType)
            ->filterByPrincipalReference($principalReference);

        return $this->getActiveConsentUuids($agenticCommerceConsentQuery);
    }

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
        string $clientIdentifier,
        string $principalType,
        string $principalReference,
        int $idStore
    ): array {
        $agenticCommerceConsentQuery = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByPrincipalType($principalType)
            ->filterByPrincipalReference($principalReference)
            ->filterByFkStore($idStore)
            ->joinAgenticCommerceClient()
            ->where(sprintf('%s = ?', SpyAgenticCommerceClientTableMap::COL_FK_OAUTH_CLIENT), $clientIdentifier);

        return $this->getActiveConsentUuids($agenticCommerceConsentQuery);
    }

    public function lockAgenticCommerceConsentLockByIdCustomer(int $idCustomer): bool
    {
        $agenticCommerceConsentLockEntity = $this->getFactory()
            ->createAgenticCommerceConsentLockQuery()
            ->filterByFkCustomer($idCustomer)
            ->lockForUpdate()
            ->findOne();

        return $agenticCommerceConsentLockEntity !== null;
    }

    public function lockAgenticCommerceConsentByUuid(string $consentUuid): bool
    {
        $agenticCommerceConsentEntity = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByUuid($consentUuid)
            ->lockForUpdate()
            ->findOne();

        return $agenticCommerceConsentEntity !== null;
    }

    public function lockAgenticCommerceClientById(int $idAgenticCommerceClient): ?string
    {
        $agenticCommerceClientEntity = $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->filterByIdAgenticCommerceClient($idAgenticCommerceClient)
            ->lockForUpdate()
            ->findOne();

        return $agenticCommerceClientEntity?->getFkOauthClient();
    }

    public function getActiveAgenticCommerceConsentUuidsByClient(string $clientIdentifier): array
    {
        $agenticCommerceConsentQuery = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->joinAgenticCommerceClient()
            ->where(sprintf('%s = ?', SpyAgenticCommerceClientTableMap::COL_FK_OAUTH_CLIENT), $clientIdentifier);

        return $this->getActiveConsentUuids($agenticCommerceConsentQuery);
    }

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByCustomerReference(string $customerReference): array
    {
        $agenticCommerceConsentQuery = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByCustomerReference($customerReference);

        return $this->getActiveConsentUuids($agenticCommerceConsentQuery);
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer>
     */
    public function getAgenticCommerceConsentEvents(int $idAgenticCommerceConsent): array
    {
        $agenticCommerceConsentEventEntities = $this->getFactory()
            ->createAgenticCommerceConsentEventQuery()
            ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent)
            ->orderByIdAgenticCommerceConsentEvent(Criteria::ASC)
            ->find();

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();
        $agenticCommerceConsentEventTransfers = [];

        foreach ($agenticCommerceConsentEventEntities as $agenticCommerceConsentEventEntity) {
            $agenticCommerceConsentEventTransfers[] = $agenticCommerceMapper->mapAgenticCommerceConsentEventEntityToAgenticCommerceConsentEventTransfer(
                $agenticCommerceConsentEventEntity,
                new AgenticCommerceConsentEventTransfer(),
            );
        }

        return $agenticCommerceConsentEventTransfers;
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getAgenticCommerceConsentConstraintsByIdConsent(int $idAgenticCommerceConsent): array
    {
        return $this->mapConsentConstraints(
            $this->getFactory()
                ->createAgenticCommerceConsentConstraintQuery()
                ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent),
        );
    }

    /**
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>>
     */
    public function getAgenticCommerceConsentConstraintsGroupedByIdConsent(array $idAgenticCommerceConsents): array
    {
        if ($idAgenticCommerceConsents === []) {
            return [];
        }

        $agenticCommerceConsentConstraintEntities = $this->getFactory()
            ->createAgenticCommerceConsentConstraintQuery()
            ->filterByFkAgenticCommerceConsent_In($idAgenticCommerceConsents)
            ->orderByIdAgenticCommerceConsentConstraint(Criteria::ASC)
            ->find();

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();
        $agenticCommerceConsentConstraintTransfersByIdConsent = [];

        foreach ($agenticCommerceConsentConstraintEntities as $agenticCommerceConsentConstraintEntity) {
            $agenticCommerceConsentConstraintTransfersByIdConsent[$agenticCommerceConsentConstraintEntity->getFkAgenticCommerceConsent()][] = $agenticCommerceMapper
                ->mapAgenticCommerceConsentConstraintEntityToAgenticCommerceConsentConstraintTransfer(
                    $agenticCommerceConsentConstraintEntity,
                    new AgenticCommerceConsentConstraintTransfer(),
                );
        }

        return $agenticCommerceConsentConstraintTransfersByIdConsent;
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getAgenticCommerceConsentConstraintsByConsentUuid(string $consentUuid): array
    {
        return $this->mapConsentConstraints(
            $this->getFactory()
                ->createAgenticCommerceConsentConstraintQuery()
                ->joinAgenticCommerceConsent()
                ->where(sprintf('%s = ?', SpyAgenticCommerceConsentTableMap::COL_UUID), $consentUuid),
        );
    }

    /**
     * @param list<string> $events
     */
    public function findLatestAgenticCommerceConsentEvent(string $consentUuid, array $events): ?AgenticCommerceConsentEventTransfer
    {
        $agenticCommerceConsentEventEntity = $this->getFactory()
            ->createAgenticCommerceConsentEventQuery()
            ->filterByEvent_In($events)
            ->joinAgenticCommerceConsent()
            ->where(sprintf('%s = ?', SpyAgenticCommerceConsentTableMap::COL_UUID), $consentUuid)
            ->orderByCreatedAt(Criteria::DESC)
            ->orderByIdAgenticCommerceConsentEvent(Criteria::DESC)
            ->findOne();

        if ($agenticCommerceConsentEventEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceConsentEventEntityToAgenticCommerceConsentEventTransfer($agenticCommerceConsentEventEntity, new AgenticCommerceConsentEventTransfer());
    }

    public function findAgenticCommerceConsentEventById(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer
    {
        $agenticCommerceConsentEventEntity = $this->getFactory()
            ->createAgenticCommerceConsentEventQuery()
            ->joinWith('AgenticCommerceConsent')
            ->joinWith('AgenticCommerceConsent.AgenticCommerceClient')
            ->filterByIdAgenticCommerceConsentEvent($idAgenticCommerceConsentEvent)
            ->findOne();

        if ($agenticCommerceConsentEventEntity === null) {
            return null;
        }

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();

        return $agenticCommerceMapper->expandAgenticCommerceConsentEventTransferWithConsent(
            $agenticCommerceConsentEventEntity->getAgenticCommerceConsent(),
            $agenticCommerceMapper->mapAgenticCommerceConsentEventEntityToAgenticCommerceConsentEventTransfer($agenticCommerceConsentEventEntity, new AgenticCommerceConsentEventTransfer()),
        );
    }

    public function findAgenticCommerceSalesOrderAttribution(int $idSalesOrder): ?AgenticCommerceSalesOrderAttributionTransfer
    {
        $agenticCommerceSalesOrderEntity = $this->getFactory()
            ->createAgenticCommerceSalesOrderQuery()
            ->filterByFkSalesOrder($idSalesOrder)
            ->findOne();

        if ($agenticCommerceSalesOrderEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createAgenticCommerceMapper()
            ->mapAgenticCommerceSalesOrderEntityToAgenticCommerceSalesOrderAttributionTransfer(
                $agenticCommerceSalesOrderEntity,
                new AgenticCommerceSalesOrderAttributionTransfer(),
            );
    }

    /**
     * @module Oauth
     * @module Store
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentTransfer>
     */
    public function getAgenticCommerceConsentsPastExpiry(int $idAgenticCommerceConsentAfter, int $limit): array
    {
        $agenticCommerceConsentEntities = $this->createAgenticCommerceConsentQueryWithRelations()
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->filterByExpiresAt((new DateTime())->format(static::DATE_TIME_FORMAT), Criteria::LESS_THAN)
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsentAfter, Criteria::GREATER_THAN)
            ->orderByIdAgenticCommerceConsent(Criteria::ASC)
            ->limit($limit)
            ->find();

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();
        $agenticCommerceConsentTransfers = [];

        foreach ($agenticCommerceConsentEntities as $agenticCommerceConsentEntity) {
            $agenticCommerceConsentTransfers[] = $agenticCommerceMapper->mapAgenticCommerceConsentEntityToAgenticCommerceConsentTransfer(
                $agenticCommerceConsentEntity,
                new AgenticCommerceConsentTransfer(),
            );
        }

        return $agenticCommerceConsentTransfers;
    }

    /**
     * @param list<string> $registrationTypes
     *
     * @return list<int>
     */
    public function getAgenticCommerceClientIdsCreatedBefore(
        array $registrationTypes,
        DateTimeInterface $createdBefore,
        int $idAgenticCommerceClientAfter,
        int $limit
    ): array {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getAgenticCommerceClientIdsCreatedBefore($registrationTypes, $createdBefore, $idAgenticCommerceClientAfter, $limit);
    }

    /**
     * @param list<int> $idAgenticCommerceClients
     *
     * @return list<int>
     */
    public function getAgenticCommerceClientIdsWithConsent(array $idAgenticCommerceClients): array
    {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getAgenticCommerceClientIdsWithConsent($idAgenticCommerceClients);
    }

    /**
     * @return list<int>
     */
    public function getAgenticCommerceConsentEventIdsCreatedBefore(DateTimeInterface $createdBefore, int $idAgenticCommerceConsentEventAfter, int $limit): array
    {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getAgenticCommerceConsentEventIdsCreatedBefore($createdBefore, $idAgenticCommerceConsentEventAfter, $limit);
    }

    /**
     * @return list<int>
     */
    public function getAgenticCommerceConsentEventIdsWithClientDataCreatedBefore(
        DateTimeInterface $createdBefore,
        int $idAgenticCommerceConsentEventAfter,
        int $limit
    ): array {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getAgenticCommerceConsentEventIdsWithClientDataCreatedBefore($createdBefore, $idAgenticCommerceConsentEventAfter, $limit);
    }

    /**
     * @param list<int> $idAgenticCommerceConsentEvents
     *
     * @return list<int>
     */
    public function getNewestAgenticCommerceConsentEventIds(array $idAgenticCommerceConsentEvents): array
    {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getNewestAgenticCommerceConsentEventIds($idAgenticCommerceConsentEvents);
    }

    /**
     * @return list<int>
     */
    public function getInactiveAgenticCommerceConsentIds(DateTimeInterface $updatedBefore, int $idAgenticCommerceConsentAfter, int $limit): array
    {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getInactiveAgenticCommerceConsentIds($updatedBefore, $idAgenticCommerceConsentAfter, $limit);
    }

    /**
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, list<int>>
     */
    public function getAgenticCommerceConsentEventIdsGroupedByIdConsent(array $idAgenticCommerceConsents): array
    {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getAgenticCommerceConsentEventIdsGroupedByIdConsent($idAgenticCommerceConsents);
    }

    /**
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, string>
     */
    public function getClientNamesAtConsentIndexedByIdConsent(array $idAgenticCommerceConsents): array
    {
        if ($idAgenticCommerceConsents === []) {
            return [];
        }

        /** @var \Propel\Runtime\Collection\ArrayCollection $consentEventRows */
        $consentEventRows = $this->getFactory()
            ->createAgenticCommerceConsentEventQuery()
            ->filterByFkAgenticCommerceConsent_In($idAgenticCommerceConsents)
            ->filterByEvent_In(static::CLIENT_NAME_EVENTS)
            ->filterByClientName(null, Criteria::ISNOTNULL)
            ->orderByCreatedAt(Criteria::DESC)
            ->orderByIdAgenticCommerceConsentEvent(Criteria::DESC)
            ->select([
                SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT,
                SpyAgenticCommerceConsentEventTableMap::COL_CLIENT_NAME,
            ])
            ->find();

        return $this->getLatestClientNamesIndexedByIdConsent($consentEventRows);
    }

    /**
     * @return array<int, string>
     */
    protected function getLatestClientNamesIndexedByIdConsent(ArrayCollection $consentEventRows): array
    {
        $latestClientNamesByIdConsent = [];

        foreach ($consentEventRows as $consentEventRow) {
            $idAgenticCommerceConsent = (int)$consentEventRow[SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT];
            $latestClientNamesByIdConsent[$idAgenticCommerceConsent] ??= (string)$consentEventRow[SpyAgenticCommerceConsentEventTableMap::COL_CLIENT_NAME];
        }

        return $latestClientNamesByIdConsent;
    }

    /**
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function getReferencedConsentEventIds(array $consentEventIds): array
    {
        return $this->getFactory()->createAgenticCommerceRetentionReader()->getReferencedConsentEventIds($consentEventIds);
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    protected function mapConsentConstraints(SpyAgenticCommerceConsentConstraintQuery $agenticCommerceConsentConstraintQuery): array
    {
        $agenticCommerceConsentConstraintEntities = $agenticCommerceConsentConstraintQuery
            ->orderByIdAgenticCommerceConsentConstraint(Criteria::ASC)
            ->find();

        $agenticCommerceMapper = $this->getFactory()->createAgenticCommerceMapper();
        $agenticCommerceConsentConstraintTransfers = [];

        foreach ($agenticCommerceConsentConstraintEntities as $agenticCommerceConsentConstraintEntity) {
            $agenticCommerceConsentConstraintTransfers[] = $agenticCommerceMapper->mapAgenticCommerceConsentConstraintEntityToAgenticCommerceConsentConstraintTransfer(
                $agenticCommerceConsentConstraintEntity,
                new AgenticCommerceConsentConstraintTransfer(),
            );
        }

        return $agenticCommerceConsentConstraintTransfers;
    }

    /**
     * @return list<string>
     */
    protected function getActiveConsentUuids(SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery): array
    {
        /** @var \Propel\Runtime\Collection\ArrayCollection<int, string|null> $consentUuids */
        $consentUuids = $this->filterActive($agenticCommerceConsentQuery)
            ->select(SpyAgenticCommerceConsentTableMap::COL_UUID)
            ->find();

        return array_values(array_filter($consentUuids->toArray(), static fn (?string $consentUuid): bool => $consentUuid !== null));
    }

    protected function filterActive(SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery): SpyAgenticCommerceConsentQuery
    {
        return $agenticCommerceConsentQuery
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->filterByExpiresAt((new DateTime())->format(static::DATE_TIME_FORMAT), Criteria::GREATER_THAN);
    }

    /**
     * @module Oauth
     * @module Store
     */
    protected function createAgenticCommerceConsentQueryWithRelations(): SpyAgenticCommerceConsentQuery
    {
        return $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->joinWith('AgenticCommerceClient')
            ->joinWith('AgenticCommerceClient.OauthClient')
            ->joinWith('Store');
    }

    protected function applyConditions(
        SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery,
        AgenticCommerceConsentConditionsTransfer $agenticCommerceConsentConditionsTransfer
    ): SpyAgenticCommerceConsentQuery {
        if ($agenticCommerceConsentConditionsTransfer->getUuids() !== []) {
            $agenticCommerceConsentQuery->filterByUuid_In($agenticCommerceConsentConditionsTransfer->getUuids());
        }

        if ($agenticCommerceConsentConditionsTransfer->getCustomerReferences() !== []) {
            $agenticCommerceConsentQuery->filterByCustomerReference_In($agenticCommerceConsentConditionsTransfer->getCustomerReferences());
        }

        if ($agenticCommerceConsentConditionsTransfer->getPrincipalTypes() !== []) {
            $agenticCommerceConsentQuery->filterByPrincipalType_In($agenticCommerceConsentConditionsTransfer->getPrincipalTypes());
        }

        if ($agenticCommerceConsentConditionsTransfer->getPrincipalReferences() !== []) {
            $agenticCommerceConsentQuery->filterByPrincipalReference_In($agenticCommerceConsentConditionsTransfer->getPrincipalReferences());
        }

        if ($agenticCommerceConsentConditionsTransfer->getClientIdentifiers() !== []) {
            $agenticCommerceConsentQuery->addAnd(
                SpyAgenticCommerceClientTableMap::COL_FK_OAUTH_CLIENT,
                $agenticCommerceConsentConditionsTransfer->getClientIdentifiers(),
                Criteria::IN,
            );
        }

        if ($agenticCommerceConsentConditionsTransfer->getStatuses() !== []) {
            $agenticCommerceConsentQuery = $this->applyEffectiveStatusCondition($agenticCommerceConsentQuery, $agenticCommerceConsentConditionsTransfer->getStatuses());
        }

        return $agenticCommerceConsentQuery;
    }

    /**
     * @param array<string> $statuses
     */
    protected function applyEffectiveStatusCondition(
        SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery,
        array $statuses
    ): SpyAgenticCommerceConsentQuery {
        // A consent stays `active` in storage past `expires_at`; filtering on the stored value alone would hand out expired consents as active.
        $now = (new DateTime())->format(static::DATE_TIME_FORMAT);
        $conditionNames = [];

        foreach (array_values(array_unique($statuses)) as $index => $status) {
            $storedStatusConditionName = sprintf('stored_status_%d', $index);
            $effectiveConditionName = sprintf('effective_%d', $index);
            $agenticCommerceConsentQuery->condition($storedStatusConditionName, sprintf('%s = ?', SpyAgenticCommerceConsentTableMap::COL_STATUS), $status);

            if ($status === AgenticCommerceConfig::CONSENT_STATUS_ACTIVE) {
                $agenticCommerceConsentQuery
                    ->condition(sprintf('not_expired_%d', $index), sprintf('%s > ?', SpyAgenticCommerceConsentTableMap::COL_EXPIRES_AT), $now)
                    ->combine([$storedStatusConditionName, sprintf('not_expired_%d', $index)], Criteria::LOGICAL_AND, $effectiveConditionName);
                $conditionNames[] = $effectiveConditionName;

                continue;
            }

            if ($status === AgenticCommerceConfig::CONSENT_STATUS_EXPIRED) {
                $agenticCommerceConsentQuery
                    ->condition(sprintf('stored_active_%d', $index), sprintf('%s = ?', SpyAgenticCommerceConsentTableMap::COL_STATUS), AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
                    ->condition(sprintf('past_end_%d', $index), sprintf('%s <= ?', SpyAgenticCommerceConsentTableMap::COL_EXPIRES_AT), $now)
                    ->combine([sprintf('stored_active_%d', $index), sprintf('past_end_%d', $index)], Criteria::LOGICAL_AND, sprintf('time_expired_%d', $index))
                    ->combine([$storedStatusConditionName, sprintf('time_expired_%d', $index)], Criteria::LOGICAL_OR, $effectiveConditionName);
                $conditionNames[] = $effectiveConditionName;

                continue;
            }

            $conditionNames[] = $storedStatusConditionName;
        }

        return $agenticCommerceConsentQuery->where($conditionNames, Criteria::LOGICAL_OR);
    }

    protected function applyClientConditions(
        SpyAgenticCommerceClientQuery $agenticCommerceClientQuery,
        AgenticCommerceClientConditionsTransfer $agenticCommerceClientConditionsTransfer
    ): SpyAgenticCommerceClientQuery {
        if ($agenticCommerceClientConditionsTransfer->getClientIdentifiers() !== []) {
            $agenticCommerceClientQuery->filterByFkOauthClient_In($agenticCommerceClientConditionsTransfer->getClientIdentifiers());
        }

        if ($agenticCommerceClientConditionsTransfer->getStatuses() !== []) {
            $agenticCommerceClientQuery->filterByStatus_In($agenticCommerceClientConditionsTransfer->getStatuses());
        }

        if ($agenticCommerceClientConditionsTransfer->getRegistrationTypes() !== []) {
            $agenticCommerceClientQuery->filterByRegistrationType_In($agenticCommerceClientConditionsTransfer->getRegistrationTypes());
        }

        if ($agenticCommerceClientConditionsTransfer->getSoftwareIds() !== []) {
            $agenticCommerceClientQuery->filterBySoftwareId_In($agenticCommerceClientConditionsTransfer->getSoftwareIds());
        }

        if ($agenticCommerceClientConditionsTransfer->getBlockedByValues() !== []) {
            $agenticCommerceClientQuery->filterByBlockedBy_In($agenticCommerceClientConditionsTransfer->getBlockedByValues());
        }

        if ($agenticCommerceClientConditionsTransfer->getRedirectHosts() !== []) {
            $agenticCommerceClientQuery->filterByIdAgenticCommerceClient_In(
                $this->getIdAgenticCommerceClientsByRedirectHosts(array_values($agenticCommerceClientConditionsTransfer->getRedirectHosts())),
            );
        }

        return $agenticCommerceClientQuery;
    }

    /**
     * Matches the normalized selectors (see `AgenticCommerceServiceInterface::findNormalizedHost()`) against the normalized
     * redirect hosts stored with each client, because a stored URI keeps the spelling it was registered with: upper case,
     * a trailing dot, percent-encoding or Unicode. A selector that is not a valid host matches nothing.
     *
     * @param list<string> $redirectHosts
     *
     * @return list<int>
     */
    protected function getIdAgenticCommerceClientsByRedirectHosts(array $redirectHosts): array
    {
        $redirectHosts = $this->getFactory()->getAgenticCommerceService()->normalizeHosts($redirectHosts);

        if ($redirectHosts === []) {
            return [];
        }

        $idAgenticCommerceClients = $this->getFactory()
            ->createAgenticCommerceClientRedirectHostQuery()
            ->filterByHost_In($redirectHosts)
            ->select([SpyAgenticCommerceClientRedirectHostTableMap::COL_FK_AGENTIC_COMMERCE_CLIENT])
            ->distinct()
            ->find()
            ->getData();

        return array_values(array_map('intval', $idAgenticCommerceClients));
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\SortTransfer> $sortTransfers
     */
    protected function applySorting(
        SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery,
        ArrayObject $sortTransfers
    ): SpyAgenticCommerceConsentQuery {
        $isSorted = false;

        foreach ($sortTransfers as $sortTransfer) {
            $column = static::SORTABLE_FIELDS[$sortTransfer->getFieldOrFail()] ?? null;

            if ($column === null) {
                continue;
            }

            $agenticCommerceConsentQuery->orderBy($column, $sortTransfer->getIsAscending() ? Criteria::ASC : Criteria::DESC);
            $isSorted = true;
        }

        if (!$isSorted) {
            $agenticCommerceConsentQuery->orderBy(SpyAgenticCommerceConsentTableMap::COL_CREATED_AT, Criteria::DESC);
        }

        return $agenticCommerceConsentQuery;
    }

    protected function applyPagination(
        ModelCriteria $modelCriteria,
        PaginationTransfer $paginationTransfer
    ): ModelCriteria {
        if ($paginationTransfer->getOffset() !== null && $paginationTransfer->getLimit() !== null) {
            $paginationTransfer->setNbResults($modelCriteria->count());

            return $modelCriteria
                ->offset($paginationTransfer->getOffsetOrFail())
                ->setLimit($paginationTransfer->getLimitOrFail());
        }

        if ($paginationTransfer->getPage() !== null && $paginationTransfer->getMaxPerPage() !== null) {
            $propelModelPager = $modelCriteria->paginate(
                $paginationTransfer->getPageOrFail(),
                $paginationTransfer->getMaxPerPageOrFail(),
            );

            $paginationTransfer->setNbResults($propelModelPager->getNbResults())
                ->setFirstIndex($propelModelPager->getFirstIndex())
                ->setLastIndex($propelModelPager->getLastIndex())
                ->setFirstPage($propelModelPager->getFirstPage())
                ->setLastPage($propelModelPager->getLastPage())
                ->setNextPage($propelModelPager->getNextPage())
                ->setPreviousPage($propelModelPager->getPreviousPage());

            return $propelModelPager->getQuery();
        }

        return $modelCriteria;
    }

    /**
     * Propel writes `created_at` in the PHP default timezone, so the period start is compared in that timezone.
     *
     * @module Sales
     * @module Oms
     *
     * @param list<string> $canceledOmsStateNames
     *
     * @return array<int, int>
     */
    public function getAgenticCommerceAttributedGrandTotalsIndexedByIdSalesOrder(
        int $idAgenticCommerceConsent,
        DateTimeImmutable $placedSince,
        array $canceledOmsStateNames
    ): array {
        $agenticCommerceSalesOrderQuery = $this->getFactory()->createAgenticCommerceSalesOrderQuery();
        $agenticCommerceSalesOrderQuery
            ->useAgenticCommerceConsentEventQuery(null, Criteria::INNER_JOIN)
                ->filterByFkAgenticCommerceConsent($idAgenticCommerceConsent)
            ->endUse();
        /** @var list<int> $idSalesOrders */
        $idSalesOrders = $agenticCommerceSalesOrderQuery
            ->useSpySalesOrderQuery()
                ->filterByCreatedAt($placedSince->setTimezone(new DateTimeZone(date_default_timezone_get()))->format(static::DATE_TIME_FORMAT), Criteria::GREATER_EQUAL)
                ->useItemQuery()
                    ->useStateQuery()
                        ->filterByName($canceledOmsStateNames, Criteria::NOT_IN)
                    ->endUse()
                ->endUse()
            ->endUse()
            ->select([SpyAgenticCommerceSalesOrderTableMap::COL_FK_SALES_ORDER])
            ->distinct()
            ->find()
            ->getData();

        if ($idSalesOrders === []) {
            return [];
        }

        $grandTotalsByIdSalesOrder = [];
        $salesOrderTotalsEntities = $this->getFactory()
            ->createSalesOrderTotalsQuery()
            ->filterByFkSalesOrder_In($idSalesOrders)
            ->orderByIdSalesOrderTotals(Criteria::ASC)
            ->find();

        foreach ($salesOrderTotalsEntities as $salesOrderTotalsEntity) {
            $grandTotalsByIdSalesOrder[$salesOrderTotalsEntity->getFkSalesOrder()] = (int)$salesOrderTotalsEntity->getGrandTotal();
        }

        return $grandTotalsByIdSalesOrder;
    }
}
