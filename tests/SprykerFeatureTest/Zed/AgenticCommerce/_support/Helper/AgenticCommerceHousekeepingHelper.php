<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Helper;

use Codeception\Module;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEvent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEventQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use PHPUnit\Framework\AssertionFailedError;
use Propel\Runtime\ActiveQuery\Criteria;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentEventRetentionPluginInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManager;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepository;
use SprykerFeature\Zed\AgenticCommerce\Persistence\Propel\Mapper\AgenticCommerceMapper;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerTest\Shared\Sales\Helper\SalesDataHelper;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Zed\Oms\Helper\OmsHelper;

class AgenticCommerceHousekeepingHelper extends Module
{
    use DependencyHelperTrait;

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const string IP_ADDRESS = '203.0.113.7';

    public const string USER_AGENT = 'Agent/1.0';

    protected const string STATE_MACHINE_PROCESS_NAME = 'Test01';

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentEventRetentionPluginInterface> $consentEventRetentionPlugins
     */
    public function setConsentEventRetentionPlugins(array $consentEventRetentionPlugins): void
    {
        $this->getDependencyHelper()->setDependency(
            AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_EVENT_RETENTION,
            $consentEventRetentionPlugins,
        );
    }

    /**
     * @param list<int> $retainedConsentEventIds
     */
    public function createRetentionPlugin(array $retainedConsentEventIds): AgenticCommerceConsentEventRetentionPluginInterface
    {
        return new class ($retainedConsentEventIds) implements AgenticCommerceConsentEventRetentionPluginInterface {
            /**
             * @param list<int> $retainedConsentEventIds
             */
            public function __construct(protected array $retainedConsentEventIds)
            {
            }

            public function filterRetainedConsentEventIds(array $consentEventIds): array
            {
                return array_values(array_intersect($consentEventIds, $this->retainedConsentEventIds));
            }
        };
    }

    public function haveRevokedConsentUpdatedAt(string $updatedAt): AgenticCommerceConsentTransfer
    {
        $agenticCommerceConsentTransfer = $this->getAgenticCommerceHelper()->haveAgenticCommerceConsent([
            AgenticCommerceConsentTransfer::STATUS => AgenticCommerceConfig::CONSENT_STATUS_REVOKED,
        ]);

        SpyAgenticCommerceConsentQuery::create()
            ->filterByIdAgenticCommerceConsent($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail())
            ->update(['UpdatedAt' => $this->formatRelativeDate($updatedAt)]);

        return $agenticCommerceConsentTransfer;
    }

    /**
     * @param list<string> $createdAts
     *
     * @return list<int>
     */
    public function haveConsentEventsAt(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, array $createdAts): array
    {
        $idAgenticCommerceConsentEvents = [];

        foreach ($createdAts as $createdAt) {
            $idAgenticCommerceConsentEvents[] = $this->haveConsentEventAt(
                $agenticCommerceConsentTransfer,
                AgenticCommerceConfig::CONSENT_EVENT_GRANTED,
                $createdAt,
                AgenticCommerceHelper::CLIENT_NAME,
            );
        }

        return $idAgenticCommerceConsentEvents;
    }

    public function haveConsentEventAt(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        string $event,
        string $createdAt,
        ?string $clientName = null
    ): int {
        $agenticCommerceConsentEventEntity = (new SpyAgenticCommerceConsentEvent())
            ->setFkAgenticCommerceConsent($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail())
            ->setEvent($event)
            ->setActor(AgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER)
            ->setScopes(json_encode($agenticCommerceConsentTransfer->getGrantedScopes(), JSON_THROW_ON_ERROR))
            ->setClientName($clientName)
            ->setCreatedAt($this->formatRelativeDate($createdAt));
        $agenticCommerceConsentEventEntity->save();

        return (int)$agenticCommerceConsentEventEntity->getIdAgenticCommerceConsentEvent();
    }

    /**
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function haveConsentEventWithClientDataAt(string $createdAt): AgenticCommerceConsentEventTransfer
    {
        $agenticCommerceConsentTransfer = $this->getAgenticCommerceHelper()->haveAgenticCommerceConsent();
        $idAgenticCommerceConsentEvent = $this->haveConsentEventAt($agenticCommerceConsentTransfer, AgenticCommerceConfig::CONSENT_EVENT_GRANTED, $createdAt);

        SpyAgenticCommerceConsentEventQuery::create()
            ->filterByIdAgenticCommerceConsentEvent($idAgenticCommerceConsentEvent)
            ->update(['IpAddress' => static::IP_ADDRESS, 'UserAgent' => static::USER_AGENT]);

        return $this->findConsentEvent($idAgenticCommerceConsentEvent) ?? throw new AssertionFailedError('The seeded consent event is missing.');
    }

    public function getOldestConsentEventId(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer): int
    {
        return (int)SpyAgenticCommerceConsentEventQuery::create()
            ->filterByFkAgenticCommerceConsent($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail())
            ->orderByCreatedAt(Criteria::ASC)
            ->orderByIdAgenticCommerceConsentEvent(Criteria::ASC)
            ->findOne()
            ?->getIdAgenticCommerceConsentEvent();
    }

    public function haveOrderAttributedToConsentEvent(int $idAgenticCommerceConsentEvent): int
    {
        $this->getOmsHelper()->configureTestStateMachine([static::STATE_MACHINE_PROCESS_NAME]);
        $idSalesOrder = $this->getSalesDataHelper()->haveOrder([], static::STATE_MACHINE_PROCESS_NAME)->getIdSalesOrderOrFail();

        (new AgenticCommerceEntityManager())->createAgenticCommerceSalesOrderAttribution($idSalesOrder, $idAgenticCommerceConsentEvent);

        return $idSalesOrder;
    }

    public function findAttributedConsentEventId(int $idSalesOrder): ?int
    {
        return (new AgenticCommerceRepository())
            ->findAgenticCommerceSalesOrderAttribution($idSalesOrder)
            ?->getConsentEvent()
            ?->getIdAgenticCommerceConsentEvent();
    }

    public function hasOauthClient(string $clientIdentifier): bool
    {
        return SpyOauthClientQuery::create()->filterByIdentifier($clientIdentifier)->exists();
    }

    public function findConsentByUuidOrNull(string $consentUuid): ?AgenticCommerceConsentTransfer
    {
        return (new AgenticCommerceRepository())->findAgenticCommerceConsentByUuid($consentUuid);
    }

    public function findConsentEvent(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer
    {
        $agenticCommerceConsentEventEntity = SpyAgenticCommerceConsentEventQuery::create()
            ->filterByIdAgenticCommerceConsentEvent($idAgenticCommerceConsentEvent)
            ->findOne();

        if ($agenticCommerceConsentEventEntity === null) {
            return null;
        }

        return (new AgenticCommerceMapper())->mapAgenticCommerceConsentEventEntityToAgenticCommerceConsentEventTransfer(
            $agenticCommerceConsentEventEntity,
            new AgenticCommerceConsentEventTransfer(),
        );
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer>
     */
    public function getConsentEventsOfType(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, string $event): array
    {
        $agenticCommerceConsentEventTransfers = (new AgenticCommerceRepository())->getAgenticCommerceConsentEvents(
            $agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail(),
        );

        return array_values(array_filter(
            $agenticCommerceConsentEventTransfers,
            static fn (AgenticCommerceConsentEventTransfer $agenticCommerceConsentEventTransfer): bool => $agenticCommerceConsentEventTransfer->getEvent() === $event,
        ));
    }

    protected function formatRelativeDate(string $relativeDate): string
    {
        return (new DateTime($relativeDate))->format(static::DATE_TIME_FORMAT);
    }

    protected function getAgenticCommerceHelper(): AgenticCommerceHelper
    {
        /** @var \SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper $agenticCommerceHelper */
        $agenticCommerceHelper = $this->getModule('\\' . AgenticCommerceHelper::class);

        return $agenticCommerceHelper;
    }

    protected function getSalesDataHelper(): SalesDataHelper
    {
        /** @var \SprykerTest\Shared\Sales\Helper\SalesDataHelper $salesDataHelper */
        $salesDataHelper = $this->getModule('\\' . SalesDataHelper::class);

        return $salesDataHelper;
    }

    protected function getOmsHelper(): OmsHelper
    {
        /** @var \SprykerTest\Zed\Oms\Helper\OmsHelper $omsHelper */
        $omsHelper = $this->getModule('\\' . OmsHelper::class);

        return $omsHelper;
    }
}
