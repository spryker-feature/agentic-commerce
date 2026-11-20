<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce;

use SprykerFeature\Client\AgenticCommerce\AgenticCommerceFactory as AgenticCommerceClientFactory;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceContextReader;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceContextReaderInterface;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceRequestAttributeSeeder;
use SprykerFeature\Glue\AgenticCommerce\Context\AgenticCommerceRequestAttributeSeederInterface;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceRequestMatcher;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapper;
use SprykerFeature\Shared\AgenticCommerce\Request\RequestClientDataMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class AgenticCommerceBundle extends AbstractBundle
{
    protected const string NAMESPACE_PREFIX = 'SprykerFeature\\Glue\\AgenticCommerce\\';

    protected const string SERVICE_RESOURCE_METADATA_COLLECTION_FACTORY = 'api_platform.metadata.resource.metadata_collection_factory';

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure();

        $services->load(static::NAMESPACE_PREFIX, __DIR__ . '/')
            ->exclude([
                __DIR__ . '/{Exception,Plugin}/',
                __DIR__ . '/Api/Storefront/{Processor,Provider}/',
                __DIR__ . '/AgenticCommerce{Bundle,DependencyProvider,Factory}.php',
            ]);

        $services->load(static::NAMESPACE_PREFIX . 'Context\\', __DIR__ . '/Context/')->public();
        $services->alias(AgenticCommerceContextReaderInterface::class, AgenticCommerceContextReader::class)->public();
        $services->alias(AgenticCommerceRequestAttributeSeederInterface::class, AgenticCommerceRequestAttributeSeeder::class)->public();

        $services->set(AgenticCommerceRequestMatcher::class)
            ->arg('$resourceMetadataCollectionFactory', new Reference(static::SERVICE_RESOURCE_METADATA_COLLECTION_FACTORY));
        $services->set(ZedCallFailureLogger::class)->arg('$errorLogger', null);

        $services->set(AgenticCommerceClientFactory::class);
        $services->set(AgenticCommerceRedisWrapperInterface::class)
            ->factory([service(AgenticCommerceClientFactory::class), 'createAgenticCommerceRedisWrapper']);
        $services->set(RequestClientDataMapper::class);
        $services->alias(RequestClientDataMapperInterface::class, RequestClientDataMapper::class);
    }
}
