<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Spryker\Client\Session\SessionClientInterface;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;

class AuthorizationRequestParker implements AuthorizationRequestParkerInterface
{
    protected const string SESSION_KEY_AUTHORIZATION_REQUESTS = 'agentic_commerce.authorization_requests';

    protected const string SESSION_KEY_PENDING_AUTHORIZATION_REQUEST = 'agentic_commerce.pending_authorization_request';

    protected const string KEY_AUTHORIZATION_RESPONSE = 'authorizationResponse';

    protected const string KEY_EXPIRES_AT = 'expiresAt';

    protected const int AUTHORIZATION_REQUEST_ID_LENGTH_IN_BYTES = 32;

    protected const int MAX_PARKED_AUTHORIZATION_REQUESTS = 5;

    public function __construct(
        protected SessionClientInterface $sessionClient,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    public function park(AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer): string
    {
        /** @var int<1, max> $authorizationRequestIdLengthInBytes */
        $authorizationRequestIdLengthInBytes = static::AUTHORIZATION_REQUEST_ID_LENGTH_IN_BYTES;
        $authorizationRequestId = bin2hex(random_bytes($authorizationRequestIdLengthInBytes));
        $parkedAuthorizationRequests = $this->getUnexpiredAuthorizationRequests();
        $parkedAuthorizationRequests[$authorizationRequestId] = [
            static::KEY_AUTHORIZATION_RESPONSE => $agenticCommerceAuthorizationResponseTransfer->toArray(),
            static::KEY_EXPIRES_AT => time() + $this->agenticCommerceConfig->getAuthorizationRequestLifetimeInSeconds(),
        ];

        $this->sessionClient->set(static::SESSION_KEY_AUTHORIZATION_REQUESTS, $this->keepNewest($parkedAuthorizationRequests));

        return $authorizationRequestId;
    }

    public function findParkedAuthorization(string $authorizationRequestId): ?AgenticCommerceAuthorizationResponseTransfer
    {
        $authorizationResponse = $this->getUnexpiredAuthorizationRequests()[$authorizationRequestId][static::KEY_AUTHORIZATION_RESPONSE] ?? null;

        if (!is_array($authorizationResponse)) {
            return null;
        }

        return (new AgenticCommerceAuthorizationResponseTransfer())->fromArray($authorizationResponse, true);
    }

    public function remove(string $authorizationRequestId): void
    {
        $parkedAuthorizationRequests = $this->getUnexpiredAuthorizationRequests();
        unset($parkedAuthorizationRequests[$authorizationRequestId]);

        $this->sessionClient->set(static::SESSION_KEY_AUTHORIZATION_REQUESTS, $parkedAuthorizationRequests);

        if ($this->sessionClient->get(static::SESSION_KEY_PENDING_AUTHORIZATION_REQUEST) === $authorizationRequestId) {
            $this->sessionClient->remove(static::SESSION_KEY_PENDING_AUTHORIZATION_REQUEST);
        }
    }

    public function markPending(string $authorizationRequestId): void
    {
        $this->sessionClient->set(static::SESSION_KEY_PENDING_AUTHORIZATION_REQUEST, $authorizationRequestId);
    }

    public function findPendingAuthorizationRequestId(): ?string
    {
        $authorizationRequestId = $this->sessionClient->get(static::SESSION_KEY_PENDING_AUTHORIZATION_REQUEST);

        if (!is_string($authorizationRequestId) || !isset($this->getUnexpiredAuthorizationRequests()[$authorizationRequestId])) {
            return null;
        }

        return $authorizationRequestId;
    }

    public function clearPending(): void
    {
        $this->sessionClient->remove(static::SESSION_KEY_PENDING_AUTHORIZATION_REQUEST);
    }

    /**
     * @param array<array<string, mixed>> $parkedAuthorizationRequests
     *
     * @return array<array<string, mixed>>
     */
    protected function keepNewest(array $parkedAuthorizationRequests): array
    {
        uasort(
            $parkedAuthorizationRequests,
            static fn (array $parkedAuthorizationRequest, array $otherParkedAuthorizationRequest): int => $parkedAuthorizationRequest[static::KEY_EXPIRES_AT] <=> $otherParkedAuthorizationRequest[static::KEY_EXPIRES_AT],
        );

        return array_slice($parkedAuthorizationRequests, -static::MAX_PARKED_AUTHORIZATION_REQUESTS, null, true);
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected function getUnexpiredAuthorizationRequests(): array
    {
        $parkedAuthorizationRequests = $this->sessionClient->get(static::SESSION_KEY_AUTHORIZATION_REQUESTS, []);

        if (!is_array($parkedAuthorizationRequests)) {
            return [];
        }

        $now = time();

        return array_filter(
            $parkedAuthorizationRequests,
            static fn (mixed $parkedAuthorizationRequest): bool => is_array($parkedAuthorizationRequest)
                && ($parkedAuthorizationRequest[static::KEY_EXPIRES_AT] ?? 0) > $now,
        );
    }
}
