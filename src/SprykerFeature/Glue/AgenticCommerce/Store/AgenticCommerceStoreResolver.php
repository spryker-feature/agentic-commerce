<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Store;

use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceBearerTokenExtractor;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommercePathMatcher;
use Symfony\Component\HttpFoundation\Request;

class AgenticCommerceStoreResolver implements AgenticCommerceStoreResolverInterface
{
    /**
     * @uses \Spryker\Glue\StoresApi\Processor\Resolver\StoreResolver::HEADER_STORE_NAME
     */
    protected const string HEADER_STORE_NAME = 'Store';

    /**
     * @uses \Spryker\Glue\StoresApi\Processor\Resolver\StoreResolver::PARAMETER_STORE_NAME
     */
    protected const string PARAMETER_STORE_NAME = '_store';

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected AgenticCommerceBearerTokenExtractor $agenticCommerceBearerTokenExtractor,
        protected AgenticCommercePathMatcher $agenticCommercePathMatcher
    ) {
    }

    public function findTokenStoreNameWhenRequestNamesNoStore(Request $request): ?string
    {
        if (!$this->agenticCommercePathMatcher->isDelegatedAccessPath($request)) {
            return null;
        }

        if ($request->query->has(static::PARAMETER_STORE_NAME) || $request->headers->has(static::HEADER_STORE_NAME)) {
            return null;
        }

        $accessToken = $this->agenticCommerceBearerTokenExtractor->findBearerToken($request);

        if ($accessToken === null) {
            return null;
        }

        $agenticCommerceAccessTokenValidationResponseTransfer = $this->agenticCommerceClient->validateAgenticCommerceAccessToken($accessToken);

        if (!$agenticCommerceAccessTokenValidationResponseTransfer->getIsValid()) {
            return null;
        }

        return $agenticCommerceAccessTokenValidationResponseTransfer->getAgenticCommerceAccessToken()?->getStoreName();
    }
}
