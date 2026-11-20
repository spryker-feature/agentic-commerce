<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Security;

use ApiPlatform\Metadata\Operation;
use Spryker\ApiPlatform\Request\RequestAttribute;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Service\AgenticCommerce\AgenticCommerceServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AgenticCommerceAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    protected const string ERROR_DESCRIPTION_INSUFFICIENT_SCOPE = 'The access token does not allow this call.';

    protected const string ERROR_DESCRIPTION_ACCESS_DENIED = 'Access to this resource is denied.';

    protected const string SCOPE_ROLE_PATTERN = '/ROLE_SCOPE_[A-Z0-9_]+/';

    public function __construct(
        protected AgenticCommerceAuthenticationChallengeBuilder $agenticCommerceAuthenticationChallengeBuilder,
        protected AgenticCommerceServiceInterface $agenticCommerceService,
        protected Security $security
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $missingScope = $this->findMissingScope($request, $accessDeniedException);

        if ($missingScope === null) {
            return new JsonResponse(
                [AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR_DESCRIPTION => static::ERROR_DESCRIPTION_ACCESS_DENIED],
                Response::HTTP_FORBIDDEN,
            );
        }

        $request->attributes->set(AgenticCommerceConfig::ATTRIBUTE_MISSING_SCOPE, $missingScope);

        return new JsonResponse(
            [
                AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR => AgenticCommerceAuthenticationChallengeBuilder::ERROR_INSUFFICIENT_SCOPE,
                AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR_DESCRIPTION => static::ERROR_DESCRIPTION_INSUFFICIENT_SCOPE,
            ],
            Response::HTTP_FORBIDDEN,
            [
                AgenticCommerceAuthenticationChallengeBuilder::HEADER_WWW_AUTHENTICATE => $this->agenticCommerceAuthenticationChallengeBuilder->buildInsufficientScopeHeader(
                    $request,
                    $missingScope,
                ),
            ],
        );
    }

    protected function findMissingScope(Request $request, AccessDeniedException $accessDeniedException): ?string
    {
        $userRoles = $this->security->getUser()?->getRoles() ?? [];

        foreach ($this->collectCheckedRoles($request, $accessDeniedException) as $checkedRole) {
            if (in_array($checkedRole, $userRoles, true)) {
                continue;
            }

            $scope = $this->findScopeOfRole($checkedRole);

            if ($scope !== null) {
                return $scope;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function collectCheckedRoles(Request $request, AccessDeniedException $accessDeniedException): array
    {
        $checkedRoles = array_values(array_filter($accessDeniedException->getAttributes(), 'is_string'));
        $operation = $request->attributes->get(RequestAttribute::API_OPERATION);

        if ($operation instanceof Operation) {
            preg_match_all(static::SCOPE_ROLE_PATTERN, (string)$operation->getSecurity() . ' ' . (string)$operation->getSecurityPostDenormalize(), $matches);
            $checkedRoles = [...$checkedRoles, ...$matches[0]];
        }

        return array_values(array_unique($checkedRoles));
    }

    protected function findScopeOfRole(string $role): ?string
    {
        foreach ($this->agenticCommerceService->getProtectedResourcesFailClosed() as $agenticCommerceProtectedResourceTransfer) {
            foreach ($agenticCommerceProtectedResourceTransfer->getScopes() as $scope) {
                if ($this->agenticCommerceService->mapScopeToRole($scope) === $role) {
                    return $scope;
                }
            }
        }

        return null;
    }
}
