<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;
use Spryker\Zed\Kernel\Container;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceDependencyProvider;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentBodySink;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFetcher;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\ClientMetadataDocumentFetcherInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuard;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuardInterface;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group AgenticCommerceClient
 * @group ClientMetadataDocumentFetcherTest
 * Add your own group annotations below this line
 */
class ClientMetadataDocumentFetcherTest extends Unit
{
    protected const string DOCUMENT_URL = 'https://vendor.example/oauth/client.json';

    protected const string PUBLIC_ADDRESS = '8.8.8.8';

    protected const string PUBLIC_IPV6_ADDRESS = '2001:4860:4860::8888';

    protected const string LOOPBACK_ADDRESS = '127.0.0.1';

    protected const int MAX_BYTES = 5120;

    protected const int TIMEOUT_SECONDS = 5;

    protected AgenticCommerceBusinessTester $tester;

    /**
     * @var list<array{request: \Psr\Http\Message\RequestInterface, options: array<string, mixed>}>
     */
    protected array $history = [];

    protected function createFetcher(callable|MockHandler $handler, ?HostAddressGuardInterface $hostAddressGuard = null): ClientMetadataDocumentFetcherInterface
    {
        $handlerStack = HandlerStack::create($handler);
        $handlerStack->push(Middleware::tap(function (RequestInterface $request, array $options): void {
            $this->history[] = ['request' => $request, 'options' => $options];
        }));

        return new ClientMetadataDocumentFetcher(
            new Client(['handler' => $handlerStack]),
            $hostAddressGuard ?? $this->createHostAddressGuardResolvingTo(static::PUBLIC_ADDRESS),
            new AgenticCommerceConfig(),
        );
    }

    protected function createHostAddressGuardResolvingTo(?string $address): HostAddressGuardInterface
    {
        $realHostAddressGuard = new HostAddressGuard();
        $hostAddressGuardMock = $this->createMock(HostAddressGuardInterface::class);
        $hostAddressGuardMock->method('resolveAllowedAddress')->willReturn($address);
        $hostAddressGuardMock->method('isSameAddress')->willReturnCallback($realHostAddressGuard->isSameAddress(...));
        $hostAddressGuardMock->method('isAllowedAddress')->willReturnCallback($realHostAddressGuard->isAllowedAddress(...));

        return $hostAddressGuardMock;
    }

    public function testGivenAJsonDocumentWithMaxAgeWhenFetchedThenItsContentAndCacheLifetimeAreReturned(): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, new Response(200, ['content-type' => 'application/json; charset=utf-8', 'cache-control' => 'public, max-age=600'], '{"client_id":"https://vendor.example/oauth/client.json"}'));

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertTrue($documentTransfer->getIsFetched());
        $this->assertSame(static::DOCUMENT_URL, $documentTransfer->getContent()['client_id']);
        $this->assertSame(600, $documentTransfer->getCacheLifetimeSeconds());
        $this->assertSame(static::PUBLIC_ADDRESS, $documentTransfer->getConnectedAddress());
    }

    /**
     * @dataProvider cacheControlDataProvider
     */
    public function testGivenACacheControlHeaderWhenFetchedThenTheLifetimeIsClampedToTheConfiguredBounds(?string $cacheControl, int $expectedLifetime): void
    {
        // Arrange
        $headers = ['content-type' => 'application/json'] + ($cacheControl === null ? [] : ['cache-control' => $cacheControl]);
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, new Response(200, $headers, '{}'));

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertSame($expectedLifetime, $documentTransfer->getCacheLifetimeSeconds());
    }

    /**
     * @return array<string, array{string|null, int}>
     */
    public function cacheControlDataProvider(): array
    {
        return [
            'no header' => [null, 3600],
            'below minimum' => ['max-age=10', 300],
            'above maximum' => ['max-age=999999', 86400],
            'no-store is the stated deviation: minimum, not uncached' => ['no-store', 300],
        ];
    }

    /**
     * @dataProvider failingResponseDataProvider
     */
    public function testGivenAFailingResponseWhenFetchedThenTheFetchFails(ResponseInterface $response): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, $response);

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertFalse($documentTransfer->getIsFetched());
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_FAILED, $documentTransfer->getFailureReason());
    }

    /**
     * @return array<string, list<\Psr\Http\Message\ResponseInterface>>
     */
    public function failingResponseDataProvider(): array
    {
        $json = ['content-type' => 'application/json'];
        $oversizedBody = '{"a":"' . str_repeat('a', 5200) . '"}';

        return [
            'redirect' => [new Response(302, ['location' => 'https://other.example/client.json'])],
            'not found' => [new Response(404)],
            'html' => [new Response(200, ['content-type' => 'text/html'], '<html></html>')],
            'too large with content length' => [new Response(200, $json + ['content-length' => (string)strlen($oversizedBody)], $oversizedBody)],
            'too large without content length' => [new Response(200, $json, $oversizedBody)],
            'content length above the limit with a small body' => [new Response(200, $json + ['content-length' => (string)(static::MAX_BYTES + 1)], '{}')],
            'not an object' => [new Response(200, $json, '"text"')],
            'a list' => [new Response(200, $json, '["a"]')],
            'broken json' => [new Response(200, $json, '{')],
        ];
    }

    /**
     * @dataProvider jsonObjectDataProvider
     *
     * @param array<mixed> $expectedContent
     */
    public function testGivenABodyThatIsAJsonObjectWhenFetchedThenItIsFetched(string $body, array $expectedContent): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, new Response(200, ['content-type' => 'application/json'], $body));

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertTrue($documentTransfer->getIsFetched());
        $this->assertSame($expectedContent, $documentTransfer->getContent());
    }

    /**
     * @return array<string, array{string, array<mixed>}>
     */
    public function jsonObjectDataProvider(): array
    {
        return [
            'empty object' => ['{}', []],
            'object with numeric keys' => ['{"0":"a"}', [0 => 'a']],
        ];
    }

    public function testGivenABodyWithoutContentLengthWhenItPassesTheLimitThenTheSinkStopsAcceptingData(): void
    {
        // Arrange
        $sink = new ClientMetadataDocumentBodySink(static::MAX_BYTES);

        // Act
        $acceptedBytes = $sink->write(str_repeat('a', static::MAX_BYTES));
        $refusedBytes = $sink->write('b');

        // Assert
        $this->assertSame(static::MAX_BYTES, $acceptedBytes);
        $this->assertSame(0, $refusedBytes);
        $this->assertTrue($sink->isLimitExceeded());
        $this->assertSame(static::MAX_BYTES, $sink->getSize());
    }

    public function testGivenATransportErrorWhenFetchedThenTheFetchFails(): void
    {
        // Arrange
        $handler = static fn (RequestInterface $request): PromiseInterface => Create::rejectionFor(new ConnectException('timeout', $request));

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_FAILED, $documentTransfer->getFailureReason());
    }

    public function testGivenAHostThatResolvesToARefusedAddressWhenFetchedThenNothingIsRequestedAndTheAddressIsRefused(): void
    {
        // Arrange
        $handler = new MockHandler([new Response(200, ['content-type' => 'application/json'], '{}')]);

        // Act
        $documentTransfer = $this->createFetcher($handler, $this->createHostAddressGuardResolvingTo(null))->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_ADDRESS_REFUSED, $documentTransfer->getFailureReason());
        $this->assertCount(0, $this->history);
    }

    public function testGivenAnHttpUrlWhenFetchedThenNothingIsRequestedAndTheUrlIsRefused(): void
    {
        // Arrange
        $handler = new MockHandler([new Response(200, ['content-type' => 'application/json'], '{}')]);

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch('http://vendor.example/oauth/client.json');

        // Assert
        $this->assertFalse($documentTransfer->getIsFetched());
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_URL, $documentTransfer->getFailureReason());
        $this->assertCount(0, $this->history);
    }

    public function testGivenTheConnectionLandsOnALoopbackAddressDespiteThePinWhenFetchedThenTheResponseIsDiscarded(): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::LOOPBACK_ADDRESS, new Response(200, ['content-type' => 'application/json'], '{}'));

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertFalse($documentTransfer->getIsFetched());
        $this->assertSame(SharedAgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_FETCH_FAILED, $documentTransfer->getFailureReason());
        $this->assertSame(static::LOOPBACK_ADDRESS, $documentTransfer->getConnectedAddress());
    }

    public function testGivenAnIpv6AddressReportedInLongFormWhenFetchedThenItCountsAsThePinnedAddress(): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler('2001:4860:4860:0:0:0:0:8888', new Response(200, ['content-type' => 'application/json'], '{}'));

        // Act
        $documentTransfer = $this->createFetcher($handler, $this->createHostAddressGuardResolvingTo(static::PUBLIC_IPV6_ADDRESS))->fetch(static::DOCUMENT_URL);

        // Assert
        $this->assertTrue($documentTransfer->getIsFetched());
        $this->assertSame(['vendor.example:443:[2001:4860:4860::8888]'], $this->history[0]['options']['curl'][CURLOPT_RESOLVE]);
    }

    public function testGivenAUrlWithAPortWhenFetchedThenThePinUsesThatPort(): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, new Response(200, ['content-type' => 'application/json'], '{}'));

        // Act
        $this->createFetcher($handler)->fetch('https://vendor.example:8443/oauth/client.json');

        // Assert
        $this->assertSame(['vendor.example:8443:8.8.8.8'], $this->history[0]['options']['curl'][CURLOPT_RESOLVE]);
    }

    public function testGivenAUrlWithAnIpLiteralHostWhenFetchedThenNoPinIsSetAndTheAddressIsStillChecked(): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, new Response(200, ['content-type' => 'application/json'], '{}'));

        // Act
        $documentTransfer = $this->createFetcher($handler)->fetch('https://8.8.8.8/oauth/client.json');

        // Assert
        $this->assertTrue($documentTransfer->getIsFetched());
        $this->assertSame([], $this->history[0]['options']['curl']);
    }

    public function testGivenAFetchWhenTheRequestIsSentThenProductionOptionsPinTheAddressAndDisableProxyRedirectsAndStreaming(): void
    {
        // Arrange
        $handler = $this->tester->createClientMetadataDocumentHandler(static::PUBLIC_ADDRESS, new Response(200, ['content-type' => 'application/json'], '{}'));

        // Act
        $this->createFetcher($handler)->fetch(static::DOCUMENT_URL);

        // Assert
        $options = $this->history[0]['options'];
        $this->assertSame([CURLOPT_RESOLVE => ['vendor.example:443:8.8.8.8']], $options['curl']);
        $this->assertSame('', $options['proxy']);
        $this->assertFalse($options['allow_redirects']);
        $this->assertArrayNotHasKey('stream', $options);
        $this->assertInstanceOf(ClientMetadataDocumentBodySink::class, $options['sink']);
        $this->assertEquals(static::TIMEOUT_SECONDS, $options['timeout']);
        $this->assertEquals(static::TIMEOUT_SECONDS, $options['connect_timeout']);
    }

    public function testGivenTheProductionHandlerStackWhenTheDependencyIsProvidedThenItUsesTheCurlHandler(): void
    {
        // Arrange
        $container = new Container();

        // Act
        $guzzleClient = (new AgenticCommerceDependencyProvider())->provideBusinessLayerDependencies($container)->get(AgenticCommerceDependencyProvider::CLIENT_GUZZLE);

        // Assert
        $handlerStack = $guzzleClient->getConfig('handler');
        $this->assertInstanceOf(HandlerStack::class, $handlerStack);
        $this->assertInstanceOf(CurlHandler::class, (new ReflectionProperty(HandlerStack::class, 'handler'))->getValue($handlerStack));
    }
}
