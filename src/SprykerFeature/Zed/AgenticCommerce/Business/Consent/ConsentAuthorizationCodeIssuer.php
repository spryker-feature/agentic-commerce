<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\OauthAuthorizationCodeCollectionRequestTransfer;
use Generated\Shared\Transfer\OauthAuthorizationCodeTransfer;
use Spryker\Zed\OauthCodeFlow\Business\OauthCodeFlowFacadeInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Business\Exception\AuthorizationCodeNotIssuedException;

class ConsentAuthorizationCodeIssuer implements ConsentAuthorizationCodeIssuerInterface
{
    protected const int ISSUED_AUTHORIZATION_CODE_COUNT = 1;

    public function __construct(
        protected OauthCodeFlowFacadeInterface $oauthCodeFlowFacade,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
     * @throws \SprykerFeature\Zed\AgenticCommerce\Business\Exception\AuthorizationCodeNotIssuedException
     */
    public function issueAuthorizationCode(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        string $entityIdentifier
    ): string {
        $oauthAuthorizationCodeCollectionResponseTransfer = $this->oauthCodeFlowFacade->createOauthAuthorizationCodeCollection(
            (new OauthAuthorizationCodeCollectionRequestTransfer())
                ->setIsTransactional(true)
                ->addOauthAuthorizationCode($this->buildOauthAuthorizationCode($agenticCommerceConsentTransfer, $agenticCommerceAuthorizationResponseTransfer)),
        );

        if ($oauthAuthorizationCodeCollectionResponseTransfer->getOauthAuthorizationCodes()->count() !== static::ISSUED_AUTHORIZATION_CODE_COUNT) {
            throw new AuthorizationCodeNotIssuedException($entityIdentifier);
        }

        return $oauthAuthorizationCodeCollectionResponseTransfer->getOauthAuthorizationCodes()->offsetGet(0)->getCodeOrFail();
    }

    protected function buildOauthAuthorizationCode(
        AgenticCommerceConsentTransfer $agenticCommerceConsentTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): OauthAuthorizationCodeTransfer {
        $agenticCommerceAuthorizationRequestTransfer = $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail();

        return (new OauthAuthorizationCodeTransfer())
            ->setClientId($agenticCommerceConsentTransfer->getClientIdentifierOrFail())
            ->setRedirectUri($agenticCommerceAuthorizationRequestTransfer->getRedirectUriOrFail())
            ->setUserIdentifier($agenticCommerceConsentTransfer->getUserIdentifierOrFail())
            ->setScopes(array_values(array_merge(
                $this->agenticCommerceConfig->getIdentityScopes($agenticCommerceConsentTransfer->getPrincipalTypeOrFail()),
                $agenticCommerceConsentTransfer->getGrantedScopes(),
            )))
            ->setCodeChallenge($agenticCommerceAuthorizationRequestTransfer->getCodeChallengeOrFail())
            ->setCodeChallengeMethod($agenticCommerceAuthorizationRequestTransfer->getCodeChallengeMethodOrFail())
            ->setRequestApplication(SharedAgenticCommerceConfig::REQUEST_APPLICATION_AGENTIC_COMMERCE)
            ->setPayloadExtension([
                SharedAgenticCommerceConfig::AUTHORIZATION_CODE_PAYLOAD_KEY_RESOURCE => $agenticCommerceAuthorizationRequestTransfer->getResourceOrFail(),
                SharedAgenticCommerceConfig::CLAIM_CONSENT_UUID => $agenticCommerceConsentTransfer->getUuidOrFail(),
            ]);
    }
}
