<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\CartHandoff;

use Spryker\Client\Session\SessionClientInterface;

class CartHandoffTokenParker implements CartHandoffTokenParkerInterface
{
    protected const string SESSION_KEY_PENDING_CART_HANDOFF_TOKEN = 'agentic_commerce.pending_cart_handoff_token';

    public function __construct(protected SessionClientInterface $sessionClient)
    {
    }

    public function park(string $token): void
    {
        $this->sessionClient->set(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN, $token);
    }

    public function findParked(): ?string
    {
        $token = $this->sessionClient->get(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function clear(): void
    {
        $this->sessionClient->remove(static::SESSION_KEY_PENDING_CART_HANDOFF_TOKEN);
    }
}
