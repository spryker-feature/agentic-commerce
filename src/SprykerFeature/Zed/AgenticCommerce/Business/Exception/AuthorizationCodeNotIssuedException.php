<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Exception;

use Exception;

class AuthorizationCodeNotIssuedException extends Exception
{
    protected const string MESSAGE = 'No authorization code was issued for the consent at index "%s".';

    public function __construct(protected string $entityIdentifier)
    {
        parent::__construct(sprintf(static::MESSAGE, $entityIdentifier));
    }

    public function getEntityIdentifier(): string
    {
        return $this->entityIdentifier;
    }
}
