<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\Token;

use DateInterval;
use DateTimeInterface;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenTransfer;
use Generated\Shared\Transfer\AgenticCommerceAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationRequestTransfer;
use Generated\Shared\Transfer\OauthAccessTokenValidationResponseTransfer;
use Generated\Shared\Transfer\OauthErrorTransfer;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Exception as JwtException;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;

class AgenticCommerceAccessTokenValidator implements AgenticCommerceAccessTokenValidatorInterface
{
    protected const string ERROR_MESSAGE_DELEGATED_ACCESS_TOKEN_REJECTED = 'Delegated access tokens are not accepted by this application.';

    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string TIME_LEEWAY_INTERVAL_FORMAT = 'PT%dS';

    public function __construct(
        protected AgenticCommerceConfig $agenticCommerceConfig,
        protected AgenticCommerceServiceInterface $agenticCommerceService
    ) {
    }

    public function validateAgenticCommerceAccessToken(string $accessToken): AgenticCommerceAccessTokenValidationResponseTransfer
    {
        $agenticCommerceAccessTokenValidationResponseTransfer = (new AgenticCommerceAccessTokenValidationResponseTransfer())
            ->setIsValid(false)
            ->setErrorCode(SharedAgenticCommerceConfig::ERROR_INVALID_TOKEN);
        $token = $this->parseToken($accessToken);

        if ($token === null || !$this->isVerified($token) || !$this->hasDelegatedAccessClaims($token)) {
            return $agenticCommerceAccessTokenValidationResponseTransfer;
        }

        return $agenticCommerceAccessTokenValidationResponseTransfer
            ->setIsValid(true)
            ->setErrorCode(null)
            ->setAgenticCommerceAccessToken($this->mapTokenToAgenticCommerceAccessToken($token, new AgenticCommerceAccessTokenTransfer()));
    }

    public function rejectAgenticCommerceToken(
        OauthAccessTokenValidationRequestTransfer $oauthAccessTokenValidationRequestTransfer,
        OauthAccessTokenValidationResponseTransfer $oauthAccessTokenValidationResponseTransfer
    ): OauthAccessTokenValidationResponseTransfer {
        $token = $this->parseToken((string)$oauthAccessTokenValidationRequestTransfer->getAccessToken());

        if ($token === null || !$this->isDelegatedAccessToken($token)) {
            return $oauthAccessTokenValidationResponseTransfer;
        }

        return $oauthAccessTokenValidationResponseTransfer
            ->setIsValid(false)
            ->setError(
                (new OauthErrorTransfer())
                    ->setErrorType(SharedAgenticCommerceConfig::ERROR_INVALID_TOKEN)
                    ->setMessage(static::ERROR_MESSAGE_DELEGATED_ACCESS_TOKEN_REJECTED),
            );
    }

    protected function parseToken(string $accessToken): ?UnencryptedToken
    {
        if ($accessToken === '') {
            return null;
        }

        try {
            $token = (new Parser(new JoseEncoder()))->parse($accessToken);
        } catch (JwtException) {
            return null;
        }

        return $token instanceof UnencryptedToken ? $token : null;
    }

    protected function isVerified(UnencryptedToken $token): bool
    {
        $issuer = $this->agenticCommerceConfig->getIssuer();
        $resourceUri = $this->agenticCommerceConfig->getResourceUri();

        if ($issuer === '' || $resourceUri === '' || !$this->isSignedWithTheDelegatedAccessKey($token) || !$this->isPermittedForADeclaredProtectedResource($token)) {
            return false;
        }

        return (new Validator())->validate(
            $token,
            new IssuedBy($issuer),
            new StrictValidAt(SystemClock::fromUTC(), new DateInterval(sprintf(static::TIME_LEEWAY_INTERVAL_FORMAT, $this->agenticCommerceConfig->getTokenTimeLeewayInSeconds()))),
        );
    }

    /**
     * Which declared resource the token must name is decided where the called path is known: the delegated access firewall.
     */
    protected function isPermittedForADeclaredProtectedResource(UnencryptedToken $token): bool
    {
        foreach ((array)$token->claims()->get(RegisteredClaims::AUDIENCE, []) as $audience) {
            if (is_string($audience) && $this->agenticCommerceService->findProtectedResourceByResourceUri($audience) !== null) {
                return true;
            }
        }

        return false;
    }

    protected function isSignedWithTheDelegatedAccessKey(UnencryptedToken $token): bool
    {
        $publicKey = $this->agenticCommerceConfig->getOauthPublicKey();

        if ($publicKey === '') {
            return false;
        }

        try {
            return (new Validator())->validate($token, new SignedWith(new Sha256(), InMemory::plainText($publicKey)));
        } catch (JwtException) {
            return false;
        }
    }

    protected function hasDelegatedAccessClaims(UnencryptedToken $token): bool
    {
        $claims = $token->claims();

        return $this->isNonEmptyString($claims->get(RegisteredClaims::SUBJECT))
            && $this->isNonEmptyString($claims->get(RegisteredClaims::ID))
            && $this->isNonEmptyString($claims->get(SharedAgenticCommerceConfig::CLAIM_CLIENT_ID))
            && $this->isNonEmptyString($claims->get(SharedAgenticCommerceConfig::CLAIM_STORE))
            && $this->isNonEmptyString($claims->get(SharedAgenticCommerceConfig::CLAIM_CONSENT_UUID))
            && is_array($claims->get(SharedAgenticCommerceConfig::CLAIM_SCOPES));
    }

    protected function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    /**
     * A token is treated as delegated when it carries the consent claim or names the delegated access resource in its
     * audience. Neither marker can be removed without signing the token again, and the signature is not looked at, so a
     * key pair shared with the storefront by mistake does not turn every storefront token away.
     */
    protected function isDelegatedAccessToken(UnencryptedToken $token): bool
    {
        if ($token->claims()->has(SharedAgenticCommerceConfig::CLAIM_CONSENT_UUID)) {
            return true;
        }

        $resourceUri = $this->agenticCommerceConfig->getResourceUri();

        return $resourceUri !== '' && $token->isPermittedFor($resourceUri);
    }

    protected function mapTokenToAgenticCommerceAccessToken(
        UnencryptedToken $token,
        AgenticCommerceAccessTokenTransfer $agenticCommerceAccessTokenTransfer
    ): AgenticCommerceAccessTokenTransfer {
        $claims = $token->claims();
        $expirationTime = $claims->get(RegisteredClaims::EXPIRATION_TIME);

        return $agenticCommerceAccessTokenTransfer
            ->setIssuer((string)$claims->get(RegisteredClaims::ISSUER))
            ->setAudience(array_values(array_map('strval', (array)$claims->get(RegisteredClaims::AUDIENCE, []))))
            ->setSubject((string)$claims->get(RegisteredClaims::SUBJECT))
            ->setTokenIdentifier((string)$claims->get(RegisteredClaims::ID))
            ->setClientId((string)$claims->get(SharedAgenticCommerceConfig::CLAIM_CLIENT_ID))
            ->setStoreName((string)$claims->get(SharedAgenticCommerceConfig::CLAIM_STORE))
            ->setConsentUuid((string)$claims->get(SharedAgenticCommerceConfig::CLAIM_CONSENT_UUID))
            ->setScopes(array_values(array_map('strval', (array)$claims->get(SharedAgenticCommerceConfig::CLAIM_SCOPES))))
            ->setAuthorizationDetails($this->readAuthorizationDetails($token))
            ->setExpiresAt($expirationTime instanceof DateTimeInterface ? $expirationTime->format(static::DATE_TIME_FORMAT) : null);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function readAuthorizationDetails(UnencryptedToken $token): array
    {
        $authorizationDetails = $token->claims()->get(SharedAgenticCommerceConfig::CLAIM_AUTHORIZATION_DETAILS);

        return is_array($authorizationDetails) ? array_values(array_filter($authorizationDetails, 'is_array')) : [];
    }
}
