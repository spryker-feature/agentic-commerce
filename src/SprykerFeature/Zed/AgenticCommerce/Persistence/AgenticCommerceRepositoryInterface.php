<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Persistence;

use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;

interface AgenticCommerceRepositoryInterface
{
    public function findAgenticCommerceClientByClientIdentifier(string $clientIdentifier): ?AgenticCommerceClientTransfer;

    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer;

    public function findAgenticCommerceConsentByUuid(string $uuid): ?AgenticCommerceConsentTransfer;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByPrincipal(string $principalType, string $principalReference): array;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
        string $clientIdentifier,
        string $principalType,
        string $principalReference,
        int $idStore
    ): array;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByClient(string $clientIdentifier): array;

    /**
     * @return list<string>
     */
    public function getActiveAgenticCommerceConsentUuidsByCustomerReference(string $customerReference): array;

    /**
     * Returns the events oldest first.
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer>
     */
    public function getAgenticCommerceConsentEvents(int $idAgenticCommerceConsent): array;
}
