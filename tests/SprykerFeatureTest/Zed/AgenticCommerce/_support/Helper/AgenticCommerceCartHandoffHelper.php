<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Helper;

use Codeception\Module;
use DateTime;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRedeemRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCartHandoffRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceCartHandoffQuery;
use Orm\Zed\AgenticCommerce\Persistence\SpyAgenticCommerceConsentQuery;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper;
use SprykerTest\Shared\Quote\Helper\PersistentQuoteHelper;

class AgenticCommerceCartHandoffHelper extends Module
{
    protected const string HASH_ALGORITHM = 'sha256';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string PAST = '-1 minute';

    public function havePersistentCartOfCustomer(CustomerTransfer $customerTransfer): QuoteTransfer
    {
        return $this->getPersistentQuoteHelper()->havePersistentQuote([
            QuoteTransfer::CUSTOMER => $customerTransfer->toArray(),
            QuoteTransfer::CUSTOMER_REFERENCE => $customerTransfer->getCustomerReferenceOrFail(),
        ]);
    }

    /**
     * @param array<string, mixed> $seed
     */
    public function haveConsentGrantingCartWrite(CustomerTransfer $customerTransfer, array $seed = []): AgenticCommerceConsentTransfer
    {
        return $this->getAgenticCommerceHelper()->haveAgenticCommerceConsentForCustomer(
            $customerTransfer,
            $seed + [AgenticCommerceConsentTransfer::GRANTED_SCOPES => [AgenticCommerceConfig::SCOPE_CART_WRITE]],
        );
    }

    /**
     * @param list<string> $grantedScopes
     */
    public function narrowConsentScopes(AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer, array $grantedScopes): void
    {
        SpyAgenticCommerceConsentQuery::create()
            ->filterByUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->update(['GrantedScopes' => json_encode($grantedScopes, JSON_THROW_ON_ERROR)]);
    }

    public function createCartHandoffRequest(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        QuoteTransfer $quoteTransfer
    ): AgenticCommerceCartHandoffRequestTransfer {
        return (new AgenticCommerceCartHandoffRequestTransfer())
            ->setConsentUuid($agenticCommerceConsentTransfer->getUuidOrFail())
            ->setQuoteUuid($quoteTransfer->getUuidOrFail());
    }

    public function createCartHandoffRedeemRequest(
        string $token,
        string $customerReference,
        ?string $companyUserUuid = null,
        string $storeName = AgenticCommerceHelper::STORE_NAME
    ): AgenticCommerceCartHandoffRedeemRequestTransfer {
        return (new AgenticCommerceCartHandoffRedeemRequestTransfer())
            ->setToken($token)
            ->setCustomerReference($customerReference)
            ->setCompanyUserUuid($companyUserUuid)
            ->setStoreName($storeName);
    }

    public function expireCartHandoff(string $token): void
    {
        SpyAgenticCommerceCartHandoffQuery::create()
            ->filterByTokenHash(hash(static::HASH_ALGORITHM, $token))
            ->update(['ExpiresAt' => (new DateTime(static::PAST))->format(static::DATE_TIME_FORMAT)]);
    }

    /**
     * @return list<string>
     */
    public function findStoredCartHandoffTokenHashes(string $consentUuid): array
    {
        return SpyAgenticCommerceCartHandoffQuery::create()
            ->useAgenticCommerceConsentQuery()
                ->filterByUuid($consentUuid)
            ->endUse()
            ->select(['TokenHash'])
            ->find()
            ->toArray();
    }

    public function countOpenCartHandoffs(string $consentUuid): int
    {
        return SpyAgenticCommerceCartHandoffQuery::create()
            ->useAgenticCommerceConsentQuery()
                ->filterByUuid($consentUuid)
            ->endUse()
            ->filterByUsedAt(null)
            ->count();
    }

    public function hashCartHandoffToken(string $token): string
    {
        return hash(static::HASH_ALGORITHM, $token);
    }

    protected function getAgenticCommerceHelper(): AgenticCommerceHelper
    {
        /** @var \SprykerFeatureTest\Shared\AgenticCommerce\Helper\AgenticCommerceHelper $agenticCommerceHelper */
        $agenticCommerceHelper = $this->getModule('\\' . AgenticCommerceHelper::class);

        return $agenticCommerceHelper;
    }

    protected function getPersistentQuoteHelper(): PersistentQuoteHelper
    {
        /** @var \SprykerTest\Shared\Quote\Helper\PersistentQuoteHelper $persistentQuoteHelper */
        $persistentQuoteHelper = $this->getModule('\\' . PersistentQuoteHelper::class);

        return $persistentQuoteHelper;
    }
}
