<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Persistence;

use Codeception\Test\Unit;
use SprykerFeature\Zed\AgenticCommerce\Persistence\Exception\MalformedStoredListException;
use SprykerFeature\Zed\AgenticCommerce\Persistence\Propel\Mapper\AgenticCommerceMapper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Persistence
 * @group AgenticCommerceMapperTest
 * Add your own group annotations below this line
 */
class AgenticCommerceMapperTest extends Unit
{
    public function testGivenAStoredJsonListWhenDecodedThenTheStringsAreReturnedInOrder(): void
    {
        // Act
        $values = (new AgenticCommerceMapper())->decodeList('["b:read","a:read"]');

        // Assert
        $this->assertSame(['b:read', 'a:read'], $values);
    }

    public function testGivenNoStoredValueWhenDecodedThenAnEmptyListIsReturned(): void
    {
        // Act
        $values = (new AgenticCommerceMapper())->decodeList(null);

        // Assert
        $this->assertSame([], $values);
    }

    /**
     * @dataProvider listWithNonStringElementDataProvider
     */
    public function testGivenAStoredListWithANonStringElementWhenDecodedThenAMalformedStoredListExceptionIsThrown(string $storedValue): void
    {
        // Assert
        $this->expectException(MalformedStoredListException::class);

        // Act
        (new AgenticCommerceMapper())->decodeList($storedValue);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function listWithNonStringElementDataProvider(): array
    {
        return [
            'integer' => ['["a:read",1]'],
            'null' => ['["a:read",null]'],
            'list' => ['["a:read",["b:read"]]'],
            'object' => ['["a:read",{"c":"d"}]'],
        ];
    }

    /**
     * @dataProvider nonListJsonDataProvider
     */
    public function testGivenStoredJsonThatIsNoListWhenDecodedThenAMalformedStoredListExceptionIsThrown(string $storedValue): void
    {
        // Assert
        $this->expectException(MalformedStoredListException::class);

        // Act
        (new AgenticCommerceMapper())->decodeList($storedValue);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonListJsonDataProvider(): array
    {
        return [
            'string' => ['"a:read"'],
            'number' => ['1'],
            'null' => ['null'],
            'object' => ['{"scope":"a:read"}'],
            'invalid json' => ['[a:read'],
        ];
    }
}
