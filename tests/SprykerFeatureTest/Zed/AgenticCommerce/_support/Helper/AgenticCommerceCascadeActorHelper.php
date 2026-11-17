<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Generated\Shared\Transfer\UserTransfer;
use Spryker\Service\Container\ContainerInterface;
use SprykerTest\Service\Container\Helper\ContainerHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class AgenticCommerceCascadeActorHelper extends Module
{
    use ContainerHelperTrait;
    use LocatorHelperTrait;

    /**
     * @uses \SprykerFeature\Zed\AgenticCommerce\Communication\Plugin\Application\AgenticCommerceBackendGatewayApplicationPlugin::SERVICE_REQUEST_ACTING_COMPANY_USER_UUID
     */
    protected const string SERVICE_REQUEST_ACTING_COMPANY_USER_UUID = 'agentic_commerce_request_acting_company_user_uuid';

    protected ?ContainerInterface $actingCompanyUserUuidContainer = null;

    protected bool $hasCurrentBackOfficeUser = false;

    public function haveActingCompanyUserUuidInTheZedRequest(string $companyUserUuid): void
    {
        $this->actingCompanyUserUuidContainer = $this->getContainerHelper()->getContainer();
        $this->actingCompanyUserUuidContainer->set(static::SERVICE_REQUEST_ACTING_COMPANY_USER_UUID, $companyUserUuid);
    }

    public function haveCurrentBackOfficeUser(string $username): void
    {
        $this->getLocator()->user()->facade()->setCurrentUser((new UserTransfer())->setUsername($username));
        $this->hasCurrentBackOfficeUser = true;
    }

    public function _after(TestInterface $test): void
    {
        if ($this->actingCompanyUserUuidContainer !== null) {
            $this->actingCompanyUserUuidContainer->remove(static::SERVICE_REQUEST_ACTING_COMPANY_USER_UUID);
            $this->actingCompanyUserUuidContainer = null;
        }

        if ($this->hasCurrentBackOfficeUser) {
            $this->getLocator()->user()->facade()->resetCurrentUser();
            $this->hasCurrentBackOfficeUser = false;
        }

        parent::_after($test);
    }
}
