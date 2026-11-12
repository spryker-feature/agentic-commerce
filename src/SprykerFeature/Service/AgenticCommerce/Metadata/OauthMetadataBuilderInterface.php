<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Metadata;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationServerMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceMetadataTransfer;

interface OauthMetadataBuilderInterface
{
    public function buildProtectedResourceMetadata(): AgenticCommerceProtectedResourceMetadataTransfer;

    public function buildAuthorizationServerMetadata(): AgenticCommerceAuthorizationServerMetadataTransfer;
}
