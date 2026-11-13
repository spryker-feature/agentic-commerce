<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Client\AgenticCommerce\SecurityBlocker;

use Exception;
use Generated\Shared\Transfer\SecurityCheckAuthContextTransfer;
use Generated\Shared\Transfer\SecurityCheckAuthResponseTransfer;
use Predis\PredisException;
use Psr\Log\LoggerInterface;
use RedisException;
use Spryker\Client\SecurityBlocker\Exception\SecurityBlockerException;
use Spryker\Client\SecurityBlocker\SecurityBlockerClientInterface;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceConfig as ClientAgenticCommerceConfig;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;

/**
 * The SecurityBlocker key is built from type, IP and account, so an empty IP gives a bucket per account alone.
 * While `AgenticCommerceConstants::RATE_LIMIT_ENABLED` is off, the checker answers "not blocked" and makes no SecurityBlocker call.
 * A failing SecurityBlocker never blocks a request: the check answers "not blocked" and logs the error once.
 * The failure latches for the process, like the Redis latch: no further SecurityBlocker call and no further log line until
 * `forgetFailure()`, which `AgenticCommerceClient::resetAgenticCommerceFailureLatches()` calls at request start.
 */
class RateLimitChecker implements RateLimitCheckerInterface
{
    /**
     * Only `incrementFailedAttempt()` fills these buckets, so checking them does not count the request.
     */
    protected const array FAILURE_ONLY_TYPES = [
        AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE,
    ];

    protected static bool $hasFailed = false;

    public function __construct(
        protected SecurityBlockerClientInterface $securityBlockerClient,
        protected ClientAgenticCommerceConfig $config,
        protected LoggerInterface $logger,
    ) {
    }

    public function checkRateLimit(string $type, string $ip, ?string $account): SecurityCheckAuthResponseTransfer
    {
        $securityCheckAuthContextTransfer = $this->createContext($type, $ip, $account);

        try {
            if (!$this->config->isRateLimitEnabled() || static::$hasFailed) {
                return $this->createNotBlockedResponse($securityCheckAuthContextTransfer);
            }

            $securityCheckAuthResponseTransfer = $this->securityBlockerClient->isAccountBlocked($securityCheckAuthContextTransfer);

            if ($securityCheckAuthResponseTransfer->getIsBlocked() || in_array($type, static::FAILURE_ONLY_TYPES, true)) {
                return $securityCheckAuthResponseTransfer;
            }

            $this->securityBlockerClient->incrementLoginAttemptCount($securityCheckAuthContextTransfer);
        } catch (SecurityBlockerException | PredisException | RedisException $exception) {
            $this->logError($exception);
        }

        return $this->createNotBlockedResponse($securityCheckAuthContextTransfer);
    }

    public function forgetFailure(): void
    {
        static::$hasFailed = false;
    }

    public function incrementFailedAttempt(string $ip, ?string $clientId): void
    {
        if (!$this->config->isRateLimitEnabled()) {
            return;
        }

        $this->incrementAttempt($this->createContext(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN, $ip, $clientId));
        $this->incrementAttempt($this->createContext(AgenticCommerceConfig::SECURITY_BLOCKER_TYPE_TOKEN_FAILURE, $ip, null));
    }

    protected function createNotBlockedResponse(SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): SecurityCheckAuthResponseTransfer
    {
        return (new SecurityCheckAuthResponseTransfer())
            ->setSecurityCheckAuthContext($securityCheckAuthContextTransfer)
            ->setIsBlocked(false);
    }

    protected function incrementAttempt(SecurityCheckAuthContextTransfer $securityCheckAuthContextTransfer): void
    {
        if (static::$hasFailed) {
            return;
        }

        try {
            $this->securityBlockerClient->incrementLoginAttemptCount($securityCheckAuthContextTransfer);
        } catch (SecurityBlockerException | PredisException | RedisException $exception) { // @phpstan-ignore catch.neverThrown, catch.neverThrown (the Redis adapter throws Predis and phpredis exceptions unwrapped)
            $this->logError($exception);
        }
    }

    protected function createContext(string $type, string $ip, ?string $account): SecurityCheckAuthContextTransfer
    {
        return (new SecurityCheckAuthContextTransfer())
            ->setType($type)
            ->setIp($ip)
            ->setAccount($account);
    }

    protected function logError(Exception $exception): void
    {
        static::$hasFailed = true;
        $this->logger->error('Delegated access rate limit unavailable, the request is not limited.', ['exception' => $exception]);
    }
}
