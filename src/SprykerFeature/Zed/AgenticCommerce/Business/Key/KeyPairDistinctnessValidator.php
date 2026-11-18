<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Key;

use OpenSSLAsymmetricKey;
use SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException;
use SprykerFeature\Zed\AgenticCommerce\AgenticCommerceConfig;

/**
 * A delegated access token the storefront could verify would be a storefront token, so the two key pairs must differ.
 * Both public keys are parsed and compared in one canonical encoding, so the same key in another PEM (Privacy-Enhanced Mail) form still matches.
 * A configured storefront public key that cannot be read or parsed fails closed.
 */
class KeyPairDistinctnessValidator implements KeyPairDistinctnessValidatorInterface
{
    protected const string FILE_PATH_PREFIX = 'file://';

    protected const string ENCODED_KEY_BEGIN_MARKER = '-----BEGIN';

    protected const string KEY_DETAILS_PUBLIC_KEY = 'key';

    public function __construct(protected AgenticCommerceConfig $agenticCommerceConfig)
    {
    }

    public function assertKeyPairsDiffer(): void
    {
        $privateKey = $this->agenticCommerceConfig->getPrivateKey();
        $storefrontPublicKey = $this->agenticCommerceConfig->getStorefrontPublicKey();

        if ($storefrontPublicKey === '') {
            return;
        }

        $delegatedAccessPublicKey = $this->canonicalisePublicKey(openssl_pkey_get_private($privateKey))
            ?? throw new AgenticCommerceConfigurationException('The delegated access private key is not a readable PEM (Privacy-Enhanced Mail) encoded private key.');
        $storefrontCanonicalPublicKey = $this->canonicalisePublicKey(openssl_pkey_get_public($this->readStorefrontPublicKey($storefrontPublicKey)))
            ?? throw new AgenticCommerceConfigurationException('The storefront OAuth public key is configured but cannot be parsed.');

        if ($delegatedAccessPublicKey === $storefrontCanonicalPublicKey) {
            throw new AgenticCommerceConfigurationException(
                'The delegated access key pair equals the storefront OAuth key pair; configure a key pair of its own.',
            );
        }
    }

    /**
     * @throws \SprykerFeature\Shared\AgenticCommerce\Exception\AgenticCommerceConfigurationException
     */
    protected function readStorefrontPublicKey(string $storefrontPublicKey): string
    {
        if (str_starts_with(ltrim($storefrontPublicKey), static::ENCODED_KEY_BEGIN_MARKER)) {
            return $storefrontPublicKey;
        }

        $storefrontPublicKeyPath = str_starts_with($storefrontPublicKey, static::FILE_PATH_PREFIX)
            ? substr($storefrontPublicKey, strlen(static::FILE_PATH_PREFIX))
            : $storefrontPublicKey;
        $storefrontPublicKeyContents = is_file($storefrontPublicKeyPath) && is_readable($storefrontPublicKeyPath)
            ? file_get_contents($storefrontPublicKeyPath)
            : false;

        if ($storefrontPublicKeyContents === false || trim($storefrontPublicKeyContents) === '') {
            throw new AgenticCommerceConfigurationException(sprintf(
                'The storefront OAuth public key file "%s" is configured but missing, unreadable or empty.',
                $storefrontPublicKeyPath,
            ));
        }

        return $storefrontPublicKeyContents;
    }

    /**
     * The public key re-encoded by OpenSSL, the same bytes whichever form the key was given in.
     */
    protected function canonicalisePublicKey(OpenSSLAsymmetricKey|false $key): ?string
    {
        if ($key === false) {
            return null;
        }

        $keyDetails = openssl_pkey_get_details($key);

        return $keyDetails !== false && is_string($keyDetails[static::KEY_DETAILS_PUBLIC_KEY] ?? null)
            ? $keyDetails[static::KEY_DETAILS_PUBLIC_KEY]
            : null;
    }
}
