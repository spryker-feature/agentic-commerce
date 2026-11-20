<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use ArrayObject;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceRequestClientDataTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Authorization\AuthorizationRequestValidatorInterface;
use SprykerFeature\Zed\AgenticCommerce\Business\Exception\AuthorizationCodeNotIssuedException;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ConsentCreator implements ConsentCreatorInterface
{
    use TransactionTrait;

    protected const string ERROR_AUTHORIZATION_REQUEST_INVALID = 'agentic_commerce.consent.validation.authorization_request_invalid';

    protected const string ERROR_AUTHORIZATION_CODE_NOT_ISSUED = 'agentic_commerce.consent.authorization_code_not_issued';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string EXPIRY_MODIFIER_PATTERN = '+%d days';

    public function __construct(
        protected AuthorizationRequestValidatorInterface $authorizationRequestValidator,
        protected ConsentResolverInterface $consentResolver,
        protected ConsentAuthorizationCodeIssuerInterface $consentAuthorizationCodeIssuer,
        protected ConsentRevokerInterface $consentRevoker,
        protected ConsentEventWriterInterface $consentEventWriter,
        protected ConsentConstraintValidatorInterface $consentConstraintValidator,
        protected ConsentConstraintWriterInterface $consentConstraintWriter,
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected AgenticCommerceEntityManagerInterface $agenticCommerceEntityManager
    ) {
    }

    public function createAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        $agenticCommerceConsentCollectionResponseTransfer = new AgenticCommerceConsentCollectionResponseTransfer();

        // The consent form round-trips the request through the browser, so it is validated again before anything is issued.
        $agenticCommerceAuthorizationResponseTransfer = $this->authorizationRequestValidator->validateAgenticCommerceAuthorizationRequest(
            $agenticCommerceConsentCollectionRequestTransfer->getAuthorizationRequestOrFail(),
        );

        if (!$agenticCommerceAuthorizationResponseTransfer->getIsValid()) {
            return $agenticCommerceConsentCollectionResponseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_AUTHORIZATION_REQUEST_INVALID),
            );
        }

        $validAgenticCommerceConsentTransfers = $this->resolveValidConsents(
            $agenticCommerceConsentCollectionRequestTransfer,
            $agenticCommerceAuthorizationResponseTransfer,
            $agenticCommerceConsentCollectionResponseTransfer,
        );

        // An unset flag counts as transactional, so a caller that forgets it never gets a partial create.
        if ($agenticCommerceConsentCollectionRequestTransfer->getIsTransactional() !== false && $agenticCommerceConsentCollectionResponseTransfer->getErrors()->count() > 0) {
            return $agenticCommerceConsentCollectionResponseTransfer;
        }

        // Outside the transaction: a failed insert of a lock row would abort an open PostgreSQL transaction.
        $customerIds = $this->getCustomerIds($validAgenticCommerceConsentTransfers);

        foreach ($customerIds as $idCustomer) {
            $this->agenticCommerceEntityManager->createAgenticCommerceConsentLock($idCustomer);
        }

        try {
            return $this->getTransactionHandler()->handleTransaction(function () use (
                $customerIds,
                $validAgenticCommerceConsentTransfers,
                $agenticCommerceAuthorizationResponseTransfer,
                $agenticCommerceConsentCollectionResponseTransfer,
                $agenticCommerceConsentCollectionRequestTransfer,
            ): AgenticCommerceConsentCollectionResponseTransfer {
                return $this->executeCreateAgenticCommerceConsentCollectionTransaction(
                    $customerIds,
                    $validAgenticCommerceConsentTransfers,
                    $agenticCommerceAuthorizationResponseTransfer,
                    $agenticCommerceConsentCollectionResponseTransfer,
                    $agenticCommerceConsentCollectionRequestTransfer->getRequestClientData(),
                );
            });
        } catch (AuthorizationCodeNotIssuedException $authorizationCodeNotIssuedException) {
            // The transaction is rolled back, so no consent of the response was persisted; the validation errors stay.
            return $agenticCommerceConsentCollectionResponseTransfer
                ->setAgenticCommerceConsents(new ArrayObject())
                ->addError($this->createError(static::ERROR_AUTHORIZATION_CODE_NOT_ISSUED, $authorizationCodeNotIssuedException->getEntityIdentifier()));
        }
    }

    /**
     * Adds the errors of every consent that is not valid to the response.
     *
     * @return array<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer>
     */
    protected function resolveValidConsents(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer
    ): array {
        $validAgenticCommerceConsentTransfers = [];

        foreach ($agenticCommerceConsentCollectionRequestTransfer->getAgenticCommerceConsents() as $index => $agenticCommerceConsentTransfer) {
            $errorMessages = $this->consentResolver->resolveConsent($agenticCommerceConsentTransfer, $agenticCommerceAuthorizationResponseTransfer);

            foreach ($errorMessages as $errorMessage) {
                $agenticCommerceConsentCollectionResponseTransfer->addError($this->createError($errorMessage, (string)$index));
            }

            if ($errorMessages !== []) {
                continue;
            }

            // Runs only on a resolved consent: the constraint plugins decide on the server-built principal and the final scopes.
            $constraintErrorTransfers = $this->consentConstraintValidator->validateConsentConstraints(
                $agenticCommerceConsentTransfer,
                $agenticCommerceConsentCollectionRequestTransfer->getAuthorizationRequestOrFail()->getAuthorizationDetails(),
            );

            foreach ($constraintErrorTransfers as $constraintErrorTransfer) {
                $agenticCommerceConsentCollectionResponseTransfer->addError($constraintErrorTransfer->setEntityIdentifier($constraintErrorTransfer->getEntityIdentifier() ?? (string)$index));
            }

            if ($constraintErrorTransfers === []) {
                $validAgenticCommerceConsentTransfers[$index] = $agenticCommerceConsentTransfer;
            }
        }

        return $validAgenticCommerceConsentTransfers;
    }

    /**
     * Locks the consent lock row of each customer, creates each consent, issues its code, and only then revokes every consent it replaces: any failure
     * rolls the whole transaction back, so the customer keeps the old consent and never ends up with none or with two.
     * Lock order: the consent lock rows of the customers first, then the consents each new consent replaces, before any plain read of the transaction.
     *
     * @param list<int> $customerIds
     * @param array<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     */
    protected function executeCreateAgenticCommerceConsentCollectionTransaction(
        array $customerIds,
        array $agenticCommerceConsentTransfers,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer,
        ?AgenticCommerceRequestClientDataTransfer $requestClientDataTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        // The first statements of the transaction. They serialize concurrent consents of the same customer, so the active-consent lookup below cannot miss one that is being created.
        foreach ($customerIds as $idCustomer) {
            $this->agenticCommerceRepository->lockAgenticCommerceConsentLockByIdCustomer($idCustomer);
        }

        $agenticCommerceClientTransfer = $agenticCommerceAuthorizationResponseTransfer->getAgenticCommerceClientOrFail();
        $replacedConsentUuidsByIndex = [];

        // Consents of the customer for another store or another principal stay active.
        foreach ($agenticCommerceConsentTransfers as $index => $agenticCommerceConsentTransfer) {
            $replacedConsentUuidsByIndex[$index] = $this->agenticCommerceRepository->lockActiveAgenticCommerceConsentUuidsByClientPrincipalAndStore(
                $agenticCommerceClientTransfer->getClientIdentifierOrFail(),
                $agenticCommerceConsentTransfer->getPrincipalTypeOrFail(),
                $agenticCommerceConsentTransfer->getPrincipalReferenceOrFail(),
                $agenticCommerceConsentTransfer->getIdStoreOrFail(),
            );
        }

        foreach ($agenticCommerceConsentTransfers as $index => $agenticCommerceConsentTransfer) {
            $replacedConsentUuids = $replacedConsentUuidsByIndex[$index];

            $agenticCommerceConsentTransfer = $this->agenticCommerceEntityManager->createAgenticCommerceConsent(
                $agenticCommerceConsentTransfer
                    ->setAgenticCommerceClient($agenticCommerceClientTransfer)
                    ->setClientIdentifier($agenticCommerceClientTransfer->getClientIdentifierOrFail())
                    ->setStatus(SharedAgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
                    ->setExpiresAt($this->calculateExpiresAt($agenticCommerceConsentTransfer->getDurationInDaysOrFail())),
            );

            $constraintTransfers = array_values($agenticCommerceConsentTransfer->getConstraints()->getArrayCopy());
            $this->consentConstraintWriter->replaceConsentConstraints($agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail(), $constraintTransfers);

            $this->consentEventWriter->write(
                $agenticCommerceConsentTransfer->getIdAgenticCommerceConsentOrFail(),
                $replacedConsentUuids === [] ? SharedAgenticCommerceConfig::CONSENT_EVENT_GRANTED : SharedAgenticCommerceConfig::CONSENT_EVENT_RE_CONSENTED,
                SharedAgenticCommerceConfig::CONSENT_ACTOR_CUSTOMER,
                array_values($agenticCommerceConsentTransfer->getGrantedScopes()),
                $agenticCommerceClientTransfer->getName(),
                constraintTransfers: $constraintTransfers,
                requestClientDataTransfer: $requestClientDataTransfer,
            );

            $agenticCommerceConsentTransfer->setAuthorizationCode(
                $this->consentAuthorizationCodeIssuer->issueAuthorizationCode($agenticCommerceConsentTransfer, $agenticCommerceAuthorizationResponseTransfer, (string)$index),
            );

            if ($replacedConsentUuids !== []) {
                $this->consentRevoker->revokeAgenticCommerceConsentsBySystem(
                    (new AgenticCommerceConsentRevocationRequestTransfer())
                        ->setConsentUuids($replacedConsentUuids)
                        ->setRevokedBy(SharedAgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER),
                );
            }

            $agenticCommerceConsentCollectionResponseTransfer->addAgenticCommerceConsent($agenticCommerceConsentTransfer);
        }

        return $agenticCommerceConsentCollectionResponseTransfer;
    }

    /**
     * In ascending order, so two requests for the same customers take their locks in the same order.
     *
     * @param array<int, \Generated\Shared\Transfer\AgenticCommerceConsentTransfer> $agenticCommerceConsentTransfers
     *
     * @return list<int>
     */
    protected function getCustomerIds(array $agenticCommerceConsentTransfers): array
    {
        $customerIds = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $customerIds[$agenticCommerceConsentTransfer->getIdCustomerOrFail()] = $agenticCommerceConsentTransfer->getIdCustomerOrFail();
        }

        sort($customerIds);

        return $customerIds;
    }

    protected function calculateExpiresAt(int $durationInDays): string
    {
        return (new DateTime(sprintf(static::EXPIRY_MODIFIER_PATTERN, $durationInDays)))->format(static::DATE_TIME_FORMAT);
    }

    protected function createError(string $message, string $entityIdentifier): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage($message)
            ->setEntityIdentifier($entityIdentifier);
    }
}
