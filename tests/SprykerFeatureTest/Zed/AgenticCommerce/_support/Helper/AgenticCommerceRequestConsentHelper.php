<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Spryker\Service\Container\Container;
use Spryker\Service\Container\ContainerInterface;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\ZedRequest\Business\Client\Request;
use SprykerTest\Service\Container\Helper\ContainerHelperTrait;

class AgenticCommerceRequestConsentHelper extends Module
{
    use ContainerHelperTrait;

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Plugin\Application\AgenticCommerceBackendGatewayApplicationPlugin::SERVICE_REQUEST_CONSENT_UUID
     */
    protected const string SERVICE_REQUEST_CONSENT_UUID = 'agentic_commerce_request_consent_uuid';

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Plugin\Application\AgenticCommerceBackendGatewayApplicationPlugin::SERVICE_ZED_REQUEST
     */
    protected const string SERVICE_ZED_REQUEST = 'service_zed_request';

    protected ?ContainerInterface $requestConsentUuidContainer = null;

    public function haveRequestConsentUuid(string $consentUuid): void
    {
        $this->requestConsentUuidContainer = $this->getContainerHelper()->getContainer();
        $this->requestConsentUuidContainer->set(static::SERVICE_REQUEST_CONSENT_UUID, $consentUuid);
    }

    public function createContainerWithZedRequest(?string $metaDataKey, ?TransferInterface $metaDataTransfer): ContainerInterface
    {
        $zedRequest = new Request();

        if ($metaDataKey !== null && $metaDataTransfer !== null) {
            $zedRequest->addMetaTransfer($metaDataKey, $metaDataTransfer);
        }

        $container = new Container();
        $container->set(static::SERVICE_ZED_REQUEST, $zedRequest);

        return $container;
    }

    public function _after(TestInterface $test): void
    {
        if ($this->requestConsentUuidContainer !== null) {
            $this->requestConsentUuidContainer->remove(static::SERVICE_REQUEST_CONSENT_UUID);
            $this->requestConsentUuidContainer = null;
        }

        parent::_after($test);
    }
}
