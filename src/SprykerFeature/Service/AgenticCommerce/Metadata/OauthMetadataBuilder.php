<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Metadata;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationServerMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceProtectedResourceMetadataTransfer;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Service\AgenticCommerce\Resource\ProtectedResourceReaderInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class OauthMetadataBuilder implements OauthMetadataBuilderInterface
{
    // RFC 6750 (Bearer Token Usage) section 2.1: clients send the access token in the Authorization header only.
    protected const string BEARER_METHOD_HEADER = 'header';

    // Clients are public clients that prove possession with a code verifier instead of a secret.
    protected const string CLIENT_AUTHENTICATION_METHOD_NONE = 'none';

    public function __construct(
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected ProtectedResourceReaderInterface $protectedResourceReader
    ) {
    }

    public function buildProtectedResourceMetadata(): AgenticCommerceProtectedResourceMetadataTransfer
    {
        return (new AgenticCommerceProtectedResourceMetadataTransfer())
            ->setResource($this->agenticCommerceConfig->getResourceUri())
            ->setAuthorizationServers([$this->agenticCommerceConfig->getIssuer()])
            ->setScopesSupported($this->getConfiguredResourceScopes())
            ->setBearerMethodsSupported([static::BEARER_METHOD_HEADER]);
    }

    public function buildAuthorizationServerMetadata(): AgenticCommerceAuthorizationServerMetadataTransfer
    {
        $authorizationServerMetadataTransfer = (new AgenticCommerceAuthorizationServerMetadataTransfer())
            ->setIssuer($this->agenticCommerceConfig->getIssuer())
            ->setAuthorizationEndpoint($this->agenticCommerceConfig->getAuthorizationEndpoint())
            ->setTokenEndpoint($this->agenticCommerceConfig->getTokenEndpoint())
            ->setRevocationEndpoint($this->agenticCommerceConfig->getRevocationEndpoint())
            ->setResponseTypesSupported([SharedAgenticCommerceConfig::RESPONSE_TYPE_CODE])
            ->setGrantTypesSupported([SharedAgenticCommerceConfig::GRANT_TYPE_AUTHORIZATION_CODE, SharedAgenticCommerceConfig::GRANT_TYPE_REFRESH_TOKEN])
            ->setCodeChallengeMethodsSupported([SharedAgenticCommerceConfig::CODE_CHALLENGE_METHOD_S256])
            ->setTokenEndpointAuthMethodsSupported([static::CLIENT_AUTHENTICATION_METHOD_NONE])
            ->setRevocationEndpointAuthMethodsSupported([static::CLIENT_AUTHENTICATION_METHOD_NONE])
            ->setScopesSupported($this->getSupportedScopes())
            ->setAuthorizationResponseIssParameterSupported(true);

        if ($this->agenticCommerceConfig->isClientIdMetadataDocumentEnabled()) {
            $authorizationServerMetadataTransfer->setClientIdMetadataDocumentSupported(true);
        }

        $registrationEndpoint = $this->agenticCommerceConfig->getRegistrationEndpoint();

        if ($this->agenticCommerceConfig->isDynamicClientRegistrationEnabled() && $registrationEndpoint !== '') {
            $authorizationServerMetadataTransfer->setRegistrationEndpoint($registrationEndpoint);
        }

        return $authorizationServerMetadataTransfer;
    }

    /**
     * The scopes the resource at the configured resource URI declares first, then the configured scopes.
     *
     * @return list<string>
     */
    protected function getConfiguredResourceScopes(): array
    {
        $configuredResourceTransfer = $this->protectedResourceReader->findProtectedResourceByResourceUri($this->agenticCommerceConfig->getResourceUri());

        return array_values(array_unique([...($configuredResourceTransfer?->getScopes() ?? []), ...$this->agenticCommerceConfig->getSupportedScopes()]));
    }

    /**
     * The scopes of the declared resources first, then the configured scopes.
     *
     * @return list<string>
     */
    protected function getSupportedScopes(): array
    {
        $supportedScopes = [];

        foreach ($this->protectedResourceReader->getProtectedResources() as $protectedResourceTransfer) {
            $supportedScopes = [...$supportedScopes, ...$protectedResourceTransfer->getScopes()];
        }

        return array_values(array_unique([...$supportedScopes, ...$this->agenticCommerceConfig->getSupportedScopes()]));
    }
}
