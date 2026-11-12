<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Shared\AgenticCommerce\Exception;

use RuntimeException;

/**
 * A missing or malformed delegated access key, URL or scope is a deployment error: with such a value, encryption and token checks would run but protect nothing.
 */
class AgenticCommerceConfigurationException extends RuntimeException
{
}
