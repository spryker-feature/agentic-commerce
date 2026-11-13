<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Cache;

use Psr\Log\LoggerInterface;
use SprykerFeature\Client\AgenticCommerce\Exception\AgenticCommerceStorageUnavailableException;
use SprykerFeature\Client\AgenticCommerce\Redis\AgenticCommerceRedisWrapperInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class MetadataDocumentFailureCache implements MetadataDocumentFailureCacheInterface
{
    protected const string VALUE_FAILED = '1';

    protected const string KEY_TYPE = 'metadata-failed';

    protected const string HASH_ALGORITHM = 'sha256';

    protected const string LOG_MESSAGE_READ_FAILED = 'agentic_commerce metadata document failure cache could not be read, so the document is fetched.';

    protected const string LOG_MESSAGE_WRITE_FAILED = 'agentic_commerce metadata document failure cache could not be written.';

    public function __construct(
        protected AgenticCommerceRedisWrapperInterface $redisWrapper,
        protected LoggerInterface $logger
    ) {
    }

    public function has(string $clientIdentifier): bool
    {
        try {
            return $this->redisWrapper->get($this->buildKey($clientIdentifier)) !== null;
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error(static::LOG_MESSAGE_READ_FAILED, ['exception' => $exception]);

            return false;
        }
    }

    public function save(string $clientIdentifier, int $ttlSeconds): void
    {
        try {
            if ($ttlSeconds <= 0) {
                $this->redisWrapper->del([$this->buildKey($clientIdentifier)]);

                return;
            }

            $this->redisWrapper->setex($this->buildKey($clientIdentifier), $ttlSeconds, static::VALUE_FAILED);
        } catch (AgenticCommerceStorageUnavailableException $exception) {
            $this->logger->error(static::LOG_MESSAGE_WRITE_FAILED, ['exception' => $exception]);
        }
    }

    protected function buildKey(string $clientIdentifier): string
    {
        return implode(SharedAgenticCommerceConfig::REDIS_KEY_SEPARATOR, [SharedAgenticCommerceConfig::REDIS_KEY_PREFIX, static::KEY_TYPE, hash(static::HASH_ALGORITHM, $clientIdentifier)]);
    }
}
