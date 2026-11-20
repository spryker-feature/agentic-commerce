<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response;

use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OauthProtocolResponseBuilder implements OauthProtocolResponseBuilderInterface
{
    protected const string RESPONSE_KEY_ACCESS_TOKEN = 'access_token';

    protected const string RESPONSE_KEY_TOKEN_TYPE = 'token_type';

    protected const string RESPONSE_KEY_EXPIRES_IN = 'expires_in';

    protected const string RESPONSE_KEY_REFRESH_TOKEN = 'refresh_token';

    protected const string RESPONSE_KEY_SCOPE = 'scope';

    protected const string RESPONSE_KEY_AUTHORIZATION_DETAILS = 'authorization_details';

    protected const string RESPONSE_KEY_ERROR = 'error';

    protected const string RESPONSE_KEY_ERROR_DESCRIPTION = 'error_description';

    protected const string RESPONSE_KEY_CLIENT_ID = 'client_id';

    protected const string RESPONSE_KEY_CLIENT_ID_ISSUED_AT = 'client_id_issued_at';

    protected const string RESPONSE_KEY_CLIENT_NAME = 'client_name';

    protected const string RESPONSE_KEY_REDIRECT_URIS = 'redirect_uris';

    protected const string RESPONSE_KEY_GRANT_TYPES = 'grant_types';

    protected const string RESPONSE_KEY_RESPONSE_TYPES = 'response_types';

    protected const string RESPONSE_KEY_TOKEN_ENDPOINT_AUTH_METHOD = 'token_endpoint_auth_method';

    protected const string RESPONSE_KEY_CLIENT_URI = 'client_uri';

    protected const string RESPONSE_KEY_LOGO_URI = 'logo_uri';

    protected const string RESPONSE_KEY_SOFTWARE_ID = 'software_id';

    protected const string RESPONSE_KEY_SOFTWARE_VERSION = 'software_version';

    protected const string SCOPE_SEPARATOR = ' ';

    protected const string TOKEN_TYPE_BEARER = 'Bearer';

    protected const string ERROR_TEMPORARILY_UNAVAILABLE = 'temporarily_unavailable';

    protected const string ERROR_DESCRIPTION_DELEGATED_ACCESS_DISABLED = 'Delegated access is temporarily disabled.';

    protected const string HEADER_RETRY_AFTER = 'Retry-After';

    protected const string ERROR_DESCRIPTION_SERVER_ERROR = 'The request could not be processed.';

    protected const array NO_STORE_HEADERS = ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache'];

    protected const array EMPTY_BODY_HEADERS = ['Content-Type' => 'text/plain; charset=UTF-8'];

    public function createTokenResponse(OauthResponseTransfer $oauthResponseTransfer): Response
    {
        $tokenResponse = [
            static::RESPONSE_KEY_ACCESS_TOKEN => $oauthResponseTransfer->getAccessTokenOrFail(),
            static::RESPONSE_KEY_TOKEN_TYPE => static::TOKEN_TYPE_BEARER,
            static::RESPONSE_KEY_EXPIRES_IN => $oauthResponseTransfer->getExpiresIn(),
            static::RESPONSE_KEY_REFRESH_TOKEN => $oauthResponseTransfer->getRefreshToken(),
            static::RESPONSE_KEY_SCOPE => $oauthResponseTransfer->getScope() ?: null,
            static::RESPONSE_KEY_AUTHORIZATION_DETAILS => $oauthResponseTransfer->getAuthorizationDetails() ?: null,
        ];

        return new JsonResponse(
            array_filter($tokenResponse, static fn (mixed $value): bool => $value !== null),
            Response::HTTP_OK,
            static::NO_STORE_HEADERS,
        );
    }

    public function createClientInformationResponse(AgenticCommerceClientRegistrationResponseTransfer $registrationResponseTransfer): Response
    {
        $agenticCommerceClientTransfer = $registrationResponseTransfer->getAgenticCommerceClientOrFail();
        $clientInformation = [
            static::RESPONSE_KEY_CLIENT_ID => $agenticCommerceClientTransfer->getClientIdentifierOrFail(),
            static::RESPONSE_KEY_CLIENT_ID_ISSUED_AT => $registrationResponseTransfer->getClientIdIssuedAtOrFail(),
            static::RESPONSE_KEY_CLIENT_NAME => $agenticCommerceClientTransfer->getName(),
            static::RESPONSE_KEY_REDIRECT_URIS => $agenticCommerceClientTransfer->getRedirectUris(),
            static::RESPONSE_KEY_GRANT_TYPES => $registrationResponseTransfer->getGrantTypes(),
            static::RESPONSE_KEY_RESPONSE_TYPES => [SharedAgenticCommerceConfig::RESPONSE_TYPE_CODE],
            static::RESPONSE_KEY_TOKEN_ENDPOINT_AUTH_METHOD => SharedAgenticCommerceConfig::TOKEN_ENDPOINT_AUTH_METHOD_NONE,
            static::RESPONSE_KEY_SCOPE => implode(static::SCOPE_SEPARATOR, $registrationResponseTransfer->getAcceptedScopes()) ?: null,
            static::RESPONSE_KEY_CLIENT_URI => $agenticCommerceClientTransfer->getClientUri(),
            static::RESPONSE_KEY_LOGO_URI => $agenticCommerceClientTransfer->getLogoUri(),
            static::RESPONSE_KEY_SOFTWARE_ID => $agenticCommerceClientTransfer->getSoftwareId(),
            static::RESPONSE_KEY_SOFTWARE_VERSION => $agenticCommerceClientTransfer->getSoftwareVersion(),
        ];

        return new JsonResponse(
            array_filter($clientInformation, static fn (mixed $value): bool => $value !== null),
            Response::HTTP_CREATED,
            static::NO_STORE_HEADERS,
        );
    }

    public function createErrorResponse(string $error, ?string $errorDescription = null): Response
    {
        $errorResponse = [static::RESPONSE_KEY_ERROR => $error];

        if ($errorDescription !== null && $errorDescription !== '') {
            $errorResponse[static::RESPONSE_KEY_ERROR_DESCRIPTION] = $errorDescription;
        }

        $status = match ($error) {
            SharedAgenticCommerceConfig::ERROR_SERVER_ERROR => Response::HTTP_INTERNAL_SERVER_ERROR,
            static::ERROR_TEMPORARILY_UNAVAILABLE => Response::HTTP_SERVICE_UNAVAILABLE,
            default => Response::HTTP_BAD_REQUEST,
        };

        return new JsonResponse($errorResponse, $status, static::NO_STORE_HEADERS);
    }

    public function createServerErrorResponse(): Response
    {
        return $this->createErrorResponse(SharedAgenticCommerceConfig::ERROR_SERVER_ERROR, static::ERROR_DESCRIPTION_SERVER_ERROR);
    }

    public function createDelegatedAccessDisabledResponse(int $retryAfterSeconds): Response
    {
        $response = $this->createErrorResponse(static::ERROR_TEMPORARILY_UNAVAILABLE, static::ERROR_DESCRIPTION_DELEGATED_ACCESS_DISABLED);
        $response->headers->set(static::HEADER_RETRY_AFTER, (string)$retryAfterSeconds);

        return $response;
    }

    public function createRevocationResponse(): Response
    {
        return new Response('', Response::HTTP_OK, static::NO_STORE_HEADERS + static::EMPTY_BODY_HEADERS);
    }

    /**
     * @param array<string, mixed> $document
     */
    public function createJsonDocumentResponse(array $document): Response
    {
        return new JsonResponse($document, Response::HTTP_OK);
    }

    public function createNotFoundResponse(): Response
    {
        return new Response('', Response::HTTP_NOT_FOUND, static::NO_STORE_HEADERS + static::EMPTY_BODY_HEADERS);
    }
}
