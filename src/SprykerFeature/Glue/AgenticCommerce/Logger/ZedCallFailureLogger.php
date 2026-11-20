<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Logger;

use Spryker\Shared\ErrorHandler\ErrorLogger;
use Spryker\Shared\ErrorHandler\ErrorLoggerInterface;
use SprykerFeature\Glue\AgenticCommerce\Exception\ZedCallFailedException;
use Throwable;

class ZedCallFailureLogger
{
    protected const string RAW_BODY_MARKER = '[raw body]';

    public function __construct(protected ?ErrorLoggerInterface $errorLogger = null)
    {
    }

    public function log(Throwable $exception): void
    {
        ($this->errorLogger ?? ErrorLogger::getInstance())->log(new ZedCallFailedException(sprintf(
            '%s: %s',
            $exception::class,
            $this->removeRawBody($exception->getMessage()),
        )));
    }

    protected function removeRawBody(string $message): string
    {
        $rawBodyPosition = strpos($message, static::RAW_BODY_MARKER);

        return $rawBodyPosition === false ? $message : rtrim(substr($message, 0, $rawBodyPosition));
    }
}
