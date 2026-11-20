<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Plugin\OauthApi;

use Spryker\Glue\Kernel\AbstractPlugin;
use Spryker\Glue\OauthExtension\Dependency\Plugin\AccessTokenValidationExcludedPathPatternProviderPluginInterface;

/**
 * @method \SprykerFeature\Glue\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AgenticCommerceAccessTokenValidationExcludedPathPatternProviderPlugin extends AbstractPlugin implements AccessTokenValidationExcludedPathPatternProviderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Returns `AgenticCommerceServiceInterface::getDelegatedAccessPathPattern()`: the protocol endpoints and the path prefix of every declared protected resource.
     * - A misdeclared protected resource leaves only the protocol endpoints excluded and is logged, so other routes keep working.
     *
     * @api
     *
     * @return list<string>
     */
    public function getExcludedPathPatterns(): array
    {
        return [$this->getFactory()->getAgenticCommerceService()->getDelegatedAccessPathPattern()];
    }
}
