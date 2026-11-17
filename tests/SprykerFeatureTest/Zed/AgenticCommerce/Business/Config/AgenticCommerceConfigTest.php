<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Config;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Config
 * @group AgenticCommerceConfigTest
 * Add your own group annotations below this line
 */
class AgenticCommerceConfigTest extends Unit
{
    protected const string UNKNOWN_PRINCIPAL_TYPE = 'unknown_principal_type';

    protected AgenticCommerceBusinessTester $tester;

    public function testGivenTheCompanyUserPrincipalTypeWhenReadingIdentityScopesThenBothStorefrontIdentityScopesAreReturned(): void
    {
        // Act
        $identityScopes = (new AgenticCommerceConfig())->getIdentityScopes(SharedAgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER);

        // Assert
        $this->assertSame([SharedAgenticCommerceConfig::SCOPE_IDENTITY_CUSTOMER, SharedAgenticCommerceConfig::SCOPE_IDENTITY_COMPANY_USER], $identityScopes);
    }

    public function testGivenAnUnknownPrincipalTypeWhenReadingIdentityScopesThenNoIdentityScopeIsReturned(): void
    {
        // Act
        $identityScopes = (new AgenticCommerceConfig())->getIdentityScopes(static::UNKNOWN_PRINCIPAL_TYPE);

        // Assert
        $this->assertSame([], $identityScopes);
    }

    public function testGivenNoProjectConfigurationWhenReadingTheRegistrationSwitchesThroughTheZedConfigThenBothAreOff(): void
    {
        // Arrange
        $this->tester->removeConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED);
        $this->tester->removeConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED);
        $zedConfig = new AgenticCommerceConfig();

        // Act
        $isClientIdMetadataDocumentEnabled = $zedConfig->isClientIdMetadataDocumentEnabled();
        $isDynamicClientRegistrationEnabled = $zedConfig->isDynamicClientRegistrationEnabled();

        // Assert
        $this->assertFalse($isClientIdMetadataDocumentEnabled);
        $this->assertFalse($isDynamicClientRegistrationEnabled);
    }

    /**
     * @dataProvider commaSeparatedListDataProvider
     */
    public function testGivenACommaSeparatedStringWhenReadingTheDenylistsAndTheHostAllowlistThenEachValueIsOneTrimmedEntry(
        string $configurationKey,
        string $getterName
    ): void {
        // Arrange
        $this->tester->setConfig($configurationKey, ' a.example , b.example,,');

        // Act
        $values = (new AgenticCommerceConfig())->$getterName();

        // Assert
        $this->assertSame(['a.example', 'b.example'], $values);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function commaSeparatedListDataProvider(): array
    {
        return [
            'denylisted hosts' => [AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, 'getClientDenylistHosts'],
            'denylisted software ids' => [AgenticCommerceConstants::CLIENT_DENYLIST_SOFTWARE_IDS, 'getClientDenylistSoftwareIds'],
            'metadata document host allowlist' => [AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST, 'getClientIdMetadataDocumentHostAllowlist'],
        ];
    }

    public function testGivenAListWhenReadingTheDenylistedHostsThenItsNonEmptyValuesAreReturnedTrimmed(): void
    {
        // Arrange
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, ['a.example', ' b.example ', '']);

        // Act
        $denylistedHosts = (new AgenticCommerceConfig())->getClientDenylistHosts();

        // Assert
        $this->assertSame(['a.example', 'b.example'], $denylistedHosts);
    }
}
