<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Api\Storefront\Request;

use Codeception\Test\Unit;
use SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Request\RequestMediaTypeChecker;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Api
 * @group Storefront
 * @group Request
 * @group RequestMediaTypeCheckerTest
 * Add your own group annotations below this line
 */
class RequestMediaTypeCheckerTest extends Unit
{
    protected const string MEDIA_TYPE_JSON = 'application/json';

    /**
     * @dataProvider contentTypeDataProvider
     */
    public function testGivenAContentTypeWhenCheckingForJsonThenOnlyTheJsonMediaTypeMatchesWhateverItsCaseAndParameters(
        ?string $contentType,
        bool $expectedIsMediaType
    ): void {
        // Arrange
        $request = Request::create('/oauth/register', Request::METHOD_POST);
        $request->headers->remove('Content-Type');

        if ($contentType !== null) {
            $request->headers->set('Content-Type', $contentType);
        }

        // Act
        $isMediaType = (new RequestMediaTypeChecker())->isMediaType($request, static::MEDIA_TYPE_JSON);

        // Assert
        $this->assertSame($expectedIsMediaType, $isMediaType);
    }

    /**
     * @return array<string, array{string|null, bool}>
     */
    public static function contentTypeDataProvider(): array
    {
        return [
            'exact' => ['application/json', true],
            'with charset' => ['application/json; charset=UTF-8', true],
            'upper case with spaces' => [' Application/JSON ;charset=utf-8', true],
            'JSON:API' => ['application/vnd.api+json', false],
            'form' => ['application/x-www-form-urlencoded', false],
            'prefix only' => ['application/jsonx', false],
            'missing' => [null, false],
        ];
    }
}
