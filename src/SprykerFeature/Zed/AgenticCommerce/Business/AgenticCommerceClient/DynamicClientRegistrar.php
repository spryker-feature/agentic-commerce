<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataValidationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use JsonException;
use Ramsey\Uuid\Uuid;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use stdClass;

class DynamicClientRegistrar implements DynamicClientRegistrarInterface
{
    /**
     * @var int<1, max>
     */
    protected const int JSON_MAX_DEPTH = 16;

    protected const string ERROR_DESCRIPTION_DISABLED = 'Dynamic client registration is disabled.';

    protected const string ERROR_DESCRIPTION_NOT_A_JSON_OBJECT = 'The request body must be a JSON object.';

    protected const string ERROR_DESCRIPTION_BODY_TOO_LARGE = 'The request body must not be larger than %d bytes.';

    protected const string ERROR_DESCRIPTION_SOFTWARE_ID_NOT_ACCEPTED = 'The software_id is not accepted.';

    protected const string ERROR_DESCRIPTION_REDIRECT_HOST_NOT_ACCEPTED = 'A redirect_uri host is not accepted.';

    public function __construct(
        protected ClientMetadataMapperInterface $clientMetadataMapper,
        protected ClientMetadataValidatorInterface $clientMetadataValidator,
        protected ClientScopeCeilingResolverInterface $clientScopeCeilingResolver,
        protected SelfRegisteredClientWriterInterface $selfRegisteredClientWriter,
        protected BlockedClientMatcherInterface $blockedClientMatcher,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function registerClient(
        AgenticCommerceClientRegistrationRequestTransfer $registrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer {
        if (!$this->agenticCommerceConfig->isDynamicClientRegistrationEnabled()) {
            return $this->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, static::ERROR_DESCRIPTION_DISABLED);
        }

        $rawRequest = (string)$registrationRequestTransfer->getRawRequest();
        $maxBytes = $this->agenticCommerceConfig->getDynamicClientRegistrationRequestMaxBytes();

        if (strlen($rawRequest) > $maxBytes) {
            return $this->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, sprintf(static::ERROR_DESCRIPTION_BODY_TOO_LARGE, $maxBytes));
        }

        $content = $this->decodeJsonObject($rawRequest);

        if ($content === null) {
            return $this->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, static::ERROR_DESCRIPTION_NOT_A_JSON_OBJECT);
        }

        $clientMetadataTransfer = $this->clientMetadataMapper->mapRegistrationFields($content);
        $validationResponseTransfer = $this->clientMetadataValidator->validate(
            $clientMetadataTransfer,
            $this->findAllowedRedirectHosts(),
            SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC,
        );

        if (!$validationResponseTransfer->getIsValid()) {
            return $this->createErrorResponse($validationResponseTransfer->getErrorCodeOrFail(), $validationResponseTransfer->getErrorDescriptionOrFail());
        }

        if ($this->blockedClientMatcher->hasBlockedClientWithSoftwareId($clientMetadataTransfer->getSoftwareId())) {
            return $this->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT_METADATA, static::ERROR_DESCRIPTION_SOFTWARE_ID_NOT_ACCEPTED);
        }

        if ($this->blockedClientMatcher->hasBlockedClientWithRedirectHost(array_values($clientMetadataTransfer->getRedirectUris()))) {
            return $this->createErrorResponse(SharedAgenticCommerceConfig::ERROR_INVALID_REDIRECT_URI, static::ERROR_DESCRIPTION_REDIRECT_HOST_NOT_ACCEPTED);
        }

        $agenticCommerceClientTransfer = $this->selfRegisteredClientWriter->createClient(
            $this->createClientTransfer($clientMetadataTransfer, $validationResponseTransfer, $rawRequest),
        );

        return (new AgenticCommerceClientRegistrationResponseTransfer())
            ->setIsSuccessful(true)
            ->setAgenticCommerceClient($agenticCommerceClientTransfer)
            ->setClientIdIssuedAt(time())
            ->setAcceptedScopes($validationResponseTransfer->getAcceptedScopes())
            ->setGrantTypes($validationResponseTransfer->getAcceptedGrantTypes());
    }

    protected function createClientTransfer(
        AgenticCommerceClientMetadataTransfer $clientMetadataTransfer,
        AgenticCommerceClientMetadataValidationResponseTransfer $validationResponseTransfer,
        string $rawRequest
    ): AgenticCommerceClientTransfer {
        return (new AgenticCommerceClientTransfer())
            ->setClientIdentifier(Uuid::uuid4()->toString())
            ->setName($validationResponseTransfer->getClientNameOrFail())
            ->setRedirectUris(array_values($clientMetadataTransfer->getRedirectUris()))
            ->setClientUri($clientMetadataTransfer->getClientUri())
            ->setLogoUri($clientMetadataTransfer->getLogoUri())
            ->setSoftwareId($clientMetadataTransfer->getSoftwareId())
            ->setSoftwareVersion($clientMetadataTransfer->getSoftwareVersion())
            ->setRegistrationMetadata($rawRequest)
            ->setRegistrationType(SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_DYNAMIC)
            ->setTrustLevel(SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED)
            ->setStatus(SharedAgenticCommerceConfig::CLIENT_STATUS_ACTIVE)
            ->setScopeCeiling($this->clientScopeCeilingResolver->resolveDynamicClientRegistrationScopeCeiling($clientMetadataTransfer->getSoftwareId()));
    }

    /**
     * Any mode other than open applies the allowlist; its hosts are compared in lower case, and empty entries are left out.
     *
     * @return list<string>|null
     */
    protected function findAllowedRedirectHosts(): ?array
    {
        if ($this->agenticCommerceConfig->getDynamicClientRegistrationMode() === SharedAgenticCommerceConfig::DYNAMIC_CLIENT_REGISTRATION_MODE_OPEN) {
            return null;
        }

        $allowedRedirectHosts = array_map(
            static fn (string $host): string => strtolower(trim($host)),
            $this->agenticCommerceConfig->getDynamicClientRegistrationRedirectHostAllowlist(),
        );

        return array_values(array_filter($allowedRedirectHosts, static fn (string $host): bool => $host !== ''));
    }

    /**
     * @return array<mixed>|null
     */
    protected function decodeJsonObject(string $rawRequest): ?array
    {
        try {
            if (!json_decode($rawRequest, false, static::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR) instanceof stdClass) {
                return null;
            }

            return json_decode($rawRequest, true, static::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    protected function createErrorResponse(string $errorCode, string $errorDescription): AgenticCommerceClientRegistrationResponseTransfer
    {
        return (new AgenticCommerceClientRegistrationResponseTransfer())
            ->setIsSuccessful(false)
            ->setErrorCode($errorCode)
            ->setErrorDescription($errorDescription);
    }
}
