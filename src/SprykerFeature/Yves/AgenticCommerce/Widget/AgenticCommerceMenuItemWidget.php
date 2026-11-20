<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class AgenticCommerceMenuItemWidget extends AbstractWidget
{
    public const string ACTIVE_PAGE_CONNECTED_APPS = 'connectedApps';

    protected const string PARAMETER_IS_ACTIVE_PAGE = 'isActivePage';

    protected const string PARAMETER_IS_VISIBLE = 'isVisible';

    public function __construct(string $activePage)
    {
        $this->addParameter(static::PARAMETER_IS_ACTIVE_PAGE, $activePage === static::ACTIVE_PAGE_CONNECTED_APPS);
        $this->addParameter(static::PARAMETER_IS_VISIBLE, $this->getConfig()->isConfigured());
    }

    public static function getName(): string
    {
        return 'AgenticCommerceMenuItemWidget';
    }

    public static function getTemplate(): string
    {
        return '@AgenticCommerce/views/connected-apps-menu-item/connected-apps-menu-item.twig';
    }
}
