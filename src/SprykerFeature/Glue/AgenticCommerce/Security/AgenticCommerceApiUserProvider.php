<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements \Symfony\Component\Security\Core\User\UserProviderInterface<\SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser>
 */
class AgenticCommerceApiUserProvider implements UserProviderInterface
{
    protected const string ERROR_USER_NOT_LOADABLE = 'Delegated access users exist only for the request that carries their access token.';

    protected const string ERROR_UNSUPPORTED_USER_CLASS = 'Instances of "%s" are not supported.';

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new UserNotFoundException(static::ERROR_USER_NOT_LOADABLE);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AgenticCommerceApiUser) {
            throw new UnsupportedUserException(sprintf(static::ERROR_UNSUPPORTED_USER_CLASS, $user::class));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return is_a($class, AgenticCommerceApiUser::class, true);
    }
}
