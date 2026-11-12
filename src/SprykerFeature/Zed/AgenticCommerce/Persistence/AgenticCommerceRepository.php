<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use ArrayObject;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceClientTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentTableMap;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommercePersistenceFactory getFactory()
 */
class AgenticCommerceRepository extends AbstractRepository implements AgenticCommerceRepositoryInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

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
        SpyAgenticCommerceConsentQuery $agenticCommerceConsentQuery,
        PaginationTransfer $paginationTransfer
    ): SpyAgenticCommerceConsentQuery {
        if ($paginationTransfer->getOffset() !== null && $paginationTransfer->getLimit() !== null) {
            $paginationTransfer->setNbResults($agenticCommerceConsentQuery->count());

            return $agenticCommerceConsentQuery
                ->offset($paginationTransfer->getOffsetOrFail())
                ->setLimit($paginationTransfer->getLimitOrFail());
        }

        if ($paginationTransfer->getPage() !== null && $paginationTransfer->getMaxPerPage() !== null) {
            $propelModelPager = $agenticCommerceConsentQuery->paginate(
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

            /** @var \Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery $paginatedQuery */
            $paginatedQuery = $propelModelPager->getQuery();

            return $paginatedQuery;
        }

        return $agenticCommerceConsentQuery;
    }
}
