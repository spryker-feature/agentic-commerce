<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AgenticCommerceAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    protected const string ERROR_DESCRIPTION_TOKEN_REQUIRED = 'A delegated access token is required.';

    public function __construct(protected AgenticCommerceAuthenticationChallengeBuilder $agenticCommerceAuthenticationChallengeBuilder)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            [AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR_DESCRIPTION => static::ERROR_DESCRIPTION_TOKEN_REQUIRED],
            Response::HTTP_UNAUTHORIZED,
            [AgenticCommerceAuthenticationChallengeBuilder::HEADER_WWW_AUTHENTICATE => $this->agenticCommerceAuthenticationChallengeBuilder->buildUnauthorizedHeader($request)],
        );
    }
}
