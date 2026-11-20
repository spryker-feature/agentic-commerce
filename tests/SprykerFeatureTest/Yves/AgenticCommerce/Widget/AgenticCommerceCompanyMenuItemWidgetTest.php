<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Widget;

use Codeception\Test\Unit;
use Spryker\Client\Permission\PermissionClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Yves\AgenticCommerce\Widget\AgenticCommerceCompanyMenuItemWidget;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Widget
 * @group AgenticCommerceCompanyMenuItemWidgetTest
 * Add your own group annotations below this line
 */
class AgenticCommerceCompanyMenuItemWidgetTest extends Unit
{
    protected const string PARAMETER_IS_VISIBLE = 'isVisible';

    protected const string PARAMETER_IS_ACTIVE_PAGE = 'isActivePage';

    protected const string PERMISSION_KEY = 'ManageCompanyAgenticCommercePermissionPlugin';

    protected const string ACTIVE_PAGE_COMPANY_CONNECTED_APPS = 'connected-apps';

    protected AgenticCommerceYvesTester $tester;

    public function testGivenACompanyUserWithThePermissionWhenTheMenuItemIsBuiltThenItIsVisible(): void
    {
        // Act
        $widget = $this->createWidget(true, static::ACTIVE_PAGE_COMPANY_CONNECTED_APPS);

        // Assert
        $this->assertTrue($widget->getParameters()[static::PARAMETER_IS_VISIBLE] ?? null);
        $this->assertTrue($widget->getParameters()[static::PARAMETER_IS_ACTIVE_PAGE] ?? null);
    }

    public function testGivenACompanyUserWithoutThePermissionWhenTheMenuItemIsBuiltThenItIsHidden(): void
    {
        // Act
        $widget = $this->createWidget(false, static::ACTIVE_PAGE_COMPANY_CONNECTED_APPS);

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_VISIBLE] ?? null);
    }

    public function testGivenNoIssuerIsConfiguredWhenTheMenuItemIsBuiltThenItIsHidden(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::ISSUER, '');

        // Act
        $widget = $this->createWidget(true, static::ACTIVE_PAGE_COMPANY_CONNECTED_APPS);

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_VISIBLE] ?? null);
    }

    public function testGivenAnotherCompanyPageWhenTheMenuItemIsBuiltThenItIsNotActive(): void
    {
        // Act
        $widget = $this->createWidget(true, 'users');

        // Assert
        $this->assertFalse($widget->getParameters()[static::PARAMETER_IS_ACTIVE_PAGE] ?? null);
    }

    protected function createWidget(bool $canManageCompanyAgenticCommerce, string $activePage): AgenticCommerceCompanyMenuItemWidget
    {
        $permissionClientMock = $this->createMock(PermissionClientInterface::class);
        $permissionClientMock->method('can')->willReturnCallback(
            static fn (string $permissionKey): bool => $permissionKey === static::PERMISSION_KEY && $canManageCompanyAgenticCommerce,
        );

        return $this->tester->createWidget(
            AgenticCommerceCompanyMenuItemWidget::class,
            $this->tester->createFactory(['getPermissionClient' => $permissionClientMock]),
            [$activePage],
        );
    }
}
