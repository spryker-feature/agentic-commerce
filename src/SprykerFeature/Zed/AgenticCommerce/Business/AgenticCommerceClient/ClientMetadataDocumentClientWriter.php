<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use DateInterval;
use DateTimeImmutable;
use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\OauthClientTransfer;
use PDOException;
use Propel\Runtime\Exception\PropelException;
use Spryker\Shared\Kernel\Transfer\Exception\NullValueException;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\Oauth\Business\OauthFacadeInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface;

class ClientMetadataDocumentClientWriter implements ClientMetadataDocumentClientWriterInterface
{
    use LoggerTrait;

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string INTERVAL_SECONDS_FORMAT = 'PT%dS';

    /**
     * The MySQL/MariaDB driver code of a duplicate entry (`ER_DUP_ENTRY`), reported in `errorInfo[1]` under the general SQLSTATE `23000`.
     */
    protected const int MYSQL_ERROR_CODE_DUPLICATE_ENTRY = 1062;

    /**
     * The PostgreSQL SQLSTATE of a unique violation, reported in `errorInfo[0]`.
     */
    protected const string POSTGRESQL_SQLSTATE_UNIQUE_VIOLATION = '23505';

    public function __construct(
        protected AgenticCommerceRepositoryInterface $agenticCommerceRepository,
        protected OauthFacadeInterface $oauthFacade,
        protected ClientScopeCeilingResolverInterface $clientScopeCeilingResolver,
        protected SelfRegisteredClientWriterInterface $selfRegisteredClientWriter
    ) {
    }

    public function writeClient(
        AgenticCommerceClientMetadataTransfer $clientMetadataTransfer,
        ?AgenticCommerceClientTransfer $storedClientTransfer,
        int $cacheLifetimeSeconds
    ): ?AgenticCommerceClientTransfer {
        $fetchedAt = new DateTimeImmutable();
        $clientTransfer = (new AgenticCommerceClientTransfer())
            ->setClientIdentifier($clientMetadataTransfer->getClientIdOrFail())
            ->setRedirectUris(array_values($clientMetadataTransfer->getRedirectUris()))
            ->setClientUri($clientMetadataTransfer->getClientUri())
            ->setLogoUri($clientMetadataTransfer->getLogoUri())
            ->setMetadataFetchedAt($fetchedAt->format(static::DATE_TIME_FORMAT))
            ->setMetadataExpiresAt($fetchedAt->add(new DateInterval(sprintf(static::INTERVAL_SECONDS_FORMAT, $cacheLifetimeSeconds)))->format(static::DATE_TIME_FORMAT));

        if ($storedClientTransfer !== null) {
            $this->selfRegisteredClientWriter->updateClientMetadata($clientTransfer->setName($clientMetadataTransfer->getClientNameOrFail()));

            return $this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientTransfer->getClientIdentifierOrFail());
        }

        if ($this->isTakenByAnotherOauthClient($clientTransfer->getClientIdentifierOrFail())) {
            $this->getLogger()->warning('Client metadata document names a client identifier that an OAuth client outside Agentic Commerce holds.', ['host' => parse_url($clientTransfer->getClientIdentifierOrFail(), PHP_URL_HOST)]);

            return null;
        }

        // The writer rolls its transaction back before it rethrows, so the re-read below never runs on an aborted transaction.
        try {
            return $this->createClient($clientTransfer, $clientMetadataTransfer);
        } catch (PropelException $exception) {
            if (!$this->isDuplicateKey($exception)) {
                throw $exception;
            }

            return $this->findClientCreatedConcurrently($clientTransfer->getClientIdentifierOrFail(), $exception);
        } catch (NullValueException $exception) {
            return $this->findClientCreatedConcurrently($clientTransfer->getClientIdentifierOrFail(), $exception);
        }
    }

    protected function isDuplicateKey(PropelException $exception): bool
    {
        $previousException = $exception->getPrevious();

        return $previousException instanceof PDOException
            && (($previousException->errorInfo[1] ?? null) === static::MYSQL_ERROR_CODE_DUPLICATE_ENTRY
                || ($previousException->errorInfo[0] ?? null) === static::POSTGRESQL_SQLSTATE_UNIQUE_VIOLATION);
    }

    /**
     * Two first authorizations of the same URL race: the second insert violates the unique client identifier (`PropelException`),
     * or the OAuth client writer finds the first one's row and fails updating a public client without secret (`NullValueException`).
     * The client the first request created is used, if it is exactly this URL's metadata document client.
     */
    protected function findClientCreatedConcurrently(string $clientIdentifier, PropelException|NullValueException $exception): ?AgenticCommerceClientTransfer
    {
        $this->getLogger()->notice('Client from a metadata document was created concurrently; the stored client is used.', ['host' => parse_url($clientIdentifier, PHP_URL_HOST), 'exception' => $exception::class]);

        $storedClientTransfer = $this->agenticCommerceRepository->findAgenticCommerceClientByClientIdentifier($clientIdentifier);

        if (
            $storedClientTransfer === null
            || $storedClientTransfer->getClientIdentifier() !== $clientIdentifier
            || $storedClientTransfer->getRegistrationType() !== SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT
        ) {
            return null;
        }

        return $storedClientTransfer;
    }

    protected function createClient(
        AgenticCommerceClientTransfer $clientTransfer,
        AgenticCommerceClientMetadataTransfer $clientMetadataTransfer
    ): AgenticCommerceClientTransfer {
        return $this->selfRegisteredClientWriter->createClient(
            $clientTransfer
                ->setName($clientMetadataTransfer->getClientNameOrFail())
                ->setRegistrationType(SharedAgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_CLIENT_ID_METADATA_DOCUMENT)
                ->setTrustLevel(SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED)
                ->setStatus(SharedAgenticCommerceConfig::CLIENT_STATUS_ACTIVE)
                ->setScopeCeiling($this->clientScopeCeilingResolver->resolveClientIdMetadataDocumentScopeCeiling($clientTransfer->getClientIdentifierOrFail())),
        );
    }

    protected function isTakenByAnotherOauthClient(string $clientIdentifier): bool
    {
        return $this->oauthFacade->findClientByIdentifier((new OauthClientTransfer())->setIdentifier($clientIdentifier)) !== null;
    }
}
