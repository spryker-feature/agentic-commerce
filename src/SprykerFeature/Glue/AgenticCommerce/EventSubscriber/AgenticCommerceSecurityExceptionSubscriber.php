<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\EventSubscriber;

use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Exception\AgenticCommerceContextMissingException;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAccessDeniedHandler;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationEntryPoint;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AgenticCommerceSecurityExceptionSubscriber implements EventSubscriberInterface
{
    protected const int PRIORITY = 512;

    protected const string ERROR_DESCRIPTION_INVALID_TOKEN = 'The access token is invalid, expired or revoked.';

    public function __construct(
        protected Security $security,
        protected AgenticCommerceAuthenticationEntryPoint $agenticCommerceAuthenticationEntryPoint,
        protected AgenticCommerceAccessDeniedHandler $agenticCommerceAccessDeniedHandler,
        protected AgenticCommerceAuthenticationChallengeBuilder $agenticCommerceAuthenticationChallengeBuilder,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', static::PRIORITY]];
    }

    public function onKernelException(ExceptionEvent $exceptionEvent): void
    {
        $throwable = $exceptionEvent->getThrowable();

        if (
            !$throwable instanceof AuthenticationException
            && !$throwable instanceof AccessDeniedException
            && !$throwable instanceof AgenticCommerceContextMissingException
        ) {
            return;
        }

        $request = $exceptionEvent->getRequest();

        if ($this->security->getFirewallConfig($request)?->getName() !== $this->agenticCommerceConfig->getFirewallName()) {
            return;
        }

        if ($throwable instanceof AgenticCommerceContextMissingException) {
            $exceptionEvent->setResponse($this->createInvalidTokenResponse($request));

            return;
        }

        if ($throwable instanceof AuthenticationException || $this->security->getToken() === null) {
            $exceptionEvent->setResponse($this->agenticCommerceAuthenticationEntryPoint->start($request));

            return;
        }

        $exceptionEvent->setResponse($this->agenticCommerceAccessDeniedHandler->handle($request, $throwable));
    }

    protected function createInvalidTokenResponse(Request $request): Response
    {
        return new JsonResponse(
            [
                AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR => AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN,
                AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR_DESCRIPTION => static::ERROR_DESCRIPTION_INVALID_TOKEN,
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                AgenticCommerceAuthenticationChallengeBuilder::HEADER_WWW_AUTHENTICATE => $this->agenticCommerceAuthenticationChallengeBuilder->buildUnauthorizedHeader(
                    $request,
                    AgenticCommerceAuthenticationChallengeBuilder::ERROR_INVALID_TOKEN,
                ),
            ],
        );
    }
}
