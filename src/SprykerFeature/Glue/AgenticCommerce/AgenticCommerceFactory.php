<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce;

use Spryker\Glue\Kernel\AbstractFactory;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceBearerTokenExtractor;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommercePathMatcher;
use SprykerFeature\Glue\AgenticCommerce\Store\AgenticCommerceStoreResolver;
use SprykerFeature\Glue\AgenticCommerce\Store\AgenticCommerceStoreResolverInterface;
use SprykerFeature\Glue\AgenticCommerce\Store\AgenticCommerceStoreServiceExpander;
use SprykerFeature\Glue\AgenticCommerce\Store\AgenticCommerceStoreServiceExpanderInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig getConfig()
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface getClient()
 */
class AgenticCommerceFactory extends AbstractFactory
{
    public function createAgenticCommerceStoreServiceExpander(): AgenticCommerceStoreServiceExpanderInterface
    {
        return new AgenticCommerceStoreServiceExpander($this->createAgenticCommerceStoreResolver());
    }

    public function createAgenticCommerceStoreResolver(): AgenticCommerceStoreResolverInterface
    {
        return new AgenticCommerceStoreResolver(
            $this->getClient(),
            $this->createAgenticCommerceBearerTokenExtractor(),
            $this->createAgenticCommercePathMatcher(),
        );
    }

    public function createAgenticCommercePathMatcher(): AgenticCommercePathMatcher
    {
        return new AgenticCommercePathMatcher($this->getAgenticCommerceService());
    }

    public function createAgenticCommerceBearerTokenExtractor(): AgenticCommerceBearerTokenExtractor
    {
        return new AgenticCommerceBearerTokenExtractor();
    }

    public function createRequest(): Request
    {
        return Request::createFromGlobals();
    }

    public function getAgenticCommerceService(): AgenticCommerceServiceInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::SERVICE_AGENTIC_COMMERCE);
    }
}
