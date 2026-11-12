<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use DateTime;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Orm\Zed\AgenticCommerce\Persistence\Map\SpyAgenticCommerceConsentTableMap;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClient;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentEvent;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommercePersistenceFactory getFactory()
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

        $agenticCommerceClientEntity->save();

        return $agenticCommerceClientTransfer->setIdAgenticCommerceClient($agenticCommerceClientEntity->getIdAgenticCommerceClient());
    }

    public function updateAgenticCommerceClientStatus(string $clientIdentifier, string $status): bool
    {
        $affectedRows = $this->getFactory()
            ->createAgenticCommerceClientQuery()
            ->filterByFkOauthClient($clientIdentifier)
            ->filterByStatus($status, Criteria::NOT_EQUAL)
            ->update(['Status' => $status, 'UpdatedAt' => $this->formatNow()]);

        return $affectedRows === 1;
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
        $affectedRows = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->update([
                'Status' => AgenticCommerceConfig::CONSENT_STATUS_REVOKED,
                'RevokedAt' => $this->formatNow(),
                'RevokedBy' => $revokedBy,
                'RefreshTokenIdentifier' => null,
                'UpdatedAt' => $this->formatNow(),
            ]);

        return $affectedRows === 1;
    }

    public function updateAgenticCommerceConsentLastUsedAt(int $idAgenticCommerceConsent, int $writeIntervalInSeconds): void
    {
        $writeIntervalStart = (new DateTime())
            ->modify(sprintf('-%d seconds', $writeIntervalInSeconds))
            ->format(static::DATE_TIME_FORMAT);

        $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->condition(static::CONDITION_NEVER_USED, sprintf('%s IS NULL', SpyAgenticCommerceConsentTableMap::COL_LAST_USED_AT))
            ->condition(static::CONDITION_USED_BEFORE_WRITE_INTERVAL, sprintf('%s <= ?', SpyAgenticCommerceConsentTableMap::COL_LAST_USED_AT), $writeIntervalStart)
            ->where([static::CONDITION_NEVER_USED, static::CONDITION_USED_BEFORE_WRITE_INTERVAL], Criteria::LOGICAL_OR)
            ->update(['LastUsedAt' => $this->formatNow(), 'UpdatedAt' => $this->formatNow()]);
    }

    public function updateAgenticCommerceConsentRefreshTokenIdentifier(
        int $idAgenticCommerceConsent,
        ?string $currentRefreshTokenIdentifier,
        string $newRefreshTokenIdentifier
    ): bool {
        $affectedRows = $this->getFactory()
            ->createAgenticCommerceConsentQuery()
            ->filterByIdAgenticCommerceConsent($idAgenticCommerceConsent)
            ->filterByStatus(AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->filterByExpiresAt($this->formatNow(), Criteria::GREATER_THAN)
            ->filterByRefreshTokenIdentifier($currentRefreshTokenIdentifier, $currentRefreshTokenIdentifier === null ? Criteria::ISNULL : Criteria::EQUAL)
            ->update(['RefreshTokenIdentifier' => $newRefreshTokenIdentifier, 'UpdatedAt' => $this->formatNow()]);

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

    protected function formatNow(): string
    {
        return (new DateTime())->format(static::DATE_TIME_FORMAT);
    }
}
