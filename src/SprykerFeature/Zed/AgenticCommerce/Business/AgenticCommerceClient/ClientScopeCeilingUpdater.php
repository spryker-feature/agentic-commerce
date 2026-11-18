<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Zed\AgenticCommerce\Business\Scope\SupportedScopeReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ClientScopeCeilingUpdater implements ClientScopeCeilingUpdaterInterface
{
    protected const string ERROR_CLIENT_IDENTIFIER_REQUIRED = 'agentic_commerce.client.validation.client_identifier_required';

    protected const string ERROR_CLIENT_NOT_FOUND = 'agentic_commerce.client.validation.not_found';

    protected const string ERROR_SCOPE_UNSUPPORTED = 'agentic_commerce.client.validation.scope_unsupported';

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager,
        protected SupportedScopeReaderInterface $supportedScopeReader
    ) {
    }

    /**
     * @param list<string> $scopeCeiling
     */
    public function updateAgenticCommerceClientScopeCeiling(
        string $clientIdentifier,
        array $scopeCeiling
    ): AgenticCommerceClientCollectionResponseTransfer {
        if ($clientIdentifier === '') {
            return $this->createErrorResponse(static::ERROR_CLIENT_IDENTIFIER_REQUIRED);
        }

        $agenticCommerceClientTransfer = $this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientIdentifier);

        if ($agenticCommerceClientTransfer === null) {
            return $this->createErrorResponse(static::ERROR_CLIENT_NOT_FOUND);
        }

        if (array_diff($scopeCeiling, $this->supportedScopeReader->getSupportedScopeNames()) !== []) {
            return $this->createErrorResponse(static::ERROR_SCOPE_UNSUPPORTED);
        }

        $normalizedScopeCeiling = array_values(array_unique($scopeCeiling));

        $this->agenticCommerceEntityManager->updateAgenticCommerceClientScopeCeiling($clientIdentifier, $normalizedScopeCeiling);

        return (new AgenticCommerceClientCollectionResponseTransfer())
            ->addAgenticCommerceClient($agenticCommerceClientTransfer->setScopeCeiling($normalizedScopeCeiling));
    }

    protected function createErrorResponse(string $message): AgenticCommerceClientCollectionResponseTransfer
    {
        return (new AgenticCommerceClientCollectionResponseTransfer())
            ->addError((new ErrorTransfer())->setMessage($message));
    }
}
