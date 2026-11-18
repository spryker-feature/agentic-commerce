<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\OauthClientTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class AgenticCommerceClientCreator implements AgenticCommerceClientCreatorInterface
{
    use TransactionTrait;

    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_IDENTIFIER_TAKEN = 'agentic_commerce.client.validation.client_identifier_taken';

    protected const string ERROR_NAME_REQUIRED = 'agentic_commerce.client.validation.name_required';

    protected const string ERROR_REDIRECT_URIS_REQUIRED = 'agentic_commerce.client.validation.redirect_uris_required';

    protected const string ERROR_REDIRECT_URI_INVALID = 'agentic_commerce.client.validation.redirect_uri_invalid';

    protected const string ERROR_SCOPE_CEILING_REQUIRED = 'agentic_commerce.client.validation.scope_ceiling_required';

    protected const string ERROR_SCOPE_UNSUPPORTED = 'agentic_commerce.client.validation.scope_unsupported';

    protected const string ERROR_CLIENT_URI_INVALID = 'agentic_commerce.client.validation.client_uri_invalid';

    protected const string ERROR_LOGO_URI_INVALID = 'agentic_commerce.client.validation.logo_uri_invalid';

    protected const string ERROR_TRUST_LEVEL_UNSUPPORTED = 'agentic_commerce.client.validation.trust_level_unsupported';

    /**
     * @var list<string>
     */
    protected const array SUPPORTED_TRUST_LEVELS = [
        SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED,
    ];

    public function __construct(
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected OauthFacadeInterface $oauthFacade,
        protected RedirectUriValidatorInterface $redirectUriValidator,
        protected SupportedScopeReaderInterface $supportedScopeReader
    ) {
    }

    public function createAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        $agenticCommerceClientCollectionResponseTransfer = new AgenticCommerceClientCollectionResponseTransfer();
        $validAgenticCommerceClientTransfers = [];
        $requestedClientIdentifiers = [];

        foreach ($agenticCommerceClientCollectionRequestTransfer->getAgenticCommerceClients() as $index => $agenticCommerceClientTransfer) {
            $errorMessages = $this->validateAgenticCommerceClient($agenticCommerceClientTransfer, $requestedClientIdentifiers);
            $requestedClientIdentifiers[] = (string)$agenticCommerceClientTransfer->getClientIdentifier();

            foreach ($errorMessages as $errorMessage) {
                $agenticCommerceClientCollectionResponseTransfer->addError(
                    (new ErrorTransfer())->setMessage($errorMessage)->setEntityIdentifier((string)$index),
                );
            }

            if ($errorMessages === []) {
                $validAgenticCommerceClientTransfers[] = $agenticCommerceClientTransfer;
            }
        }

        if ($agenticCommerceClientCollectionRequestTransfer->getIsTransactional() && $agenticCommerceClientCollectionResponseTransfer->getErrors()->count() > 0) {
            return $agenticCommerceClientCollectionResponseTransfer;
        }

        return $this->getTransactionHandler()->handleTransaction(function () use ($validAgenticCommerceClientTransfers, $agenticCommerceClientCollectionResponseTransfer) {
            return $this->executeCreateAgenticCommerceClientCollectionTransaction($validAgenticCommerceClientTransfers, $agenticCommerceClientCollectionResponseTransfer);
        });
    }

    /**
     * @param list<\Generated\Shared\Transfer\AgenticCommerceClientTransfer> $agenticCommerceClientTransfers
     */
    protected function executeCreateAgenticCommerceClientCollectionTransaction(
        array $agenticCommerceClientTransfers,
        AgenticCommerceClientCollectionResponseTransfer $agenticCommerceClientCollectionResponseTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        foreach ($agenticCommerceClientTransfers as $agenticCommerceClientTransfer) {
            $agenticCommerceClientCollectionResponseTransfer->addAgenticCommerceClient(
                $this->persistAgenticCommerceClient($agenticCommerceClientTransfer),
            );
        }

        return $agenticCommerceClientCollectionResponseTransfer;
    }

    /**
     * @param list<string> $requestedClientIdentifiers
     *
     * @return list<string>
     */
    protected function validateAgenticCommerceClient(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer,
        array $requestedClientIdentifiers
    ): array {
        $errorMessages = [];
        $clientIdentifier = (string)$agenticCommerceClientTransfer->getClientIdentifier();

        if ($clientIdentifier === '') {
            $errorMessages[] = static::ERROR_CLIENT_IDENTIFIER_REQUIRED;
        }

        if ($clientIdentifier !== '' && ($this->isClientIdentifierTaken($clientIdentifier) || in_array($clientIdentifier, $requestedClientIdentifiers, true))) {
            $errorMessages[] = static::ERROR_CLIENT_IDENTIFIER_TAKEN;
        }

        if ((string)$agenticCommerceClientTransfer->getName() === '') {
            $errorMessages[] = static::ERROR_NAME_REQUIRED;
        }

        if ($agenticCommerceClientTransfer->getRedirectUris() === []) {
            $errorMessages[] = static::ERROR_REDIRECT_URIS_REQUIRED;
        }

        if (array_filter($agenticCommerceClientTransfer->getRedirectUris(), fn (string $redirectUri): bool => !$this->redirectUriValidator->isRedirectUriAllowed($redirectUri)) !== []) {
            $errorMessages[] = static::ERROR_REDIRECT_URI_INVALID;
        }

        if ($agenticCommerceClientTransfer->getScopeCeiling() === []) {
            $errorMessages[] = static::ERROR_SCOPE_CEILING_REQUIRED;
        }

        if (array_diff($agenticCommerceClientTransfer->getScopeCeiling(), $this->supportedScopeReader->getSupportedScopeNames()) !== []) {
            $errorMessages[] = static::ERROR_SCOPE_UNSUPPORTED;
        }

        if (!$this->redirectUriValidator->isOptionalHttpsUri($agenticCommerceClientTransfer->getClientUri())) {
            $errorMessages[] = static::ERROR_CLIENT_URI_INVALID;
        }

        if (!$this->redirectUriValidator->isOptionalHttpsUri($agenticCommerceClientTransfer->getLogoUri())) {
            $errorMessages[] = static::ERROR_LOGO_URI_INVALID;
        }

        $trustLevel = $agenticCommerceClientTransfer->getTrustLevel();

        if ($trustLevel !== null && !in_array($trustLevel, static::SUPPORTED_TRUST_LEVELS, true)) {
            $errorMessages[] = static::ERROR_TRUST_LEVEL_UNSUPPORTED;
        }

        return $errorMessages;
    }

    protected function isClientIdentifierTaken(string $clientIdentifier): bool
    {
        return $this->oauthFacade->findClientByIdentifier((new OauthClientTransfer())->setIdentifier($clientIdentifier)) !== null;
    }

    protected function persistAgenticCommerceClient(
        AgenticCommerceClientTransfer $agenticCommerceClientTransfer
    ): AgenticCommerceClientTransfer {
        $this->oauthFacade->saveClient(
            (new OauthClientTransfer())
                ->setIdentifier($agenticCommerceClientTransfer->getClientIdentifierOrFail())
                ->setName($agenticCommerceClientTransfer->getNameOrFail())
                ->setRedirectUri(array_values($agenticCommerceClientTransfer->getRedirectUris())[0] ?? null)
                ->setIsConfidential(false),
        );

        $agenticCommerceClientTransfer
            ->setRegistrationType(SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED)
            ->setTrustLevel($agenticCommerceClientTransfer->getTrustLevel() ?? SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED)
            ->setStatus(SharedAgenticCommerceConfig::CLIENT_STATUS_ACTIVE);

        return $this->agenticCommerceEntityManager->createAgenticCommerceClient($agenticCommerceClientTransfer);
    }
}
