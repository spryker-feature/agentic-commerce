<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\AgenticCommerceOrderPlacementCheckTransfer;
use Spryker\Client\ZedRequest\ZedRequestClient;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceFactory;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

class AgenticCommerceCheckoutHelper extends Module
{
    use DependencyHelperTrait;

    /**
     * @uses \SprykerFeature\Client\AgenticCommerce\Zed\AgenticCommerceStub::checkAgenticCommerceOrderPlacement()
     */
    protected const string GATEWAY_PATH_CHECK_ORDER_PLACEMENT = '/agentic-commerce/gateway/check-agentic-commerce-order-placement';

    public function mockOrderPlacementCheck(string $result, ?string $reason = null, ?string $approvalStatus = null): AgenticCommerceOrderPlacementCheckTransfer
    {
        $agenticCommerceOrderPlacementCheckTransfer = (new AgenticCommerceOrderPlacementCheckTransfer())
            ->setResult($result)
            ->setReason($reason)
            ->setApprovalStatus($approvalStatus);

        $this->getDependencyHelper()->setDependency(
            AgenticCommerceDependencyProvider::CLIENT_ZED_REQUEST,
            $this->createZedRequestClientAnswering($agenticCommerceOrderPlacementCheckTransfer),
            AgenticCommerceFactory::class,
        );

        return $agenticCommerceOrderPlacementCheckTransfer;
    }

    protected function createZedRequestClientAnswering(AgenticCommerceOrderPlacementCheckTransfer $agenticCommerceOrderPlacementCheckTransfer): ZedRequestClient
    {
        return new class ($agenticCommerceOrderPlacementCheckTransfer, static::GATEWAY_PATH_CHECK_ORDER_PLACEMENT) extends ZedRequestClient {
            public function __construct(
                protected AgenticCommerceOrderPlacementCheckTransfer $agenticCommerceOrderPlacementCheckTransfer,
                protected string $checkOrderPlacementPath
            ) {
            }

            /**
             * @param string $url
             * @param array<mixed>|int|null $requestOptions
             */
            public function call($url, TransferInterface $object, $requestOptions = null): TransferInterface // phpcs:ignore SprykerStrict.TypeHints.ParameterTypeHint.MissingNativeTypeHint
            {
                if ($url === $this->checkOrderPlacementPath) {
                    return (new AgenticCommerceOrderPlacementCheckTransfer())->fromArray($this->agenticCommerceOrderPlacementCheckTransfer->toArray(), true);
                }

                return parent::call($url, $object, $requestOptions);
            }
        };
    }
}
