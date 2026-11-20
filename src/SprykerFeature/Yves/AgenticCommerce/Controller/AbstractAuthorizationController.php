<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Controller;

use Spryker\Yves\Kernel\View\View;
use SprykerShop\Yves\ShopApplication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
abstract class AbstractAuthorizationController extends AbstractController
{
    public const string TEMPLATE_AUTHORIZATION_RESPONSE = '@AgenticCommerce/views/authorization-response/authorization-response.twig';

    public const string TEMPLATE_AUTHORIZATION_ERROR = '@AgenticCommerce/views/authorization-error/authorization-error.twig';

    public const string ERROR_INVALID_LINK = 'invalid_link';

    public const string ERROR_NOT_ALLOWED = 'not_allowed';

    public const string ERROR_EXPIRED = 'expired';

    protected const string VIEW_PARAMETER_REDIRECT_URL = 'redirectUrl';

    public const string VIEW_PARAMETER_REDIRECT_HOST = 'redirectHost';

    // Not "error": shop-wide widgets such as the newsletter form read _view.error and would print the code.
    public const string VIEW_PARAMETER_ERROR = 'authorizationError';

    public const string VIEW_PARAMETER_MESSAGE = 'authorizationMessage';

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function assertAuthorizationEndpointHost(Request $request): void
    {
        $agenticCommerceConfig = $this->getFactory()->getConfig();

        if (!$agenticCommerceConfig->isConfigured() || $request->getHost() !== strtolower((string)parse_url($agenticCommerceConfig->getAuthorizationEndpoint(), PHP_URL_HOST))) {
            throw new NotFoundHttpException();
        }
    }

    protected function renderAuthorizationResponse(string $redirectUrl): View
    {
        return $this->view([
            static::VIEW_PARAMETER_REDIRECT_URL => $redirectUrl,
            static::VIEW_PARAMETER_REDIRECT_HOST => (string)parse_url($redirectUrl, PHP_URL_HOST),
        ], [], static::TEMPLATE_AUTHORIZATION_RESPONSE);
    }

    protected function renderAuthorizationError(string $error, ?string $redirectUrl = null): View
    {
        $viewData = [static::VIEW_PARAMETER_ERROR => $error];

        if ($redirectUrl !== null) {
            $viewData[static::VIEW_PARAMETER_REDIRECT_URL] = $redirectUrl;
            $viewData[static::VIEW_PARAMETER_REDIRECT_HOST] = (string)parse_url($redirectUrl, PHP_URL_HOST);
        }

        return $this->view($viewData, [], static::TEMPLATE_AUTHORIZATION_ERROR);
    }
}
