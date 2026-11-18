<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;

class ClientMetadataMapper implements ClientMetadataMapperInterface
{
    /**
     * @var array<string, string>
     */
    protected const array STRING_FIELDS = [
        'client_id' => AgenticCommerceClientMetadataTransfer::CLIENT_ID,
        'client_name' => AgenticCommerceClientMetadataTransfer::CLIENT_NAME,
        'token_endpoint_auth_method' => AgenticCommerceClientMetadataTransfer::TOKEN_ENDPOINT_AUTH_METHOD,
        'scope' => AgenticCommerceClientMetadataTransfer::SCOPE,
        'client_uri' => AgenticCommerceClientMetadataTransfer::CLIENT_URI,
        'logo_uri' => AgenticCommerceClientMetadataTransfer::LOGO_URI,
        'software_id' => AgenticCommerceClientMetadataTransfer::SOFTWARE_ID,
        'software_version' => AgenticCommerceClientMetadataTransfer::SOFTWARE_VERSION,
    ];

    /**
     * @var array<string, string>
     */
    protected const array LIST_FIELDS = [
        'redirect_uris' => AgenticCommerceClientMetadataTransfer::REDIRECT_URIS,
        'token_endpoint_auth_methods_supported' => AgenticCommerceClientMetadataTransfer::TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED,
        'grant_types' => AgenticCommerceClientMetadataTransfer::GRANT_TYPES,
        'response_types' => AgenticCommerceClientMetadataTransfer::RESPONSE_TYPES,
    ];

    /**
     * Optional URIs that some clients send as an empty string instead of leaving them out.
     *
     * @var list<string>
     */
    protected const array EMPTY_MEANS_ABSENT_FIELDS = [
        AgenticCommerceClientMetadataTransfer::CLIENT_URI,
        AgenticCommerceClientMetadataTransfer::LOGO_URI,
    ];

    /**
     * @param array<mixed> $content
     */
    public function mapRegistrationFields(array $content): AgenticCommerceClientMetadataTransfer
    {
        $mappedFields = [];

        foreach (static::STRING_FIELDS as $field => $property) {
            $mappedFields[$property] = is_string($content[$field] ?? null) ? $content[$field] : null;
        }

        foreach (static::EMPTY_MEANS_ABSENT_FIELDS as $property) {
            $mappedFields[$property] = $mappedFields[$property] === '' ? null : $mappedFields[$property];
        }

        foreach (static::LIST_FIELDS as $field => $property) {
            $mappedFields[$property] = $this->isListOfStrings($content[$field] ?? null) ? $content[$field] : [];
        }

        return (new AgenticCommerceClientMetadataTransfer())->fromArray($mappedFields, true);
    }

    protected function isListOfStrings(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && array_filter($value, is_string(...)) === $value;
    }
}
