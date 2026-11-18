<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
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
    protected const string RENAMED_CLIENT_NAME = 'Renamed Procurement Assistant';

    protected const string STORED_REDIRECT_URI = 'https://client.example/callback';

    protected const string REFRESHED_REDIRECT_URI = 'https://client.example/new-callback';

    protected const int CACHE_LIFETIME_SECONDS = 3600;

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenStoredClientWhenItsDocumentIsRefreshedWithANewNameThenTheOauthClientTakesTheNewNameAndFirstRedirectUri(): void
    {
        // Arrange
        $clientIdentifier = sprintf('https://client.example/%s/client-metadata.json', uniqid());
        $storedClientTransfer = $this->tester->haveClientMetadataDocumentClient($clientIdentifier, [static::STORED_REDIRECT_URI], '-1 minute');
        $clientMetadataTransfer = (new AgenticCommerceClientMetadataTransfer())
            ->setClientId($clientIdentifier)
            ->setClientName(static::RENAMED_CLIENT_NAME)
            ->setRedirectUris([static::REFRESHED_REDIRECT_URI, static::STORED_REDIRECT_URI]);

        // Act
        $clientTransfer = $this->tester->getFactory()
            ->createClientMetadataDocumentClientWriter()
            ->writeClient($clientMetadataTransfer, $storedClientTransfer, static::CACHE_LIFETIME_SECONDS);

        // Assert
        $this->assertSame(static::RENAMED_CLIENT_NAME, $clientTransfer->getName());
        $this->assertSame([static::REFRESHED_REDIRECT_URI, static::STORED_REDIRECT_URI], $clientTransfer->getRedirectUris());
        $oauthClientTransfer = $this->tester->findOauthClient($clientIdentifier);
        $this->assertSame(static::RENAMED_CLIENT_NAME, $oauthClientTransfer->getName());
        $this->assertSame(static::REFRESHED_REDIRECT_URI, $oauthClientTransfer->getRedirectUri());
        $this->assertNull($oauthClientTransfer->getSecret());
        $this->assertFalse($oauthClientTransfer->getIsConfidential());
    }
}
