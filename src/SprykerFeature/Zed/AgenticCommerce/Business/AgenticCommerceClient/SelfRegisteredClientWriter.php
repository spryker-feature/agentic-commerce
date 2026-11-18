<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\OauthClientTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;

class SelfRegisteredClientWriter implements SelfRegisteredClientWriterInterface
{
    use TransactionTrait;

    public function __construct(
        protected OauthFacadeInterface $oauthFacade,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager
    ) {
    }

    public function createClient(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): AgenticCommerceClientTransfer
    {
        return $this->getTransactionHandler()->handleTransaction(function () use ($agenticCommerceClientTransfer): AgenticCommerceClientTransfer {
            $this->oauthFacade->saveClient(
                (new OauthClientTransfer())
                    ->setIdentifier($agenticCommerceClientTransfer->getClientIdentifierOrFail())
                    ->setName($agenticCommerceClientTransfer->getNameOrFail())
                    ->setRedirectUri(array_values($agenticCommerceClientTransfer->getRedirectUris())[0] ?? null)
                    ->setIsConfidential(false),
            );

            return $this->agenticCommerceEntityManager->createAgenticCommerceClient($agenticCommerceClientTransfer);
        });
    }

    public function updateClientMetadata(AgenticCommerceClientTransfer $agenticCommerceClientTransfer): void
    {
        $this->getTransactionHandler()->handleTransaction(function () use ($agenticCommerceClientTransfer): void {
            $this->agenticCommerceEntityManager->updateAgenticCommerceClientMetadata($agenticCommerceClientTransfer);

            $this->oauthFacade->updateClient(
                (new OauthClientTransfer())
                    ->setIdentifier($agenticCommerceClientTransfer->getClientIdentifierOrFail())
                    ->setName($agenticCommerceClientTransfer->getNameOrFail())
                    ->setRedirectUri(array_values($agenticCommerceClientTransfer->getRedirectUris())[0] ?? null),
            );
        });
    }
}
