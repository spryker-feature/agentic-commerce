<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Plugin\GlueStorefrontApiApplication;

use Spryker\Glue\Kernel\AbstractPlugin;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\ApplicationExtension\Dependency\Plugin\ApplicationPluginInterface;

/**
 * @method \SprykerFeature\Glue\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AgenticCommerceStoreApplicationPlugin extends AbstractPlugin implements ApplicationPluginInterface
{
    /**
     * {@inheritDoc}
     * - Must be wired directly after `\Spryker\Glue\StoresApi\Plugin\GlueStorefrontApiApplication\StoreApplicationPlugin`.
     * - Sets the `store` application service to the store claim of the delegated access token in the `Authorization` header, when the request path is a protocol endpoint or lies under a declared protected resource (`AgenticCommerceServiceInterface::getDelegatedAccessPathPattern()`) and the token validates locally.
     * - Leaves the `store` service as it is for any other path, when the request names a store through the `Store` header or the `_store` query parameter, or when it carries no valid delegated access token.
     *
     * @api
     */
    public function provide(ContainerInterface $container): ContainerInterface
    {
        return $this->getFactory()->createAgenticCommerceStoreServiceExpander()->expand($container, $this->getFactory()->createRequest());
    }
}
