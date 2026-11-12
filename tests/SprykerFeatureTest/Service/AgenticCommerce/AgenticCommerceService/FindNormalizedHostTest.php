<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceService;

use Codeception\Test\Unit;
use SprykerFeatureTest\Service\AgenticCommerce\AgenticCommerceServiceTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Service
 * @group AgenticCommerce
 * @group AgenticCommerceService
 * @group FindNormalizedHostTest
 * Add your own group annotations below this line
 */
class FindNormalizedHostTest extends Unit
{
    protected const string EXTENSION_INTL = 'intl';

    protected AgenticCommerceServiceTester $tester;

    /**
     * @dataProvider hostSpellingDataProvider
     */
    public function testGivenAHostInAnotherSpellingWhenNormalizedThenTheOneComparableFormIsReturned(string $host, string $expectedNormalizedHost): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->getService();

        // Act
        $normalizedHost = $agenticCommerceService->findNormalizedHost($host);

        // Assert
        $this->assertSame($expectedNormalizedHost, $normalizedHost);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function hostSpellingDataProvider(): array
    {
        return [
            'already normalized' => ['agents.example.com', 'agents.example.com'],
            'upper case' => ['Agents.EXAMPLE.com', 'agents.example.com'],
            'trailing dot' => ['agents.example.com.', 'agents.example.com'],
            'percent-encoded letter' => ['ag%65nts.example.com', 'agents.example.com'],
            'percent-encoded dot' => ['agents%2Eexample.com', 'agents.example.com'],
            'dotted-decimal IPv4 address' => ['127.0.0.1', '127.0.0.1'],
            'compressed IPv6 address' => ['[::1]', '[::1]'],
            'expanded IPv6 address' => ['[0:0:0:0:0:0:0:1]', '[::1]'],
            'upper-case IPv6 address with a zero group' => ['[2001:DB8:0::A]', '[2001:db8::a]'],
        ];
    }

    /**
     * @dataProvider unicodeHostSpellingDataProvider
     */
    public function testGivenAUnicodeHostWhenNormalizedThenItsAsciiFormIsReturned(string $host, string $expectedNormalizedHost): void
    {
        // Arrange
        if (!extension_loaded(static::EXTENSION_INTL)) {
            $this->markTestSkipped(sprintf('The extension "%s" is needed to convert a Unicode host.', static::EXTENSION_INTL));
        }
        $agenticCommerceService = $this->tester->getService();

        // Act
        $normalizedHost = $agenticCommerceService->findNormalizedHost($host);

        // Assert
        $this->assertSame($expectedNormalizedHost, $normalizedHost);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function unicodeHostSpellingDataProvider(): array
    {
        return [
            'unicode host' => ['bücher.example', 'xn--bcher-kva.example'],
            'upper-case unicode host' => ['BÜCHER.example', 'xn--bcher-kva.example'],
            'ideographic full stop as trailing dot' => ['agents.example.com。', 'agents.example.com'],
            'percent-encoded fullwidth full stop as trailing dot' => ['agents.example.com%EF%BC%8E', 'agents.example.com'],
        ];
    }

    /**
     * @dataProvider invalidHostDataProvider
     */
    public function testGivenAnInvalidHostWhenNormalizedThenNullIsReturned(string $host): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->getService();

        // Act
        $normalizedHost = $agenticCommerceService->findNormalizedHost($host);

        // Assert
        $this->assertNull($normalizedHost);
    }

    /**
     * @return array<string, list<string>>
     */
    public function invalidHostDataProvider(): array
    {
        return [
            'empty' => [''],
            'only a dot' => ['.'],
            'two trailing dots' => ['agents.example.com..'],
            'percent sign left after decoding' => ['agents%2525.example.com'],
            'underscore' => ['agents_1.example.com'],
            'space' => ['agents example.com'],
            'short IPv4 address' => ['127.1'],
            'decimal IPv4 address' => ['2130706433'],
            'hexadecimal IPv4 address' => ['0x7f.0.0.1'],
            'IPv4 address with a leading zero' => ['127.0.0.01'],
            'unbracketed IPv6 address' => ['::1'],
            'bracketed text that is no IPv6 address' => ['[agents.example.com]'],
            'host with a port' => ['agents.example.com:8080'],
            'IPv6 address with a port' => ['[::1]:443'],
            'host with user information' => ['user@host'],
            'host with a path' => ['host/path'],
            'host with a scheme' => ['https://host'],
            'backslash' => ['agents\\example.com'],
            'control character' => ["agents\x01.example.com"],
            'NUL byte' => ["agents\x00.example.com"],
            'label over 63 characters' => [str_repeat('a', 64) . '.example.com'],
            'host over 253 characters' => [str_repeat('abc.', 63) . 'ab'],
            'host that fails the IDN conversion' => ["\u{FFFF}.example"],
            'IPv6 address with a zone id' => ['[fe80::1%25eth0]'],
            'IPv4-mapped IPv6 address in dotted notation' => ['[::ffff:127.0.0.1]'],
            'IPv4-mapped IPv6 address in hexadecimal notation' => ['[::ffff:7f00:1]'],
            'IPv4-compatible IPv6 address' => ['[::127.0.0.1]'],
        ];
    }

    public function testGivenValidAndInvalidHostsWhenNormalizedTogetherThenOnlyTheValidOnesAreReturnedInTheirOrder(): void
    {
        // Arrange
        $agenticCommerceService = $this->tester->getService();

        // Act
        $normalizedHosts = $agenticCommerceService->normalizeHosts(['B.example.', 'agents_1.example.com', '[0::1]', '', 'a.example']);

        // Assert
        $this->assertSame(['b.example', '[::1]', 'a.example'], $normalizedHosts);
    }
}
