<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Shared\AgenticCommerce\Plugin\Permission;

use Spryker\Shared\PermissionExtension\Dependency\Plugin\PermissionPluginInterface;

/**
 * Register it in the Zed and the Client `PermissionDependencyProvider::getPermissionPlugins()`.
 */
class ManageCompanyAgenticCommercePermissionPlugin implements PermissionPluginInterface
{
    public const string KEY = 'ManageCompanyAgenticCommercePermissionPlugin';

    /**
     * {@inheritDoc}
     * - Returns the key of the permission to see and revoke the delegated access of the company's users.
     *
     * @api
     */
    public function getKey(): string
    {
        return static::KEY;
    }
}
