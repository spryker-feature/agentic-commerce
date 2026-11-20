<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataDocumentTransfer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use stdClass;

class ClientMetadataDocumentFetcher implements ClientMetadataDocumentFetcherInterface
{
    use LoggerTrait;

    protected const string METHOD_GET = 'GET';

    protected const string HEADER_ACCEPT = 'Accept';

    protected const string HEADER_CONTENT_TYPE = 'Content-Type';

    protected const string HEADER_CONTENT_LENGTH = 'Content-Length';

    protected const string HEADER_CACHE_CONTROL = 'Cache-Control';

    protected const string CONTENT_TYPE_JSON = 'application/json';

    protected const string HANDLER_STAT_PRIMARY_IP = 'primary_ip';

    protected const string SCHEME_HTTPS = 'https';

    protected const string PROXY_NONE = '';

    protected const string REQUEST_OPTION_CURL = 'curl';

    protected const string IPV6_BRACKETS = '[]';

    protected const string PIN_FORMAT = '%s:%d:%s';

    protected const string PIN_FORMAT_IPV6 = '%s:%d:[%s]';

    protected const string PATTERN_MAX_AGE = '/(?:^|,)\s*max-age\s*=\s*(\d+)/i';

    protected const string PATTERN_NO_CACHE = '/(?:^|,)\s*(?:no-store|no-cache)\b/i';

    protected const int HTTP_STATUS_OK = 200;

    protected const int HTTPS_DEFAULT_PORT = 443;

    /**
     * @var int<1, max>
     */
    protected const int JSON_MAX_DEPTH = 16;

    public function __construct(
        protected ClientInterface $guzzleClient,
        protected HostAddressGuardInterface $hostAddressGuard,
        protected AgenticCommerceConfig $agenticCommerceConfig,
    ) {
    }

    public function fetch(string $documentUrl): AgenticCommerceClientMetadataDocumentTransfer
    {
        $documentTransfer = (new AgenticCommerceClientMetadataDocumentTransfer())->setUrl($documentUrl)->setIsFetched(false);

        if (strtolower((string)parse_url($documentUrl, PHP_URL_SCHEME)) !== static::SCHEME_HTTPS) {
            return $documentTransfer->setFailureReason(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_URL);
        }

        $host = (string)parse_url($documentUrl, PHP_URL_HOST);
        $allowedAddress = $this->hostAddressGuard->resolveAllowedAddress($host);

        if ($allowedAddress === null) {
            return $documentTransfer->setFailureReason(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_ADDRESS_REFUSED);
        }

        try {
            return $this->fetchDocument($documentUrl, $host, $allowedAddress, $documentTransfer);
        } catch (GuzzleException | JsonException $exception) {
            $this->getLogger()->warning('Client metadata document could not be fetched.', ['host' => $host, 'exception' => $exception::class]);

            return $documentTransfer->setFailureReason(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_FAILED);
        }
    }

    protected function fetchDocument(
        string $documentUrl,
        string $host,
        string $allowedAddress,
        AgenticCommerceClientMetadataDocumentTransfer $documentTransfer
    ): AgenticCommerceClientMetadataDocumentTransfer {
        $timeoutSeconds = $this->agenticCommerceConfig->getClientIdMetadataDocumentFetchTimeoutSeconds();
        $maxBytes = $this->agenticCommerceConfig->getClientIdMetadataDocumentMaxBytes();
        $sink = new ClientMetadataDocumentBodySink($maxBytes);

        $response = $this->guzzleClient->request(static::METHOD_GET, $documentUrl, [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::PROXY => static::PROXY_NONE,
            RequestOptions::TIMEOUT => $timeoutSeconds,
            RequestOptions::CONNECT_TIMEOUT => $timeoutSeconds,
            RequestOptions::SINK => $sink,
            RequestOptions::HEADERS => [static::HEADER_ACCEPT => static::CONTENT_TYPE_JSON],
            RequestOptions::ON_HEADERS => static function (ResponseInterface $headersResponse) use ($maxBytes): void {
                $contentLength = $headersResponse->getHeaderLine(static::HEADER_CONTENT_LENGTH);

                if ($contentLength !== '' && (int)$contentLength > $maxBytes) {
                    throw new RuntimeException('The client metadata document is larger than allowed.');
                }
            },
            RequestOptions::ON_STATS => static function (TransferStats $transferStats) use ($documentTransfer): void {
                $connectedAddress = $transferStats->getHandlerStat(static::HANDLER_STAT_PRIMARY_IP);
                $documentTransfer->setConnectedAddress(is_string($connectedAddress) && $connectedAddress !== '' ? $connectedAddress : null);
            },
            static::REQUEST_OPTION_CURL => $this->buildCurlOptions($documentUrl, $host, $allowedAddress),
        ]);

        if ($sink->isLimitExceeded() || !$this->isAcceptedResponse($response, $documentTransfer->getConnectedAddress(), $allowedAddress)) {
            return $documentTransfer->setFailureReason(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_FAILED);
        }

        $sink->rewind();
        $body = $sink->getContents();

        if (!json_decode($body, false, static::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR) instanceof stdClass) {
            return $documentTransfer->setFailureReason(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_FAILED);
        }

        return $documentTransfer
            ->setIsFetched(true)
            ->setContent(json_decode($body, true, static::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR))
            ->setCacheLifetimeSeconds($this->resolveCacheLifetimeSeconds($response->getHeaderLine(static::HEADER_CACHE_CONTROL)));
    }

    protected function isAcceptedResponse(ResponseInterface $response, ?string $connectedAddress, string $allowedAddress): bool
    {
        return $connectedAddress !== null
            && $this->hostAddressGuard->isSameAddress($connectedAddress, $allowedAddress)
            && $this->hostAddressGuard->isAllowedAddress($connectedAddress)
            && $response->getStatusCode() === static::HTTP_STATUS_OK
            && str_starts_with(strtolower($response->getHeaderLine(static::HEADER_CONTENT_TYPE)), static::CONTENT_TYPE_JSON);
    }

    /**
     * Pins curl to the checked address and the URL's port, so a second DNS answer (DNS rebinding) is never used.
     * A host that is an IP literal needs no pin.
     *
     * @return array<int, list<string>>
     */
    protected function buildCurlOptions(string $documentUrl, string $host, string $allowedAddress): array
    {
        if (filter_var(trim($host, static::IPV6_BRACKETS), FILTER_VALIDATE_IP) !== false) {
            return [];
        }

        $port = parse_url($documentUrl, PHP_URL_PORT) ?? static::HTTPS_DEFAULT_PORT;
        $pinFormat = filter_var($allowedAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? static::PIN_FORMAT_IPV6 : static::PIN_FORMAT;

        return [CURLOPT_RESOLVE => [sprintf($pinFormat, $host, $port, $allowedAddress)]];
    }

    protected function resolveCacheLifetimeSeconds(string $cacheControl): int
    {
        $minimum = $this->agenticCommerceConfig->getClientIdMetadataDocumentCacheMinSeconds();

        if (preg_match(static::PATTERN_NO_CACHE, $cacheControl) === 1) {
            return $minimum;
        }

        if (preg_match(static::PATTERN_MAX_AGE, $cacheControl, $matches) !== 1) {
            return $this->agenticCommerceConfig->getClientIdMetadataDocumentCacheDefaultSeconds();
        }

        return max($minimum, min((int)$matches[1], $this->agenticCommerceConfig->getClientIdMetadataDocumentCacheMaxSeconds()));
    }
}
