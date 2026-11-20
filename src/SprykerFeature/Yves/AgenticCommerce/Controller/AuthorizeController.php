<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Controller;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class AuthorizeController extends AbstractAuthorizationController
{
    public const string PARAMETER_AUTHORIZATION_REQUEST = 'request';

    protected const string PARAMETER_CLIENT_ID = 'client_id';

    protected const string PARAMETER_REDIRECT_URI = 'redirect_uri';

    protected const string PARAMETER_RESPONSE_TYPE = 'response_type';

    protected const string PARAMETER_SCOPE = 'scope';

    protected const string PARAMETER_STATE = 'state';

    protected const string PARAMETER_CODE_CHALLENGE = 'code_challenge';

    protected const string PARAMETER_CODE_CHALLENGE_METHOD = 'code_challenge_method';

    protected const string PARAMETER_RESOURCE = 'resource';

    protected const string PARAMETER_AUTHORIZATION_DETAILS = 'authorization_details';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $this->assertAuthorizationEndpointHost($request);

        $agenticCommerceAuthorizationRequestTransfer = $this->mapRequestToAuthorizationRequest($request, new AgenticCommerceAuthorizationRequestTransfer());
        $agenticCommerceAuthorizationResponseTransfer = $this->getFactory()
            ->getAgenticCommerceClient()
            ->validateAgenticCommerceAuthorizationRequest($agenticCommerceAuthorizationRequestTransfer);

        if (!$agenticCommerceAuthorizationResponseTransfer->getIsValid()) {
            return $this->handleInvalidAuthorizationRequest($agenticCommerceAuthorizationRequestTransfer, $agenticCommerceAuthorizationResponseTransfer);
        }

        $authorizationRequestParker = $this->getFactory()->createAuthorizationRequestParker();
        $authorizationRequestId = $authorizationRequestParker->park($agenticCommerceAuthorizationResponseTransfer);

        if (!$this->getFactory()->getCustomerClient()->isLoggedIn()) {
            $authorizationRequestParker->markPending($authorizationRequestId);

            return $this->redirectResponseInternal(CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN);
        }

        return $this->redirectResponseInternal(
            AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CONSENT,
            [static::PARAMETER_AUTHORIZATION_REQUEST => $authorizationRequestId],
        );
    }

    protected function handleInvalidAuthorizationRequest(
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): View {
        if (!$agenticCommerceAuthorizationResponseTransfer->getIsRedirectable()) {
            return $this->renderAuthorizationError(
                $agenticCommerceAuthorizationResponseTransfer->getErrorCode() === SharedAgenticCommerceConfig::ERROR_INVALID_CLIENT ? static::ERROR_NOT_ALLOWED : static::ERROR_INVALID_LINK,
            );
        }

        $redirectUrl = $this->getFactory()->createAuthorizationResponseUrlBuilder()->buildErrorResponseUrl(
            $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequest() ?? $agenticCommerceAuthorizationRequestTransfer,
            $agenticCommerceAuthorizationResponseTransfer->getErrorCodeOrFail(),
            $agenticCommerceAuthorizationResponseTransfer->getErrorDescription(),
        );

        if ($agenticCommerceAuthorizationResponseTransfer->getAgenticCommerceClient()?->getTrustLevel() === SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED) {
            return $this->renderAuthorizationResponse($redirectUrl);
        }

        return $this->renderAuthorizationError(static::ERROR_INVALID_LINK, $redirectUrl);
    }

    protected function mapRequestToAuthorizationRequest(
        Request $request,
        AgenticCommerceAuthorizationRequestTransfer $agenticCommerceAuthorizationRequestTransfer
    ): AgenticCommerceAuthorizationRequestTransfer {
        return $agenticCommerceAuthorizationRequestTransfer
            ->setClientId($this->findQueryString($request, static::PARAMETER_CLIENT_ID))
            ->setRedirectUri($this->findQueryString($request, static::PARAMETER_REDIRECT_URI))
            ->setResponseType($this->findQueryString($request, static::PARAMETER_RESPONSE_TYPE))
            ->setScope($this->findQueryString($request, static::PARAMETER_SCOPE))
            ->setState($this->findQueryString($request, static::PARAMETER_STATE))
            ->setCodeChallenge($this->findQueryString($request, static::PARAMETER_CODE_CHALLENGE))
            ->setCodeChallengeMethod($this->findQueryString($request, static::PARAMETER_CODE_CHALLENGE_METHOD))
            ->setResource($this->findQueryString($request, static::PARAMETER_RESOURCE))
            ->setAuthorizationDetails($this->findQueryString($request, static::PARAMETER_AUTHORIZATION_DETAILS));
    }

    protected function findQueryString(Request $request, string $name): ?string
    {
        $value = $request->query->all()[$name] ?? null;

        return is_string($value) ? $value : null;
    }
}
