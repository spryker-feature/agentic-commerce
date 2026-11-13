<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClient;

use Codeception\Test\Unit;
use SprykerFeature\Client\AgenticCommerce\Cache\MetadataDocumentFailureCache;
use SprykerFeatureTest\Client\AgenticCommerce\AgenticCommerceClientTester;
use SprykerFeatureTest\Client\AgenticCommerce\Helper\InMemoryAgenticCommerceRedisWrapper;
use SprykerFeatureTest\Client\AgenticCommerce\Helper\RecordingLogger;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Client
 * @group AgenticCommerce
 * @group AgenticCommerceClient
 * @group MetadataDocumentFailureCacheTest
 * Add your own group annotations below this line
 */
class MetadataDocumentFailureCacheTest extends Unit
{
    protected const string CLIENT_IDENTIFIER = 'https://agents.example.com/client.json';

    protected const string OTHER_CLIENT_IDENTIFIER = 'https://agents.example.com/other.json';

    protected const int TTL_SECONDS = 300;

    protected AgenticCommerceClientTester $tester;

    public function testGivenAClientIdentifierWhenItsFailureIsSavedThenItIsStoredUnderTheHashedFailureKey(): void
    {
        // Arrange
        $inMemoryRedisWrapper = new InMemoryAgenticCommerceRedisWrapper();
        $metadataDocumentFailureCache = new MetadataDocumentFailureCache($inMemoryRedisWrapper, new RecordingLogger());

        // Act
        $metadataDocumentFailureCache->save(static::CLIENT_IDENTIFIER, static::TTL_SECONDS);

        // Assert
        $this->assertNotNull($inMemoryRedisWrapper->get('agentic_commerce:metadata-failed:' . hash('sha256', static::CLIENT_IDENTIFIER)));
        $this->assertNull($inMemoryRedisWrapper->get(static::CLIENT_IDENTIFIER));
    }

    public function testGivenASavedFailureWhenAskingForThatAndAnotherClientIdentifierThenOnlyTheSavedOneHasAFailure(): void
    {
        // Arrange
        $metadataDocumentFailureCache = new MetadataDocumentFailureCache(new InMemoryAgenticCommerceRedisWrapper(), new RecordingLogger());
        $metadataDocumentFailureCache->save(static::CLIENT_IDENTIFIER, static::TTL_SECONDS);

        // Act
        $hasFailure = $metadataDocumentFailureCache->has(static::CLIENT_IDENTIFIER);
        $hasOtherFailure = $metadataDocumentFailureCache->has(static::OTHER_CLIENT_IDENTIFIER);

        // Assert
        $this->assertTrue($hasFailure);
        $this->assertFalse($hasOtherFailure);
    }

    public function testGivenASavedFailureWhenItIsSavedForZeroSecondsThenItIsRemoved(): void
    {
        // Arrange
        $metadataDocumentFailureCache = new MetadataDocumentFailureCache(new InMemoryAgenticCommerceRedisWrapper(), new RecordingLogger());
        $metadataDocumentFailureCache->save(static::CLIENT_IDENTIFIER, static::TTL_SECONDS);

        // Act
        $metadataDocumentFailureCache->save(static::CLIENT_IDENTIFIER, 0);

        // Assert
        $this->assertFalse($metadataDocumentFailureCache->has(static::CLIENT_IDENTIFIER));
    }
}
