<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Glue\AgenticCommerce\Security;

use Codeception\Test\Unit;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceBearerTokenExtractor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group AgenticCommerce
 * @group Security
 * @group AgenticCommerceBearerTokenExtractorTest
 * Add your own group annotations below this line
 */
class AgenticCommerceBearerTokenExtractorTest extends Unit
{
    protected const string ACCESS_TOKEN = 'delegated-access-token';

    /**
     * @dataProvider authorizationHeaderDataProvider
     */
    public function testGivenAnAuthorizationHeaderWhenExtractingTheBearerTokenThenOnlyABearerSchemeInAnyCaseYieldsTheToken(
        ?string $authorizationHeader,
        ?string $expectedToken
    ): void {
        // Arrange
        $request = Request::create('/agentic-commerce/principal');

        if ($authorizationHeader !== null) {
            $request->headers->set('Authorization', $authorizationHeader);
        }

        // Act
        $token = (new AgenticCommerceBearerTokenExtractor())->findBearerToken($request);

        // Assert
        $this->assertSame($expectedToken, $token);
    }

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function authorizationHeaderDataProvider(): array
    {
        return [
            'bearer scheme' => ['Bearer ' . static::ACCESS_TOKEN, static::ACCESS_TOKEN],
            'lower case scheme' => ['bearer ' . static::ACCESS_TOKEN, static::ACCESS_TOKEN],
            'upper case scheme' => ['BEARER ' . static::ACCESS_TOKEN, static::ACCESS_TOKEN],
            'basic scheme' => ['Basic dXNlcjpwYXNz', null],
            'scheme without token' => ['Bearer ', null],
            'no header' => [null, null],
        ];
    }
}
