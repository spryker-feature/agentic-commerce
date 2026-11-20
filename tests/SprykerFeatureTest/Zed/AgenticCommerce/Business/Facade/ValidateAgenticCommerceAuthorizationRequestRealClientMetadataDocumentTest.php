<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * The Client ID Metadata Documents (CIMD) of real clients, stored as fetched under `_data/client-metadata-documents/`.
 *
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group ValidateAgenticCommerceAuthorizationRequestRealClientMetadataDocumentTest
 * Add your own group annotations below this line
 */
class ValidateAgenticCommerceAuthorizationRequestRealClientMetadataDocumentTest extends Unit
{
    protected const string CHATGPT_CLIENT_ID = 'https://chatgpt.com/oauth/client.json';

    protected const string CLAUDE_AI_CLIENT_ID = 'https://claude.ai/oauth/mcp-oauth-client-metadata';

    protected const string CLAUDE_CODE_CLIENT_ID = 'https://claude.ai/oauth/claude-code-client-metadata';

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([
            AgenticCommerceHelper::SCOPE => 'agentic_commerce.scope.agentic-commerce.principal-read',
        ])]);
        $this->tester->allowAllHostsInTheAddressGuard();
    }

    public function testGivenChatGptsDocumentWhenAuthorizingThenTheClientIsCreatedAsAPublicClientAlthoughItPrefersPrivateKeyAuthentication(): void
    {
        // Arrange
        $this->tester->mockClientMetadataDocumentResponse($this->tester->createClientMetadataDocumentFixtureResponse('chatgpt'));
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier(static::CHATGPT_CLIENT_ID, 'https://chatgpt.com/connector_platform_oauth_redirect');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame('ChatGPT', $this->tester->findClient(static::CHATGPT_CLIENT_ID)->getName());
        $this->assertFalse($this->tester->findOauthClient(static::CHATGPT_CLIENT_ID)->getIsConfidential());
    }

    public function testGivenClaudeAiConnectorDocumentWhenAuthorizingThenTheUnsupportedJwtBearerGrantIsIgnored(): void
    {
        // Arrange
        $this->tester->mockClientMetadataDocumentResponse($this->tester->createClientMetadataDocumentFixtureResponse('claude-ai'));
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier(static::CLAUDE_AI_CLIENT_ID, 'https://claude.ai/api/mcp/auth_callback');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame('Claude', $this->tester->findClient(static::CLAUDE_AI_CLIENT_ID)->getName());
    }

    /**
     * @dataProvider claudeCodeRedirectUriDataProvider
     */
    public function testGivenClaudeCodesDocumentWithPortlessLoopbackRedirectsWhenAuthorizingFromAnEphemeralPortThenTheRequestIsValid(string $redirectUri): void
    {
        // Arrange
        $this->tester->mockClientMetadataDocumentResponse($this->tester->createClientMetadataDocumentFixtureResponse('claude-code'));
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier(static::CLAUDE_CODE_CLIENT_ID, $redirectUri);

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function claudeCodeRedirectUriDataProvider(): array
    {
        return [
            'localhost with ephemeral port' => ['http://localhost:54321/callback'],
            'ipv4 loopback with ephemeral port' => ['http://127.0.0.1:61000/callback'],
        ];
    }

    public function testGivenClaudeCodesDocumentWhenAuthorizingWithAnotherLoopbackPathThenTheRequestIsRefused(): void
    {
        // Arrange
        $this->tester->mockClientMetadataDocumentResponse($this->tester->createClientMetadataDocumentFixtureResponse('claude-code'));
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier(static::CLAUDE_CODE_CLIENT_ID, 'http://localhost:54321/other');

        // Act
        $authorizationResponseTransfer = $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertFalse($authorizationResponseTransfer->getIsRedirectable());
    }
}
