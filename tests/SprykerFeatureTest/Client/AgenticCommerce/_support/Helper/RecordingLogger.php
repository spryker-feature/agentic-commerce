<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\Helper;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;

class RecordingLogger extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: string, context: array<mixed>}>
     */
    protected array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string)$message, 'context' => $context];
    }

    /**
     * @return list<array{level: mixed, message: string, context: array<mixed>}>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @return list<string>
     */
    public function getErrorMessages(): array
    {
        return $this->getMessagesOfLevel(LogLevel::ERROR);
    }

    /**
     * @return list<string>
     */
    public function getWarningMessages(): array
    {
        return $this->getMessagesOfLevel(LogLevel::WARNING);
    }

    /**
     * @return list<string>
     */
    protected function getMessagesOfLevel(string $level): array
    {
        $messages = [];

        foreach ($this->records as $record) {
            if ($record['level'] === $level) {
                $messages[] = $record['message'];
            }
        }

        return $messages;
    }
}
