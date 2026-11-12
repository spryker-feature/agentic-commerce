<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Shared\AgenticCommerce\Helper;

use Codeception\Module;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceClientTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClient;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceClientQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsent;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use Orm\Zed\Oauth\Persistence\SpyOauthClient;
use Orm\Zed\Oauth\Persistence\SpyOauthClientQuery;
use PHPUnit\Framework\AssertionFailedError;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepository;
use SprykerTest\Shared\Customer\Helper\CustomerDataHelperTrait;
use SprykerTest\Shared\Store\Helper\StoreDataHelperTrait;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;

class AgenticCommerceHelper extends Module
{
    use CustomerDataHelperTrait;
    use DataCleanupHelperTrait;
    use StoreDataHelperTrait;

    public const string CLIENT_NAME = 'Procurement Assistant';

    public const string REDIRECT_URI = 'https://client.example/callback';

    public const string SCOPE = 'agentic-commerce:principal-read';

    public const string STORE_NAME = 'DE';

    public const string LOCALE_NAME = 'en_US';

    public const string SEED_REFRESH_TOKEN_IDENTIFIER = 'refreshTokenIdentifier';

    public const string ISSUER = 'http://glue-storefront.eu.spryker.local';

    public const string RESOURCE_URI = self::ISSUER . '/agentic-commerce';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string DEFAULT_CONSENT_LIFETIME = '+30 days';

    /**
     * @param array<string, mixed> $seed
     */
    public function haveAgenticCommerceClient(array $seed = []): AgenticCommerceClientTransfer
    {
        $clientIdentifier = $seed[AgenticCommerceClientTransfer::CLIENT_IDENTIFIER] ?? sprintf('delegated-access-client-%s', uniqid());

        (new SpyOauthClient())
            ->setIdentifier($clientIdentifier)
            ->setName($seed[AgenticCommerceClientTransfer::NAME] ?? static::CLIENT_NAME)
            ->setRedirectUri(static::REDIRECT_URI)
            ->setIsConfidential(false)
            ->save();

        (new SpyAgenticCommerceClient())
            ->setFkOauthClient($clientIdentifier)
            ->setRedirectUris(json_encode($seed[AgenticCommerceClientTransfer::REDIRECT_URIS] ?? [static::REDIRECT_URI], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES))
            ->setScopeCeiling(json_encode($seed[AgenticCommerceClientTransfer::SCOPE_CEILING] ?? [static::SCOPE], JSON_THROW_ON_ERROR))
            ->setRegistrationType(AgenticCommerceConfig::CLIENT_REGISTRATION_TYPE_PRE_REGISTERED)
            ->setTrustLevel(AgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED)
            ->setStatus($seed[AgenticCommerceClientTransfer::STATUS] ?? AgenticCommerceConfig::CLIENT_STATUS_ACTIVE)
            ->setClientUri($seed[AgenticCommerceClientTransfer::CLIENT_URI] ?? null)
            ->setLogoUri($seed[AgenticCommerceClientTransfer::LOGO_URI] ?? null)
            ->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($clientIdentifier): void {
            $this->debug(sprintf('Deleting delegated access client: %s', $clientIdentifier));
            $this->deleteClientWithConsents($clientIdentifier);
        });

        return $this->findClient($clientIdentifier);
    }

    protected function deleteClientWithConsents(string $clientIdentifier): void
    {
        $delegatedAccessClientQuery = SpyAgenticCommerceClientQuery::create()->filterByFkOauthClient($clientIdentifier);
        $idAgenticCommerceClient = $delegatedAccessClientQuery->findOne()?->getIdAgenticCommerceClient();

        if ($idAgenticCommerceClient !== null) {
            SpyAgenticCommerceConsentQuery::create()->filterByFkAgenticCommerceClient($idAgenticCommerceClient)->delete();
        }

        SpyAgenticCommerceClientQuery::create()->filterByFkOauthClient($clientIdentifier)->delete();
        SpyOauthClientQuery::create()->filterByIdentifier($clientIdentifier)->delete();
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveAgenticCommerceConsent(array $seed = []): AgenticCommerceConsentTransfer
    {
        return $this->haveAgenticCommerceConsentForCustomer($this->haveAgenticCommerceCustomer(), $seed);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveAgenticCommerceConsentForCustomer(CustomerTransfer $customerTransfer, array $seed = []): AgenticCommerceConsentTransfer
    {
        $clientIdentifier = $seed[AgenticCommerceConsentTransfer::CLIENT_IDENTIFIER] ?? $this->haveAgenticCommerceClient()->getClientIdentifierOrFail();
        $storeTransfer = $this->getStoreDataHelper()->haveStore([StoreTransfer::NAME => $seed[AgenticCommerceConsentTransfer::STORE_NAME] ?? static::STORE_NAME]);

        $agenticCommerceConsentEntity = (new SpyAgenticCommerceConsent())
            ->setFkAgenticCommerceClient($this->findClient($clientIdentifier)->getIdAgenticCommerceClientOrFail())
            ->setFkCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
            ->setPrincipalType($seed[AgenticCommerceConsentTransfer::PRINCIPAL_TYPE] ?? AgenticCommerceConfig::PRINCIPAL_TYPE_CUSTOMER)
            ->setPrincipalReference($seed[AgenticCommerceConsentTransfer::PRINCIPAL_REFERENCE] ?? $customerTransfer->getCustomerReferenceOrFail())
            ->setFkStore($storeTransfer->getIdStoreOrFail())
            ->setGrantedScopes(json_encode($seed[AgenticCommerceConsentTransfer::GRANTED_SCOPES] ?? [static::SCOPE], JSON_THROW_ON_ERROR))
            ->setStatus($seed[AgenticCommerceConsentTransfer::STATUS] ?? AgenticCommerceConfig::CONSENT_STATUS_ACTIVE)
            ->setExpiresAt($seed[AgenticCommerceConsentTransfer::EXPIRES_AT] ?? (new DateTime(static::DEFAULT_CONSENT_LIFETIME))->format(static::DATE_TIME_FORMAT))
            ->setLastUsedAt($seed[AgenticCommerceConsentTransfer::LAST_USED_AT] ?? null)
            ->setRefreshTokenIdentifier($seed[static::SEED_REFRESH_TOKEN_IDENTIFIER] ?? null);
        $agenticCommerceConsentEntity->save();

        return $this->findConsentByUuid((string)$agenticCommerceConsentEntity->getUuid());
    }

    public function haveAgenticCommerceCustomer(): CustomerTransfer
    {
        return $this->getCustomerDataHelper()->haveCustomer([LocaleTransfer::LOCALE_NAME => static::LOCALE_NAME]);
    }

    public function findClient(string $clientIdentifier): AgenticCommerceClientTransfer
    {
        return (new AgenticCommerceRepository())->findAgenticCommerceClientByClientIdentifier($clientIdentifier)
            ?? throw new AssertionFailedError(sprintf('Delegated-access client "%s" does not exist.', $clientIdentifier));
    }

    public function findConsentByUuid(string $consentUuid): AgenticCommerceConsentTransfer
    {
        return (new AgenticCommerceRepository())->findAgenticCommerceConsentByUuid($consentUuid)
            ?? throw new AssertionFailedError(sprintf('Delegated-access consent "%s" does not exist.', $consentUuid));
    }

    /**
     * @return list<\Generated\Shared\Transfer\AgenticCommerceConsentEventTransfer>
     */
    public function getConsentEvents(string $consentUuid): array
    {
        return (new AgenticCommerceRepository())->getAgenticCommerceConsentEvents(
            $this->findConsentByUuid($consentUuid)->getIdAgenticCommerceConsentOrFail(),
        );
    }

    public function moveCreatedAtBackBySeconds(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, int $seconds): void
    {
        $createdAt = $this->findConsentByUuid($agenticCommerceConsentTransfer->getUuidOrFail())->getCreatedAtOrFail();

        SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->update(['CreatedAt' => $this->shiftTimestampBySeconds($createdAt, -$seconds)]);
    }

    protected function shiftTimestampBySeconds(string $timestamp, int $seconds): string
    {
        return (new DateTime($timestamp))->modify(sprintf('%+d seconds', $seconds))->format(static::DATE_TIME_FORMAT);
    }

    public function findConsentRefreshTokenIdentifier(string $consentUuid): ?string
    {
        return SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($consentUuid)
            ->findOne()
            ?->getRefreshTokenIdentifier();
    }
}
