<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

interface ProtectedResourceDeclarationBuilderInterface
{
    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function buildMcpProtectedResources(): array;

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>
     */
    public function buildPrincipalProtectedResources(): array;
}
