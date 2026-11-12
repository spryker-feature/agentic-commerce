<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\AgenticCommerceClientStatusUpdater;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\AgenticCommerceClientStatusUpdaterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentEventWriter;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentEventWriterInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentReader;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentRevoker;
use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentRevokerInterface;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig getConfig()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface getEntityManager()
 */
class AgenticCommerceBusinessFactory extends AbstractBusinessFactory
{
    public function createAgenticCommerceClientStatusUpdater(): AgenticCommerceClientStatusUpdaterInterface
    {
        return new AgenticCommerceClientStatusUpdater(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createConsentRevoker(),
        );
    }

    public function createConsentRevoker(): ConsentRevokerInterface
    {
        return new ConsentRevoker(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createConsentEventWriter(),
            $this->getAgenticCommerceConsentPostRevokePlugins(),
        );
    }

    public function createConsentReader(): ConsentReaderInterface
    {
        return new ConsentReader($this->getRepository());
    }

    public function createConsentEventWriter(): ConsentEventWriterInterface
    {
        return new ConsentEventWriter($this->getEntityManager());
    }

    /**
     * @return list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentPostRevokePluginInterface>
     */
    public function getAgenticCommerceConsentPostRevokePlugins(): array
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::PLUGINS_AGENTIC_COMMERCE_CONSENT_POST_REVOKE);
    }
}
