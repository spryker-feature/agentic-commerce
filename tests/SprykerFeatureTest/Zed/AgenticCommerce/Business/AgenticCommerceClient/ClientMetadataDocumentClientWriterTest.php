<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use GuzzleHttp\Psr7\Response;
use Propel\Runtime\Exception\PropelException;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group AgenticCommerceClient
 * @group ClientMetadataDocumentClientWriterTest
 * Add your own group annotations below this line
 */
class ClientMetadataDocumentClientWriterTest extends Unit
{
    protected const string CLIENT_ID = 'https://vendor.example/oauth/client.json';

    protected const string REDIRECT_URI = 'https://vendor.example/callback';

    protected const int HTTP_STATUS_OK = 200;

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([
            AgenticCommerceHelper::SCOPE => 'agentic_commerce.scope.agentic-commerce.principal-read',
        ])]);
        $this->tester->allowAllHostsInTheAddressGuard();
        $this->tester->mockClientMetadataDocumentResponse(new Response(
            static::HTTP_STATUS_OK,
            ['content-type' => 'application/json', 'cache-control' => 'max-age=3600'],
            json_encode([
                'client_id' => static::CLIENT_ID,
                'client_name' => 'Vendor app',
                'redirect_uris' => [static::REDIRECT_URI],
                'token_endpoint_auth_method' => 'none',
            ], JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * @dataProvider duplicateKeyDataProvider
     *
     * @param list<int|string> $errorInfo
     */
    public function testGivenADuplicateKeyErrorWhenCreatingAClientThenItReadsAsCreatedConcurrently(array $errorInfo): void
    {
        // Arrange
        $this->tester->simulateConcurrentCreationOfClientMetadataDocumentClient(static::CLIENT_ID, [static::REDIRECT_URI], $errorInfo);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier(static::CLIENT_ID, static::REDIRECT_URI);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::CLIENT_ID, $authorizationResponseTransfer->getAgenticCommerceClientOrFail()->getClientIdentifier());
    }

    /**
     * @dataProvider otherIntegrityErrorDataProvider
     *
     * @param list<int|string> $errorInfo
     */
    public function testGivenAnotherIntegrityErrorWhenCreatingAClientThenTheExceptionIsNotSwallowed(array $errorInfo): void
    {
        // Arrange
        $this->tester->simulateConcurrentCreationOfClientMetadataDocumentClient(static::CLIENT_ID, [static::REDIRECT_URI], $errorInfo);
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier(static::CLIENT_ID, static::REDIRECT_URI);

        // Assert
        $this->expectException(PropelException::class);

        // Act
        $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);
    }

    /**
     * @return array<string, array{list<int|string>}>
     */
    public static function duplicateKeyDataProvider(): array
    {
        return [
            'MySQL duplicate entry' => [['23000', 1062, 'Duplicate entry']],
            'PostgreSQL unique violation' => [['23505', 7, 'duplicate key value']],
        ];
    }

    /**
     * @return array<string, array{list<int|string>}>
     */
    public static function otherIntegrityErrorDataProvider(): array
    {
        return [
            'MySQL foreign key violation' => [['23000', 1452, 'Cannot add or update a child row']],
            'PostgreSQL not-null violation' => [['23502', 7, 'null value']],
        ];
    }
}
