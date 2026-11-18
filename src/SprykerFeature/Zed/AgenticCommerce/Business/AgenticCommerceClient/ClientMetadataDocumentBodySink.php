<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

/**
 * Takes the response body of a Client ID Metadata Document (CIMD) up to a byte limit; past it, every write is refused, which makes curl abort the transfer.
 */
class ClientMetadataDocumentBodySink implements StreamInterface
{
    use StreamDecoratorTrait;

    protected StreamInterface $stream;

    protected bool $limitExceeded = false;

    public function __construct(protected int $maxBytes)
    {
        $this->stream = Utils::streamFor('');
    }

    public function write(string $string): int
    {
        if ($this->limitExceeded || (int)$this->stream->getSize() + strlen($string) > $this->maxBytes) {
            $this->limitExceeded = true;

            return 0;
        }

        return $this->stream->write($string);
    }

    public function isLimitExceeded(): bool
    {
        return $this->limitExceeded;
    }
}
