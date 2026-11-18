<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceClientTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentEventTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentTableMap;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceSalesOrderTableMap;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEventQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

class AgenticCommerceRetentionReader implements AgenticCommerceRetentionReaderInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * The events that carry the client name; the Back Office block of an order falls back to the latest of them.
     *
     * @var list<string>
     */
    public const array CLIENT_NAME_EVENTS = [AgenticCommerceConfig::CONSENT_EVENT_GRANTED, AgenticCommerceConfig::CONSENT_EVENT_RE_CONSENTED];

    public function __construct(protected AgenticCommercePersistenceFactory $persistenceFactory)
    {
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
        return $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceClientQuery()
                ->filterByRegistrationType_In($registrationTypes)
                ->filterByCreatedAt($this->formatDateTime($createdBefore), Criteria::LESS_THAN)
                ->filterByIdAgenticCommerceClient($idAgenticCommerceClientAfter, Criteria::GREATER_THAN)
                ->orderByIdAgenticCommerceClient(Criteria::ASC)
                ->limit($limit),
            SpyAgenticCommerceClientTableMap::COL_ID_AGENTIC_COMMERCE_CLIENT,
        );
    }

    /**
     * @param list<int> $idAgenticCommerceClients
     *
     * @return list<int>
     */
    public function getAgenticCommerceClientIdsWithConsent(array $idAgenticCommerceClients): array
    {
        if ($idAgenticCommerceClients === []) {
            return [];
        }

        return $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentQuery()
                ->filterByFkAgenticCommerceClient_In($idAgenticCommerceClients)
                ->distinct(),
            SpyAgenticCommerceConsentTableMap::COL_FK_AGENTIC_COMMERCE_CLIENT,
        );
    }

    /**
     * @return list<int>
     */
    public function getAgenticCommerceConsentEventIdsCreatedBefore(DateTimeInterface $createdBefore, int $idAgenticCommerceConsentEventAfter, int $limit): array
    {
        return $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByCreatedAt($this->formatDateTime($createdBefore), Criteria::LESS_THAN)
                ->filterByIdAgenticCommerceConsentEvent($idAgenticCommerceConsentEventAfter, Criteria::GREATER_THAN)
                ->orderByIdAgenticCommerceConsentEvent(Criteria::ASC)
                ->limit($limit),
            SpyAgenticCommerceConsentEventTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT_EVENT,
        );
    }

    /**
     * @return list<int>
     */
    public function getAgenticCommerceConsentEventIdsWithClientDataCreatedBefore(
        DateTimeInterface $createdBefore,
        int $idAgenticCommerceConsentEventAfter,
        int $limit
    ): array {
        return $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByCreatedAt($this->formatDateTime($createdBefore), Criteria::LESS_THAN)
                ->filterByIdAgenticCommerceConsentEvent($idAgenticCommerceConsentEventAfter, Criteria::GREATER_THAN)
                ->condition('hasIpAddress', SpyAgenticCommerceConsentEventTableMap::COL_IP_ADDRESS . ' IS NOT NULL')
                ->condition('hasUserAgent', SpyAgenticCommerceConsentEventTableMap::COL_USER_AGENT . ' IS NOT NULL')
                ->where(['hasIpAddress', 'hasUserAgent'], Criteria::LOGICAL_OR)
                ->orderByIdAgenticCommerceConsentEvent(Criteria::ASC)
                ->limit($limit),
            SpyAgenticCommerceConsentEventTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT_EVENT,
        );
    }

    /**
     * @param list<int> $idAgenticCommerceConsentEvents
     *
     * @return list<int>
     */
    public function getNewestAgenticCommerceConsentEventIds(array $idAgenticCommerceConsentEvents): array
    {
        if ($idAgenticCommerceConsentEvents === []) {
            return [];
        }

        $idAgenticCommerceConsents = $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByIdAgenticCommerceConsentEvent_In($idAgenticCommerceConsentEvents)
                ->distinct(),
            SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT,
        );

        $newestIdAgenticCommerceConsentEvents = array_values($this->getLatestConsentEventIdsByIdConsent(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByFkAgenticCommerceConsent_In($idAgenticCommerceConsents),
        ));

        return array_values(array_intersect($idAgenticCommerceConsentEvents, $newestIdAgenticCommerceConsentEvents));
    }

    /**
     * @return list<int>
     */
    public function getInactiveAgenticCommerceConsentIds(DateTimeInterface $updatedBefore, int $idAgenticCommerceConsentAfter, int $limit): array
    {
        return $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentQuery()
                ->filterByStatus_In([AgenticCommerceConfig::CONSENT_STATUS_REVOKED, AgenticCommerceConfig::CONSENT_STATUS_EXPIRED])
                ->filterByUpdatedAt($this->formatDateTime($updatedBefore), Criteria::LESS_THAN)
                ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsentAfter, Criteria::GREATER_THAN)
                ->orderByIdAgenticCommerceConsent(Criteria::ASC)
                ->limit($limit),
            SpyAgenticCommerceConsentTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT,
        );
    }

    /**
     * @param list<int> $idAgenticCommerceConsents
     *
     * @return array<int, list<int>>
     */
    public function getAgenticCommerceConsentEventIdsGroupedByIdConsent(array $idAgenticCommerceConsents): array
    {
        if ($idAgenticCommerceConsents === []) {
            return [];
        }

        $consentEventRows = $this->persistenceFactory
            ->createAgenticCommerceConsentEventQuery()
            ->filterByFkAgenticCommerceConsent_In($idAgenticCommerceConsents)
            ->select([
                SpyAgenticCommerceConsentEventTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT_EVENT,
                SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT,
            ])
            ->find();

        $idAgenticCommerceConsentEventsByIdConsent = [];

        foreach ($consentEventRows as $consentEventRow) {
            $idAgenticCommerceConsentEventsByIdConsent[(int)$consentEventRow[SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT]][]
                = (int)$consentEventRow[SpyAgenticCommerceConsentEventTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT_EVENT];
        }

        return $idAgenticCommerceConsentEventsByIdConsent;
    }

    /**
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function getReferencedConsentEventIds(array $consentEventIds): array
    {
        if ($consentEventIds === []) {
            return [];
        }

        $referencedConsentEventIds = $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceSalesOrderQuery()
                ->filterByFkAgenticCommerceConsentEvent_In($consentEventIds)
                ->distinct(),
            SpyAgenticCommerceSalesOrderTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT_EVENT,
        );

        return array_values(array_unique(array_merge(
            $referencedConsentEventIds,
            $this->getClientNameFallbackConsentEventIds($consentEventIds),
        )));
    }

    /**
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    protected function getClientNameFallbackConsentEventIds(array $consentEventIds): array
    {
        $idAgenticCommerceConsentsOfCandidates = $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByIdAgenticCommerceConsentEvent_In($consentEventIds)
                ->filterByEvent_In(static::CLIENT_NAME_EVENTS)
                ->distinct(),
            SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT,
        );

        if ($idAgenticCommerceConsentsOfCandidates === []) {
            return [];
        }

        $idAgenticCommerceConsentsWithReferencedEventWithoutClientName = $this->findIds(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByFkAgenticCommerceConsent_In($idAgenticCommerceConsentsOfCandidates)
                ->filterByClientName(null, Criteria::ISNULL)
                ->joinAgenticCommerceSalesOrder(null, Criteria::INNER_JOIN)
                ->distinct(),
            SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT,
        );

        if ($idAgenticCommerceConsentsWithReferencedEventWithoutClientName === []) {
            return [];
        }

        $fallbackConsentEventIds = $this->getLatestConsentEventIdsByIdConsent(
            $this->persistenceFactory
                ->createAgenticCommerceConsentEventQuery()
                ->filterByFkAgenticCommerceConsent_In($idAgenticCommerceConsentsWithReferencedEventWithoutClientName)
                ->filterByEvent_In(static::CLIENT_NAME_EVENTS),
        );

        return array_values(array_intersect($consentEventIds, $fallbackConsentEventIds));
    }

    /**
     * Selects the id column on the query and returns its values as integers; Propel returns a `BIGINT` as a string.
     *
     * @return list<int>
     */
    protected function findIds(ModelCriteria $modelCriteria, string $idColumn): array
    {
        /** @var \Propel\Runtime\Collection\ArrayCollection<int, int|string> $ids */
        $ids = $modelCriteria->select($idColumn)->find();

        return array_map('intval', array_values($ids->toArray()));
    }

    protected function formatDateTime(DateTimeInterface $dateTime): string
    {
        return DateTimeImmutable::createFromInterface($dateTime)
            ->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format(static::DATE_TIME_FORMAT);
    }

    /**
     * Returns the id of the latest event of every consent the query matches, ordered as `findLatestAgenticCommerceConsentEvent()` orders: `created_at`, then id, both descending.
     *
     * @return array<int, int>
     */
    protected function getLatestConsentEventIdsByIdConsent(SpyAgenticCommerceConsentEventQuery $agenticCommerceConsentEventQuery): array
    {
        $consentEventRows = $agenticCommerceConsentEventQuery
            ->orderByCreatedAt(Criteria::DESC)
            ->orderByIdAgenticCommerceConsentEvent(Criteria::DESC)
            ->select([
                SpyAgenticCommerceConsentEventTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT_EVENT,
                SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT,
            ])
            ->find();

        $latestIdAgenticCommerceConsentEventsByIdConsent = [];

        foreach ($consentEventRows as $consentEventRow) {
            $idAgenticCommerceConsent = (int)$consentEventRow[SpyAgenticCommerceConsentEventTableMap::COL_FK_AGENTIC_COMMERCE_CONSENT];
            $latestIdAgenticCommerceConsentEventsByIdConsent[$idAgenticCommerceConsent] ??= (int)$consentEventRow[SpyAgenticCommerceConsentEventTableMap::COL_ID_AGENTIC_COMMERCE_CONSENT_EVENT];
        }

        return $latestIdAgenticCommerceConsentEventsByIdConsent;
    }
}
