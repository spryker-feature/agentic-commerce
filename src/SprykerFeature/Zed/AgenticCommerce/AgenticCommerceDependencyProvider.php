<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE = 'PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addAgenticCommerceConsentPostRevokePlugins($container);

        return $container;
    }

    protected function addAgenticCommerceConsentPostRevokePlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE, function () {
            return $this->getAgenticCommerceConsentPostRevokePlugins();
        });

        return $container;
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface>
     */
    protected function getAgenticCommerceConsentPostRevokePlugins(): array
    {
        return [];
    }
}
