<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Logger;

use Codeception\Test\Unit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Spryker\Shared\ErrorHandler\ErrorLoggerInterface;
use Spryker\Shared\ZedRequest\Client\Exception\InvalidZedResponseException;
use SprykerFeature\Glue\AgenticCommerce\Logger\ZedCallFailureLogger;
use Throwable;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Logger
 * @group ZedCallFailureLoggerTest
 * Add your own group annotations below this line
 */
class ZedCallFailureLoggerTest extends Unit
{
    protected const string REQUEST_ID = 'request-id-1';

    protected const string ZED_URL = 'http://backend-gateway.shop.test/oauth/token';

    protected const string TOKEN_RESPONSE_BODY = 'Notice: stray output{"access_token":"secret-access-token","refresh_token":"secret-refresh-token"}';

    public function testGivenAnInvalidZedResponseCarryingTokensWhenLoggingThenTheLoggedExceptionKeepsTheStatusAndTheRequestIdButNotTheBody(): void
    {
        // Arrange
        $loggedExceptions = [];
        $errorLoggerMock = $this->createMock(ErrorLoggerInterface::class);
        $errorLoggerMock->method('log')->willReturnCallback(static function (Throwable $exception) use (&$loggedExceptions): void {
            $loggedExceptions[] = $exception;
        });
        $invalidZedResponseException = new InvalidZedResponseException('Invalid JSON', $this->createZedResponse(), static::ZED_URL, static::REQUEST_ID);

        // Act
        (new ZedCallFailureLogger($errorLoggerMock))->log($invalidZedResponseException);

        // Assert
        $this->assertCount(1, $loggedExceptions);
        $loggedException = $loggedExceptions[0];
        $this->assertNull($loggedException->getPrevious());
        $this->assertStringContainsString(InvalidZedResponseException::class, $loggedException->getMessage());
        $this->assertStringContainsString('[status code] 200', $loggedException->getMessage());
        $this->assertStringContainsString(static::REQUEST_ID, $loggedException->getMessage());
        $this->assertStringNotContainsString('secret-access-token', $loggedException->getMessage());
        $this->assertStringNotContainsString('secret-refresh-token', $loggedException->getMessage());
        $this->assertStringNotContainsString('[raw body]', $loggedException->getMessage());
    }

    protected function createZedResponse(): ResponseInterface
    {
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('__toString')->willReturn(static::TOKEN_RESPONSE_BODY);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getBody')->willReturn($streamMock);

        return $responseMock;
    }
}
