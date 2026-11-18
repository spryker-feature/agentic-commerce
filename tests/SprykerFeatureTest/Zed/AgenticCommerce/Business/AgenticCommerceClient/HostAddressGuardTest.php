<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient\HostAddressGuard;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group AgenticCommerceClient
 * @group HostAddressGuardTest
 * Add your own group annotations below this line
 */
class HostAddressGuardTest extends Unit
{
    /**
     * @dataProvider refusedHostDataProvider
     */
    public function testGivenARefusedHostWhenResolvedThenNoAddressIsAllowed(string $host): void
    {
        // Arrange
        $hostAddressGuard = new HostAddressGuard();

        // Act
        $allowedAddress = $hostAddressGuard->resolveAllowedAddress($host);

        // Assert
        $this->assertNull($allowedAddress);
    }

    /**
     * @return array<string, list<string>>
     */
    public function refusedHostDataProvider(): array
    {
        return [
            'loopback' => ['127.0.0.1'],
            'localhost name' => ['localhost'],
            'private ten' => ['10.0.0.5'],
            'private one seven two' => ['172.16.0.1'],
            'private one nine two' => ['192.168.1.1'],
            'link local metadata endpoint' => ['169.254.169.254'],
            'carrier grade nat' => ['100.64.0.1'],
            'ietf protocol assignments' => ['192.0.0.170'],
            'ipv4 multicast' => ['239.255.255.250'],
            'ipv6 multicast' => ['[ff02::1]'],
            'ipv6 loopback' => ['[::1]'],
            'ipv6 unique local' => ['[fd00::1]'],
            'ipv4 mapped ipv6 loopback' => ['[::ffff:127.0.0.1]'],
            'unspecified' => ['0.0.0.0'],
            'unresolvable' => ['does-not-exist.invalid'],
        ];
    }

    public function testGivenAPublicIpLiteralWhenResolvedThenThatAddressIsAllowed(): void
    {
        // Arrange
        $hostAddressGuard = new HostAddressGuard();

        // Act
        $allowedAddress = $hostAddressGuard->resolveAllowedAddress('8.8.8.8');

        // Assert
        $this->assertSame('8.8.8.8', $allowedAddress);
    }

    public function testGivenAPublicIpv6LiteralInBracketsWhenResolvedThenTheAddressIsAllowedWithoutBrackets(): void
    {
        // Arrange
        $hostAddressGuard = new HostAddressGuard();

        // Act
        $allowedAddress = $hostAddressGuard->resolveAllowedAddress('[2001:4860:4860::8888]');

        // Assert
        $this->assertSame('2001:4860:4860::8888', $allowedAddress);
    }

    /**
     * @dataProvider sameAddressDataProvider
     */
    public function testGivenTwoWritingsOfAnAddressWhenComparedThenTheBinaryFormDecides(string $firstAddress, string $secondAddress, bool $expectedIsSame): void
    {
        // Act
        $isSame = (new HostAddressGuard())->isSameAddress($firstAddress, $secondAddress);

        // Assert
        $this->assertSame($expectedIsSame, $isSame);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public function sameAddressDataProvider(): array
    {
        return [
            'identical ipv4' => ['8.8.8.8', '8.8.8.8', true],
            'ipv6 short and long' => ['2001:db8::1', '2001:0db8:0:0:0:0:0:1', true],
            'ipv6 with brackets' => ['[2001:db8::1]', '2001:db8::1', true],
            'different' => ['8.8.8.8', '8.8.4.4', false],
            'not an address' => ['vendor.example', '8.8.8.8', false],
        ];
    }

    /**
     * @dataProvider allowedAddressDataProvider
     */
    public function testGivenAnAddressWhenCheckedThenOnlyAPublicIpAddressIsAllowed(string $address, bool $expectedIsAllowed): void
    {
        // Act
        $isAllowed = (new HostAddressGuard())->isAllowedAddress($address);

        // Assert
        $this->assertSame($expectedIsAllowed, $isAllowed);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function allowedAddressDataProvider(): array
    {
        return [
            'public ipv4' => ['8.8.8.8', true],
            'public ipv6 with brackets' => ['[2001:4860:4860::8888]', true],
            'loopback' => ['127.0.0.1', false],
            'ipv6 multicast' => ['ff02::1', false],
            'host name' => ['vendor.example', false],
            'empty' => ['', false],
        ];
    }
}
