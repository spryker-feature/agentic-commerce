<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce;

use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AgenticCommerce\Cache\ConsentUsageCache;
use SprykerFeature\Client\AgenticCommerce\Cache\ConsentUsageCacheInterface;
use SprykerFeature\Client\AgenticCommerce\Cache\MetadataDocumentFailureCache;
use SprykerFeature\Client\AgenticCommerce\Cache\MetadataDocumentFailureCacheInterface;
use SprykerFeature\Client\AgenticCommerce\Context\AuthenticatedConsentHolder;
use SprykerFeature\Client\AgenticCommerce\Context\AuthenticatedConsentHolderInterface;
use SprykerFeature\Client\AgenticCommerce\Expander\SecurityBlockerConfigurationSettingsExpander;
use SprykerFeature\Client\AgenticCommerce\Expander\SecurityBlockerConfigurationSettingsExpanderInterface;
use SprykerFeature\Client\AgenticCommerce\KillSwitch\KillSwitch;
use SprykerFeature\Client\AgenticCommerce\KillSwitch\KillSwitchInterface;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapper;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Client\AgenticCommerce\SecurityBlocker\RateLimitChecker;
use SprykerFeature\Client\AgenticCommerce\SecurityBlocker\RateLimitCheckerInterface;
use SprykerFeature\Client\AgenticCommerce\Token\AgenticCommerceAccessTokenValidator;
use SprykerFeature\Client\AgenticCommerce\Token\AgenticCommerceAccessTokenValidatorInterface;
use SprykerFeature\Client\AgenticCommerce\Zed\AgenticCommerceStub;
use SprykerFeature\Client\AgenticCommerce\Zed\AgenticCommerceStubInterface;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;

/**
 * @method \SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceFactory extends AbstractFactory
{
    use LoggerTrait;

    public function createAgenticCommerceAccessTokenValidator(): AgenticCommerceAccessTokenValidatorInterface
    {
        return new AgenticCommerceAccessTokenValidator($this->getConfig(), $this->getAgenticCommerceService());
    }

    public function createAgenticCommerceStub(): AgenticCommerceStubInterface
    {
        return new AgenticCommerceStub($this->getZedRequestClient());
    }

    public function createAuthenticatedConsentHolder(): AuthenticatedConsentHolderInterface
    {
        return new AuthenticatedConsentHolder();
    }

    public function createAgenticCommerceRedisWrapper(): AgenticCommerceRedisWrapperInterface
    {
        return new AgenticCommerceRedisWrapper($this->getRedisClient(), $this->getConfig());
    }

    public function createKillSwitch(): KillSwitchInterface
    {
        return new KillSwitch($this->createAgenticCommerceRedisWrapper(), $this->getLogger());
    }

    public function createConsentUsageCache(): ConsentUsageCacheInterface
    {
        return new ConsentUsageCache($this->createAgenticCommerceRedisWrapper(), $this->getConfig(), $this->getLogger());
    }

    public function createMetadataDocumentFailureCache(): MetadataDocumentFailureCacheInterface
    {
        return new MetadataDocumentFailureCache($this->createAgenticCommerceRedisWrapper(), $this->getLogger());
    }

    public function createRateLimitChecker(): RateLimitCheckerInterface
    {
        return new RateLimitChecker($this->getSecurityBlockerClient(), $this->getConfig(), $this->getLogger());
    }

    public function createSecurityBlockerConfigurationSettingsExpander(): SecurityBlockerConfigurationSettingsExpanderInterface
    {
        return new SecurityBlockerConfigurationSettingsExpander($this->getConfig());
    }

    public function getSecurityBlockerClient(): SecurityBlockerClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_SECURITY_BLOCKER);
    }

    public function getRedisClient(): RedisClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_REDIS);
    }

    public function getAgenticCommerceService(): AgenticCommerceServiceInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::SERVICE_AGENTIC_COMMERCE);
    }

    public function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_ZED_REQUEST);
    }

    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(AgenticCommerceDependencyProvider::CLIENT_CUSTOMER);
    }
}
