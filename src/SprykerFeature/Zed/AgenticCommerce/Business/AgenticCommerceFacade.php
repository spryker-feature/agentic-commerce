<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataDocumentTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceBusinessFactory getFactory()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface getEntityManager()
 */
class AgenticCommerceFacade extends AbstractFacade implements AgenticCommerceFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceClientCreator()
            ->createAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceClientCollection(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer
    ): AgenticCommerceClientCollectionTransfer {
        return $this->getRepository()->getAgenticCommerceClientCollection($agenticCommerceClientCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceClientStatusUpdater()
            ->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateAgenticCommerceClientScopeCeiling(
        string $clientIdentifier,
        array $scopeCeiling
    ): AgenticCommerceClientCollectionResponseTransfer {
        return $this->getFactory()
            ->createClientScopeCeilingUpdater()
            ->updateAgenticCommerceClientScopeCeiling($clientIdentifier, $scopeCeiling);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function revokeAgenticCommerceConsentsBySystem(
        AgenticCommerceConsentRevocationRequestTransfer $agenticCommerceConsentRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createConsentRevoker()
            ->revokeAgenticCommerceConsentsBySystem($agenticCommerceConsentRevocationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceScopeCollection(
        AgenticCommerceScopeCriteriaTransfer $agenticCommerceScopeCriteriaTransfer
    ): AgenticCommerceScopeCollectionTransfer {
        return $this->getFactory()
            ->createSupportedScopeReader()
            ->getScopeCollection($agenticCommerceScopeCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        return $this->getFactory()
            ->createConsentReader()
            ->getAgenticCommerceConsentCollection($agenticCommerceConsentCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createConsentRevoker()
            ->updateAgenticCommerceConsentCollection($agenticCommerceConsentCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceScopeTransfer>
     */
    public function getAgenticCommerceSupportedScopes(): array
    {
        return $this->getFactory()->createSupportedScopeReader()->getSupportedScopes();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function fetchAgenticCommerceClientMetadataDocument(string $documentUrl): AgenticCommerceClientMetadataDocumentTransfer
    {
        return $this->getFactory()->createClientMetadataDocumentFetcher()->fetch($documentUrl);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerAgenticCommerceClient(
        AgenticCommerceClientRegistrationRequestTransfer $agenticCommerceClientRegistrationRequestTransfer
    ): AgenticCommerceClientRegistrationResponseTransfer {
        return $this->getFactory()->createDynamicClientRegistrar()->registerClient($agenticCommerceClientRegistrationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findAgenticCommerceRequestConsentUuid(): ?string
    {
        return $this->getFactory()
            ->createRequestConsentUuidReader()
            ->findRequestConsentUuid();
    }
}
