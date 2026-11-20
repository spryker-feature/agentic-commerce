<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Widget;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\Widget\AgenticCommerceMenuItemWidget;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Widget
 * @group AgenticCommerceMenuItemWidgetTest
 * Add your own group annotations below this line
 */
class AgenticCommerceMenuItemWidgetTest extends Unit
{
    protected const string PARAMETER_IS_VISIBLE = 'isVisible';

    protected AgenticCommerceYvesTester $tester;

    public function testGivenTheIssuerTheAuthorizationEndpointTheResourceUriAndThePublicKeyAreConfiguredWhenTheMenuItemIsBuiltThenItIsVisible(): void
    {
        // Act
        $widget = new AgenticCommerceMenuItemWidget(AgenticCommerceMenuItemWidget::ACTIVE_PAGE_CONNECTED_APPS);

        // Assert
        $this->assertTrue($widget->getParameters()[static::PARAMETER_IS_VISIBLE] ?? null);
    }

    public function testGivenNoIssuerIsConfiguredWhenTheMenuItemIsBuiltThenItIsHidden(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Act
        $widget = new AgenticCommerceMenuItemWidget(AgenticCommerceMenuItemWidget::ACTIVE_PAGE_CONNECTED_APPS);

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_VISIBLE] ?? null);
    }
}
