<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Yves\AgenticCommerce\Authorization;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use SprykerFeature\Yves\AgenticCommerce\Authorization\AuthorizationResponseUrlBuilder;
use SprykerFeatureTest\Yves\AgenticCommerce\AgenticCommerceYvesTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group AgenticCommerce
 * @group Authorization
 * @group AuthorizationResponseUrlBuilderTest
 * Add your own group annotations below this line
 */
class AuthorizationResponseUrlBuilderTest extends Unit
{
    protected const string REDIRECT_URI_WITH_QUERY = 'https://app.example/callback?a=1';

    protected const string STATE = 'state-123';

    protected const string ENCODED_ISSUER = 'https%3A%2F%2Fglue.shop.test';

    protected const string AUTHORIZATION_CODE = 'code-123';

    protected AgenticCommerceYvesTester $tester;

    public function testGivenARedirectUriWithAQueryWhenTheCodeResponseUrlIsBuiltThenTheQueryIsKeptAndTheResponseParametersAreAppended(): void
    {
        // Arrange
        $authorizationResponseUrlBuilder = $this->tester->createFactory([])->createAuthorizationResponseUrlBuilder();

        // Act
        $responseUrl = $authorizationResponseUrlBuilder->buildCodeResponseUrl($this->createAuthorizationRequest(), static::AUTHORIZATION_CODE);

        // Assert
        $this->assertSame(
            sprintf('%s&code=%s&state=%s&iss=%s', static::REDIRECT_URI_WITH_QUERY, static::AUTHORIZATION_CODE, static::STATE, static::ENCODED_ISSUER),
            $responseUrl,
        );
    }

    public function testGivenARedirectUriWithAQueryWhenTheErrorResponseUrlIsBuiltThenTheQueryIsKeptAndTheErrorIsAppended(): void
    {
        // Arrange
        $authorizationResponseUrlBuilder = $this->tester->createFactory([])->createAuthorizationResponseUrlBuilder();

        // Act
        $responseUrl = $authorizationResponseUrlBuilder->buildErrorResponseUrl($this->createAuthorizationRequest(), AuthorizationResponseUrlBuilder::ERROR_ACCESS_DENIED);

        // Assert
        $this->assertSame(
            sprintf('%s&error=access_denied&state=%s&iss=%s', static::REDIRECT_URI_WITH_QUERY, static::STATE, static::ENCODED_ISSUER),
            $responseUrl,
        );
    }

    public function testGivenAnErrorWhenTheErrorResponseUrlIsBuiltThenItCarriesTheIssuer(): void
    {
        // Arrange
        $authorizationResponseUrlBuilder = $this->tester->createFactory([])->createAuthorizationResponseUrlBuilder();

        // Act
        $responseUrl = $authorizationResponseUrlBuilder->buildErrorResponseUrl($this->createAuthorizationRequest(), AuthorizationResponseUrlBuilder::ERROR_SERVER_ERROR);

        // Assert
        parse_str((string)parse_url($responseUrl, PHP_URL_QUERY), $responseParameters);
        $this->assertSame(rawurldecode(static::ENCODED_ISSUER), $responseParameters['iss']);
    }

    protected function createAuthorizationRequest(): AgenticCommerceAuthorizationRequestTransfer
    {
        return (new AgenticCommerceAuthorizationRequestTransfer())
            ->setRedirectUri(static::REDIRECT_URI_WITH_QUERY)
            ->setState(static::STATE);
    }
}
