<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

class ClientMetadataDocumentFailureCache implements ClientMetadataDocumentFailureCacheInterface
{
    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function isFailureCached(string $clientIdentifier): bool
    {
        if ($this->agenticCommerceConfig->getClientIdMetadataDocumentFailureCacheTtlSeconds() <= 0) {
            return false;
        }

        return $this->agenticCommerceClient->hasAgenticCommerceMetadataDocumentFailure($clientIdentifier);
    }

    public function cacheFailure(string $clientIdentifier): void
    {
        $ttlSeconds = $this->agenticCommerceConfig->getClientIdMetadataDocumentFailureCacheTtlSeconds();

        if ($ttlSeconds <= 0) {
            return;
        }

        $this->agenticCommerceClient->saveAgenticCommerceMetadataDocumentFailure($clientIdentifier, $ttlSeconds);
    }
}
