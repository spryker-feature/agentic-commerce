<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
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
use Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentUsageResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesResponseTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceScopeCriteriaTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceBusinessFactory getFactory()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface getEntityManager()
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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
    public function updateAgenticCommerceClientStatusByCriteria(
        AgenticCommerceClientCriteriaTransfer $agenticCommerceClientCriteriaTransfer,
        string $status
    ): AgenticCommerceClientCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceClientStatusUpdater()
            ->updateAgenticCommerceClientStatusByCriteria($agenticCommerceClientCriteriaTransfer, $status);
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
    public function validateAgenticCommerceAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationResponseTransfer {
        return $this->getFactory()
            ->createAuthorizationRequestValidator()
            ->validateAgenticCommerceAuthorizationRequest($agenticCommerceAuthorizationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommercePrincipalCandidates(CustomerTransfer $customerTransfer): AgenticCommercePrincipalCollectionTransfer
    {
        return $this->getFactory()
            ->createPrincipalResolver()
            ->getCandidates($customerTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceGrantableScopes(
        AgenticCommerceGrantableScopesRequestTransfer $agenticCommerceGrantableScopesRequestTransfer
    ): AgenticCommerceGrantableScopesResponseTransfer {
        return $this->getFactory()
            ->createScopeNarrower()
            ->getGrantableScopes($agenticCommerceGrantableScopesRequestTransfer);
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
    public function getAgenticCommerceAuthorizationDetailsTypeCollection(
        AgenticCommerceAuthorizationDetailsTypeCriteriaTransfer $agenticCommerceAuthorizationDetailsTypeCriteriaTransfer
    ): AgenticCommerceAuthorizationDetailsTypeCollectionTransfer {
        return $this->getFactory()
            ->createAuthorizationDetailsTypeReader()
            ->getAuthorizationDetailsTypeCollection();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createConsentCreator()
            ->createAgenticCommerceConsentCollection($agenticCommerceConsentCollectionRequestTransfer);
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
    public function countAgenticCommerceConsents(AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer): int
    {
        return $this->getRepository()->countAgenticCommerceConsents($agenticCommerceConsentCriteriaTransfer);
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
     */
    public function validateAgenticCommerceConsentUsage(
        AgenticCommerceConsentUsageRequestTransfer $agenticCommerceConsentUsageRequestTransfer
    ): AgenticCommerceConsentUsageResponseTransfer {
        return $this->getFactory()
            ->createConsentUsageValidator()
            ->validateAgenticCommerceConsentUsage($agenticCommerceConsentUsageRequestTransfer);
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
     *
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function getAgenticCommerceConsentConstraints(string $consentUuid): array
    {
        return $this->getFactory()
            ->createConsentConstraintReader()
            ->getConsentConstraints($consentUuid);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceConsentConstraintFields(
        AgenticCommerceConstraintFieldRequestTransfer $agenticCommerceConstraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldCollectionTransfer {
        return $this->getFactory()
            ->createConsentConstraintReader()
            ->getConsentConstraintFields($agenticCommerceConstraintFieldRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findLatestAgenticCommerceConsentTermsEvent(string $consentUuid): ?AgenticCommerceConsentEventTransfer
    {
        return $this->getFactory()
            ->createConsentEventReader()
            ->findLatestConsentTermsEvent($consentUuid);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findAgenticCommerceConsentEventById(int $idAgenticCommerceConsentEvent): ?AgenticCommerceConsentEventTransfer
    {
        return $this->getFactory()
            ->createConsentEventReader()
            ->findConsentEventById($idAgenticCommerceConsentEvent);
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function invalidateAgenticCommerceConsentCacheByCustomerReference(string $customerReference): void
    {
        $this->getFactory()->createConsentCacheInvalidator()->invalidateByCustomerReference($customerReference);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param list<int> $consentEventIds
     *
     * @return list<int>
     */
    public function filterAgenticCommerceOrderReferencedConsentEventIds(array $consentEventIds): array
    {
        return $this->getRepository()->getReferencedConsentEventIds($consentEventIds);
    }
}
