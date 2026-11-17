<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin;

/**
 * Use this plugin to keep delegated-access consent events that something outside the consent part references, for example an order.
 */
interface AgenticCommerceConsentEventRetentionPluginInterface
{
    /**
     * Specification:
     * - Receives candidate consent event ids that housekeeping would prune.
     * - Returns the subset that must be kept because something outside the module references it, for example an order.
     *
     * @api
     *
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function filterRetainedConsentEventIds(array $consentEventIds): array;
}
