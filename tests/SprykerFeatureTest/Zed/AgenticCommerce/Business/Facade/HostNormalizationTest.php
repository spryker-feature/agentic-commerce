<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use GuzzleHttp\Psr7\Response;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConstants;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerFeatureTest\Zed\AgenticCommerce\AgenticCommerceBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group AgenticCommerce
 * @group Business
 * @group Facade
 * @group HostNormalizationTest
 * Add your own group annotations below this line
 */
class HostNormalizationTest extends Unit
{
    protected const string DENYLISTED_HOST = 'evil.example';

    protected const string REDIRECT_URI = 'https://agents.example.com/callback';

    protected const string ERROR_INVALID_CLIENT = 'invalid_client';

    protected const string ERROR_REDIRECT_HOST_INVALID = 'agentic_commerce.client.validation.redirect_host_invalid';

    protected const int HTTP_STATUS_OK = 200;

    protected const int CACHE_LIFETIME_SECONDS = 3600;

    protected const string EXTENSION_INTL = 'intl';

    protected const string PATTERN_NON_ASCII = '/[^\x00-\x7F]/';

    protected AgenticCommerceBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setConfig(AgenticCommerceConstants::DYNAMIC_CLIENT_REGISTRATION_ENABLED, true);
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_ENABLED, true);
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN);
        $this->tester->setScopeProviderPlugins([$this->tester->createScopeProviderPlugin([
            AgenticCommerceHelper::SCOPE => 'agentic_commerce.scope.agentic-commerce.principal-read',
        ])]);
        $this->tester->allowAllHostsInTheAddressGuard();
    }

    /**
     * @dataProvider denylistedRedirectHostSpellingDataProvider
     */
    public function testGivenADenylistedHostWhenAClientRegistersWithAnotherSpellingOfItThenRegistrationFailsAndNothingIsCreated(
        string $denylistedHost,
        string $redirectUri
    ): void {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts($denylistedHost, $redirectUri);
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, [$denylistedHost]);
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->registerClient($redirectUri);

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function denylistedRedirectHostSpellingDataProvider(): array
    {
        return [
            'percent-encoded letter' => [static::DENYLISTED_HOST, 'https://ev%69l.example/callback'],
            'percent-encoded dot' => [static::DENYLISTED_HOST, 'https://evil%2Eexample/callback'],
            'trailing dot' => [static::DENYLISTED_HOST, 'https://evil.example./callback'],
            'upper case' => [static::DENYLISTED_HOST, 'https://EVIL.example/callback'],
            'unicode host against a punycode entry' => ['xn--bcher-kva.example', 'https://bücher.example/callback'],
            'punycode host against a unicode entry' => ['bücher.example', 'https://xn--bcher-kva.example/callback'],
            'ideographic full stop as trailing dot' => [static::DENYLISTED_HOST, 'https://evil.example。/callback'],
            'percent-encoded ideographic full stop as trailing dot' => [static::DENYLISTED_HOST, 'https://evil.example%E3%80%82/callback'],
            'percent-encoded fullwidth full stop as trailing dot' => [static::DENYLISTED_HOST, 'https://evil.example%EF%BC%8E/callback'],
            'expanded IPv6 address' => ['[::1]', 'https://[0:0:0:0:0:0:0:1]/callback'],
            'IPv6 address with a zero group' => ['[::1]', 'https://[0::1]/callback'],
            'compressed IPv6 entry against an expanded address' => ['[0:0:0:0:0:0:0:1]', 'https://[::1]/callback'],
            'upper-case IPv6 address' => ['[2001:db8::a]', 'https://[2001:DB8:0::A]/callback'],
            'short IPv4 address' => ['127.0.0.1', 'https://127.1/callback'],
            'decimal IPv4 address' => ['127.0.0.1', 'https://2130706433/callback'],
            'hexadecimal IPv4 address' => ['127.0.0.1', 'https://0x7f.0.0.1/callback'],
            'IPv4 address with a leading zero' => ['127.0.0.1', 'https://127.0.0.01/callback'],
        ];
    }

    /**
     * @dataProvider invalidRedirectHostDataProvider
     */
    public function testGivenARedirectHostThatIsNotAValidHostAfterDecodingWhenAClientRegistersThenRegistrationFailsAndNothingIsCreated(
        string $redirectUri
    ): void {
        // Arrange
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->registerClient($redirectUri);

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidRedirectHostDataProvider(): array
    {
        return [
            'double percent-encoding' => ['https://evil%2569.example/callback'],
            'encoded null byte' => ['https://evil%00.example/callback'],
            'encoded slash' => ['https://evil.example%2Fpath.example/callback'],
            'two percent-encoded trailing dots' => ['https://evil.example%2E%2E/callback'],
        ];
    }

    public function testGivenARedirectHostBlockWhenAClientRegistersWithAPercentEncodedSpellingOfThatHostThenRegistrationFailsAndNothingIsCreated(): void
    {
        // Arrange
        $host = sprintf('%s.example', uniqid());
        $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://evil.%s/callback', $host)],
        ]);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions(
                (new AgenticCommerceClientConditionsTransfer())->addRedirectHost(sprintf('evil.%s', $host)),
            ),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->registerClient(sprintf('https://%%65vil.%s/other-callback', $host));

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    public function testGivenADenylistedHostWhenAClientIdOnAPercentEncodedSpellingOfThatHostIsResolvedThenItIsRefusedWithoutFetching(): void
    {
        // Arrange
        $clientIdentifier = 'https://agents.ex%61mple.com/client.json';
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse($clientIdentifier));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization($clientIdentifier);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(static::ERROR_INVALID_CLIENT, $authorizationResponseTransfer->getErrorCode());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenADenylistedHostWhenAClientIdOnThatHostWithAnEncodedUnicodeTrailingDotIsResolvedThenItIsRefusedWithoutFetching(): void
    {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts('agents.example.com%E3%80%82');
        $clientIdentifier = 'https://agents.example.com%E3%80%82/client.json';
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_DENYLIST_HOSTS, ['agents.example.com']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse($clientIdentifier));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization($clientIdentifier);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_HOST_DENYLISTED, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenAHostAllowlistOfOnlyInvalidEntriesWhenAClientIdIsResolvedThenAConfigurationExceptionIsThrown(): void
    {
        // Arrange
        $clientIdentifier = 'https://agents.example.com/client.json';
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST, ['*.claude.ai']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse($clientIdentifier));

        // Assert
        $this->expectException(AgenticCommerceConfigurationException::class);

        // Act
        $this->resolveClientForAuthorization($clientIdentifier);
    }

    /**
     * @dataProvider redirectHostBlockSpellingDataProvider
     */
    public function testGivenAClientWhenBlockingByAnotherSpellingOfItsRedirectHostThenItIsBlockedByTheNormalizedHost(
        string $storedRedirectUriPattern,
        string $redirectHostPattern,
        string $normalizedHostPattern
    ): void {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts($storedRedirectUriPattern, $redirectHostPattern);
        $label = uniqid('agent');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf($storedRedirectUriPattern, $label)],
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions(
                (new AgenticCommerceClientConditionsTransfer())->addRedirectHost(sprintf($redirectHostPattern, $label)),
            ),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $storedClientTransfer = $this->tester->findClient($clientTransfer->getClientIdentifierOrFail());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_BLOCKED, $storedClientTransfer->getStatus());
        $this->assertSame(
            AgenticCommerceConfig::CLIENT_BLOCKED_BY_PREFIX_REDIRECT_HOST . sprintf($normalizedHostPattern, $label),
            $storedClientTransfer->getBlockedBy(),
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public function redirectHostBlockSpellingDataProvider(): array
    {
        return [
            'unicode stored, punycode selector' => ['https://%s.bücher.example/callback', '%s.xn--bcher-kva.example', '%s.xn--bcher-kva.example'],
            'punycode stored, unicode selector' => ['https://%s.xn--bcher-kva.example/callback', '%s.Bücher.example', '%s.xn--bcher-kva.example'],
            'trailing dot stored' => ['https://%s.evil.example./callback', '%s.evil.example', '%s.evil.example'],
            'percent-encoded stored' => ['https://%s.ev%%69l.example/callback', '%s.evil.example', '%s.evil.example'],
            'trailing dot selector' => ['https://%s.evil.example/callback', '%s.evil.example.', '%s.evil.example'],
            'percent-encoded selector' => ['https://%s.evil.example/callback', '%s.ev%%69l.example', '%s.evil.example'],
        ];
    }

    public function testGivenAClientBlockedByAUnicodeSpellingOfItsRedirectHostWhenAClientOfThePunycodeSpellingRegistersThenRegistrationFails(): void
    {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts('bücher.example');
        $label = uniqid('agent');
        $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s.bücher.example/callback', $label)],
        ]);
        $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions(
                (new AgenticCommerceClientConditionsTransfer())->addRedirectHost(sprintf('%s.bücher.example', $label)),
            ),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );
        $clientCountBefore = $this->tester->countClients();

        // Act
        $registrationResponseTransfer = $this->registerClient(sprintf('https://%s.xn--bcher-kva.example/other-callback', $label));

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
        $this->assertSame($clientCountBefore, $this->tester->countClients());
    }

    public function testGivenARedirectHostThatIsNotAValidHostWhenBlockingByItThenARedirectHostInvalidErrorIsReturnedAndNothingIsBlocked(): void
    {
        // Arrange
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => ['https://agents.example.com/callback'],
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateAgenticCommerceClientStatusByCriteria(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions(
                (new AgenticCommerceClientConditionsTransfer())->addRedirectHost('agents%2525.example.com'),
            ),
            AgenticCommerceConfig::CLIENT_STATUS_BLOCKED,
        );

        // Assert
        $this->assertSame(static::ERROR_REDIRECT_HOST_INVALID, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertSame(AgenticCommerceConfig::CLIENT_STATUS_ACTIVE, $this->tester->findClient($clientTransfer->getClientIdentifierOrFail())->getStatus());
    }

    public function testGivenAUnicodeRedirectHostConditionWhenReadingClientsThenTheClientWithThePunycodeSpellingIsReturned(): void
    {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts('bücher.example');
        $label = uniqid('agent');
        $clientTransfer = $this->tester->haveAgenticCommerceClient([
            AgenticCommerceClientTransfer::REDIRECT_URIS => [sprintf('https://%s.xn--bcher-kva.example./callback', $label)],
        ]);

        // Act
        $clientCollectionTransfer = $this->tester->getFacade()->getAgenticCommerceClientCollection(
            (new AgenticCommerceClientCriteriaTransfer())->setAgenticCommerceClientConditions(
                (new AgenticCommerceClientConditionsTransfer())->addRedirectHost(sprintf('%s.BÜCHER.example', $label)),
            ),
        );

        // Assert
        $this->assertCount(1, $clientCollectionTransfer->getAgenticCommerceClients());
        $this->assertSame($clientTransfer->getClientIdentifierOrFail(), $clientCollectionTransfer->getAgenticCommerceClients()->offsetGet(0)->getClientIdentifier());
    }

    public function testGivenAClientIdWhoseHostIsNotAValidHostAfterDecodingWhenItIsResolvedThenItIsRefusedAsAnInvalidUrlWithoutFetching(): void
    {
        // Arrange
        $clientIdentifier = 'https://agents%2525.example.com/client.json';
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse($clientIdentifier));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization($clientIdentifier);

        // Assert
        $this->assertFalse($authorizationResponseTransfer->getIsValid());
        $this->assertSame(AgenticCommerceConfig::CLIENT_RESOLVE_FAILURE_INVALID_URL, $authorizationResponseTransfer->getFailureReason());
        $this->assertSame(0, $this->tester->getHttpRequestCount());
    }

    public function testGivenAHostAllowlistInUnicodeWhenAClientIdOnThePunycodeSpellingOfThatHostIsResolvedThenTheDocumentIsFetched(): void
    {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts('bücher.example');
        $clientIdentifier = 'https://xn--bcher-kva.example/client.json';
        $this->tester->setConfig(AgenticCommerceConstants::CLIENT_ID_METADATA_DOCUMENT_HOST_ALLOWLIST, ['Bücher.example.']);
        $this->tester->mockClientMetadataDocumentResponse($this->createDocumentResponse($clientIdentifier));

        // Act
        $authorizationResponseTransfer = $this->resolveClientForAuthorization($clientIdentifier);

        // Assert
        $this->assertTrue($authorizationResponseTransfer->getIsValid());
        $this->assertSame(1, $this->tester->getHttpRequestCount());
    }

    public function testGivenARedirectHostAllowlistWhenAClientRegistersWithAPercentEncodedSpellingOfAnotherHostThenRegistrationFails(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_ALLOWLIST);
        $this->tester->mockConfigMethod('getDynamicClientRegistrationRedirectHostAllowlist', ['claude.ai']);

        // Act
        $registrationResponseTransfer = $this->registerClient('https://claude.ai%2Eevil.example/callback');

        // Assert
        $this->assertFalse($registrationResponseTransfer->getIsSuccessful());
        $this->assertSame(AgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, $registrationResponseTransfer->getErrorCode());
    }

    public function testGivenARedirectHostAllowlistInUnicodeWhenAClientRegistersWithThePunycodeSpellingOfThatHostThenRegistrationSucceeds(): void
    {
        // Arrange
        $this->skipWithoutIntlForNonAsciiHosts('bücher.example');
        $this->tester->mockConfigMethod('getDynamicClientRegistrationMode', AgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_ALLOWLIST);
        $this->tester->mockConfigMethod('getDynamicClientRegistrationRedirectHostAllowlist', ['bücher.example']);

        // Act
        $registrationResponseTransfer = $this->registerClient('https://xn--bcher-kva.example/callback');

        // Assert
        $this->assertTrue($registrationResponseTransfer->getIsSuccessful());
    }

    /**
     * Unicode hosts are converted to their ASCII form only with the intl extension; without it they are refused as invalid hosts.
     */
    protected function skipWithoutIntlForNonAsciiHosts(string ...$hosts): void
    {
        if (extension_loaded(static::EXTENSION_INTL)) {
            return;
        }

        foreach ($hosts as $host) {
            if (preg_match(static::PATTERN_NON_ASCII, rawurldecode($host)) === 1) {
                $this->markTestSkipped(sprintf('The extension "%s" is needed to convert a Unicode host.', static::EXTENSION_INTL));
            }
        }
    }

    protected function registerClient(string $redirectUri): AgenticCommerceClientRegistrationResponseTransfer
    {
        return $this->tester->getFacade()->registerAgenticCommerceClient(
            (new AgenticCommerceClientRegistrationRequestTransfer())->setRawRequest(json_encode([
                'client_name' => 'Agent',
                'redirect_uris' => [$redirectUri],
                'token_endpoint_auth_method' => 'none',
                'scope' => AgenticCommerceHelper::SCOPE,
            ], JSON_THROW_ON_ERROR)),
        );
    }

    protected function createDocumentResponse(string $clientIdentifier): Response
    {
        return new Response(
            static::HTTP_STATUS_OK,
            ['content-type' => 'application/json', 'cache-control' => 'max-age=' . static::CACHE_LIFETIME_SECONDS],
            json_encode([
                'client_id' => $clientIdentifier,
                'client_name' => 'Agent',
                'redirect_uris' => [static::REDIRECT_URI],
                'token_endpoint_auth_method' => 'none',
            ], JSON_THROW_ON_ERROR),
        );
    }

    protected function resolveClientForAuthorization(string $clientIdentifier): AgenticCommerceAuthorizationResponseTransfer
    {
        $authorizationRequestTransfer = $this->tester->createValidAuthorizationRequestForClientIdentifier($clientIdentifier, static::REDIRECT_URI);

        return $this->tester->getFacade()->validateAgenticCommerceAuthorizationRequest($authorizationRequestTransfer);
    }
}
