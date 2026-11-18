<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Service\AgenticCommerce\Resource;

use Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer;
use Psr\Log\LoggerInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;

class ProtectedResourceReader implements ProtectedResourceReaderInterface
{
    public const string PATH_SEPARATOR = '/';

    /**
     * RFC 9728 (OAuth 2.0 Protected Resource Metadata), section 3.1: the well-known path segment sits between the authority and the resource path.
     */
    protected const string METADATA_PATH_SEGMENT = '/.well-known/oauth-protected-resource';

    /**
     * A path prefix matches itself and every path below it, never a path that only starts with the same characters.
     */
    protected const string PATH_PREFIX_PATTERN_FORMAT = '^%s(?:/|$)';

    protected const string ALTERNATIVE_PATTERN_FORMAT = '(?:%s)';

    protected const string ALTERNATIVE_SEPARATOR = '|';

    /**
     * The access token validation of OauthApi uses only patterns anchored at the start of the path.
     */
    protected const string ANCHORED_PATTERN_FORMAT = '^(?:%s)';

    protected const string LOG_MESSAGE_INVALID_DECLARATION = 'The declared protected resources are invalid, so the storefront treats them as not declared: %s';

    /**
     * @var list<\Generated\Shared\Transfer\AgenticCommerceProtectedResourceTransfer>|null
     */
    protected ?array $protectedResourceTransfers = null;

    protected bool $isDeclarationInvalid = false;

    /**
     * @param list<\SprykerFeature\Service\AgenticCommerce\Dependency\Plugin\AgenticCommerceProtectedResourceProviderPluginInterface> $protectedResourceProviderPlugins
     */
    public function __construct(
        protected array $protectedResourceProviderPlugins,
        protected ProtectedResourceValidatorInterface $protectedResourceValidator,
        protected LoggerInterface $logger
    ) {
    }

    public function getProtectedResources(): array
    {
        if ($this->protectedResourceTransfers !== null) {
            return $this->protectedResourceTransfers;
        }

        $protectedResourceTransfers = [];

        foreach ($this->protectedResourceProviderPlugins as $protectedResourceProviderPlugin) {
            foreach ($protectedResourceProviderPlugin->getProtectedResources() as $protectedResourceTransfer) {
                $this->protectedResourceValidator->validate($protectedResourceTransfer, $protectedResourceTransfers);
                $protectedResourceTransfers[] = $protectedResourceTransfer;
            }
        }

        $this->protectedResourceTransfers = $protectedResourceTransfers;

        return $this->protectedResourceTransfers;
    }

    public function getProtectedResourcesFailClosed(): array
    {
        if ($this->isDeclarationInvalid) {
            return [];
        }

        try {
            return $this->getProtectedResources();
        } catch (AgenticCommerceConfigurationException $agenticCommerceConfigurationException) {
            $this->isDeclarationInvalid = true;
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_INVALID_DECLARATION, $agenticCommerceConfigurationException->getMessage()),
                ['exception' => $agenticCommerceConfigurationException],
            );

            return [];
        }
    }

    public function findProtectedResourceByResourceUri(string $resourceUri): ?AgenticCommerceProtectedResourceTransfer
    {
        foreach ($this->getProtectedResources() as $protectedResourceTransfer) {
            if ($protectedResourceTransfer->getResourceUri() === $resourceUri) {
                return $protectedResourceTransfer;
            }
        }

        return null;
    }

    public function findProtectedResourceByPath(string $path): ?AgenticCommerceProtectedResourceTransfer
    {
        $matchedResourceTransfer = null;

        foreach ($this->getProtectedResourcesFailClosed() as $protectedResourceTransfer) {
            $pathPrefix = (string)$protectedResourceTransfer->getPathPrefix();

            if ($path !== $pathPrefix && !str_starts_with($path, $pathPrefix . static::PATH_SEPARATOR)) {
                continue;
            }

            if ($matchedResourceTransfer === null || strlen($pathPrefix) > strlen((string)$matchedResourceTransfer->getPathPrefix())) {
                $matchedResourceTransfer = $protectedResourceTransfer;
            }
        }

        return $matchedResourceTransfer;
    }

    public function findProtectedResourceByMetadataPath(string $resourcePath): ?AgenticCommerceProtectedResourceTransfer
    {
        $path = static::PATH_SEPARATOR . $resourcePath;

        foreach ($this->getProtectedResourcesFailClosed() as $protectedResourceTransfer) {
            if (parse_url((string)$protectedResourceTransfer->getResourceUri(), PHP_URL_PATH) === $path) {
                return $protectedResourceTransfer;
            }
        }

        return null;
    }

    public function getDelegatedAccessPathPattern(): string
    {
        $alternativePatterns = [sprintf(static::ALTERNATIVE_PATTERN_FORMAT, SharedAgenticCommerceConfig::PUBLIC_PROTOCOL_PATH_PATTERN)];

        foreach ($this->getProtectedResourcesFailClosed() as $protectedResourceTransfer) {
            $alternativePatterns[] = sprintf(
                static::ALTERNATIVE_PATTERN_FORMAT,
                sprintf(static::PATH_PREFIX_PATTERN_FORMAT, preg_quote($protectedResourceTransfer->getPathPrefixOrFail())),
            );
        }

        return sprintf(static::ANCHORED_PATTERN_FORMAT, implode(static::ALTERNATIVE_SEPARATOR, $alternativePatterns));
    }

    public function getProtectedResourceMetadataUrl(AgenticCommerceProtectedResourceTransfer $agenticCommerceProtectedResourceTransfer): string
    {
        $resourceUriParts = parse_url($agenticCommerceProtectedResourceTransfer->getResourceUriOrFail());
        $port = isset($resourceUriParts['port']) ? ':' . $resourceUriParts['port'] : '';

        return sprintf(
            '%s://%s%s%s%s',
            $resourceUriParts['scheme'] ?? '',
            $resourceUriParts['host'] ?? '',
            $port,
            static::METADATA_PATH_SEGMENT,
            $resourceUriParts['path'] ?? '',
        );
    }
}
