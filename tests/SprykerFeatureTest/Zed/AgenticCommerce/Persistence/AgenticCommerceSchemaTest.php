<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Persistence;

use Codeception\Test\Unit;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Persistence
 * @group AgenticCommerceSchemaTest
 * Add your own group annotations below this line
 */
class AgenticCommerceSchemaTest extends Unit
{
    protected const string SCHEMA_PATH = __DIR__ . '/../../../../../src/SprykerFeature/Zed/AgenticCommerce/Persistence/Propel/Schema/spy_agentic_commerce.schema.xml';

    public function testGivenTheAgenticCommerceSchemaWhenReadThenNoTableReferencesCompanyUser(): void
    {
        // Arrange
        $schemaPath = static::SCHEMA_PATH;

        // Act
        $schemaXml = simplexml_load_file($schemaPath);
        $foreignTables = array_map('strval', $schemaXml->xpath('//foreign-key/@foreignTable'));

        // Assert
        $this->assertNotContains('spy_company_user', $foreignTables);
    }

    public function testGivenTheAgenticCommerceSchemaWhenReadThenEveryTableIsOwnedByTheModuleSoNoExistingTableIsExtended(): void
    {
        // Arrange
        $schemaPath = static::SCHEMA_PATH;

        // Act
        $tableNames = array_map('strval', simplexml_load_file($schemaPath)->xpath('//table/@name'));

        // Assert
        $this->assertSame(['spy_agentic_commerce_client', 'spy_agentic_commerce_client_redirect_host', 'spy_agentic_commerce_consent', 'spy_agentic_commerce_consent_lock', 'spy_agentic_commerce_consent_event', 'spy_agentic_commerce_consent_constraint', 'spy_agentic_commerce_sales_order', 'spy_agentic_commerce_cart_handoff'], $tableNames);
    }
}
