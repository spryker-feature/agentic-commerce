<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Controller;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Authorization\AuthorizationResponseUrlBuilder;
use SprykerFeature\Yves\AgenticCommerce\Form\ConsentForm;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use SprykerShop\Yves\CustomerPage\Form\LoginForm;
use SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 */
class ConsentController extends AbstractAuthorizationController
{
    use LoggerTrait;

    public const string TEMPLATE_CONSENT = '@AgenticCommerce/views/consent/consent.twig';

    public const string TEMPLATE_RELOGIN = '@AgenticCommerce/views/relogin/relogin.twig';

    public const string ERROR_NO_PERMISSION = 'no_permission';

    protected const string VIEW_PARAMETER_LOGIN_FORM = 'loginForm';

    public const string VIEW_PARAMETER_AGENTIC_COMMERCE_CLIENT = 'agenticCommerceClient';

    protected const array CONSTRAINT_REFUSAL_MESSAGES = [
        SharedAgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUESTED_REMOVED,
        SharedAgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_WIDER_THAN_REQUESTED,
    ];

    protected const string LOG_MESSAGE_CONSENT_NOT_CREATED = 'The delegated access consent could not be created.';

    protected const string LOG_CONTEXT_CLIENT_ID = 'clientId';

    protected const string LOG_CONTEXT_ERRORS = 'errors';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $this->assertAuthorizationEndpointHost($request);

        $authorizationRequestId = $this->findAuthorizationRequestId($request);
        $agenticCommerceAuthorizationResponseTransfer = $this->findParkedAuthorization($authorizationRequestId);

        if ($authorizationRequestId === null || $agenticCommerceAuthorizationResponseTransfer === null) {
            return $this->renderAuthorizationError(static::ERROR_EXPIRED);
        }

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null) {
            return $this->redirectToLogin($authorizationRequestId);
        }

        if (!$this->getFactory()->createFreshAuthenticationChecker()->isAuthenticationFresh($customerTransfer)) {
            $this->getFactory()->createAuthorizationRequestParker()->markPending($authorizationRequestId);

            return $this->redirectToAuthorizationRoute(AgenticCommerceRouteProviderPlugin::ROUTE_NAME_RELOGIN, $authorizationRequestId);
        }

        $consentFormDataProvider = $this->getFactory()->createConsentFormDataProvider();
        $formOptions = $consentFormDataProvider->getOptions($agenticCommerceAuthorizationResponseTransfer, $customerTransfer);

        if ($formOptions[ConsentForm::OPTION_PRINCIPAL_CHOICES] === []) {
            $this->getFactory()->createAuthorizationRequestParker()->remove($authorizationRequestId);

            return $this->renderAuthorizationError(static::ERROR_NO_PERMISSION, $this->getFactory()->createAuthorizationResponseUrlBuilder()->buildErrorResponseUrl(
                $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail(),
                AuthorizationResponseUrlBuilder::ERROR_ACCESS_DENIED,
            ));
        }

        $consentForm = $this->getFactory()
            ->getConsentForm($consentFormDataProvider->getData($customerTransfer, $formOptions, $agenticCommerceAuthorizationResponseTransfer), $formOptions)
            ->handleRequest($request);

        if ($this->isDenySubmitted($consentForm)) {
            $this->getFactory()->createAuthorizationRequestParker()->remove($authorizationRequestId);

            return $this->renderAuthorizationResponse($this->getFactory()->createAuthorizationResponseUrlBuilder()->buildErrorResponseUrl(
                $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail(),
                AuthorizationResponseUrlBuilder::ERROR_ACCESS_DENIED,
            ));
        }

        if (!$consentForm->isSubmitted() || !$consentForm->isValid()) {
            return $this->renderConsent($consentForm, $agenticCommerceAuthorizationResponseTransfer, $formOptions);
        }

        return $this->createConsent($request, $consentForm, $authorizationRequestId, $agenticCommerceAuthorizationResponseTransfer, $customerTransfer, $formOptions);
    }

    public function reloginAction(Request $request): View|RedirectResponse
    {
        $this->assertAuthorizationEndpointHost($request);

        $authorizationRequestId = $this->findAuthorizationRequestId($request);
        $agenticCommerceAuthorizationResponseTransfer = $this->findParkedAuthorization($authorizationRequestId);

        if ($authorizationRequestId === null || $agenticCommerceAuthorizationResponseTransfer === null) {
            return $this->renderAuthorizationError(static::ERROR_EXPIRED);
        }

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null) {
            return $this->redirectToLogin($authorizationRequestId);
        }

        if ($this->getFactory()->createFreshAuthenticationChecker()->isAuthenticationFresh($customerTransfer)) {
            return $this->redirectToAuthorizationRoute(AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CONSENT, $authorizationRequestId);
        }

        $this->getFactory()->createAuthorizationRequestParker()->markPending($authorizationRequestId);

        return $this->view([
            static::VIEW_PARAMETER_LOGIN_FORM => $this->getFactory()->getLoginForm([LoginForm::FIELD_EMAIL => $customerTransfer->getEmail()])->createView(),
            static::VIEW_PARAMETER_AGENTIC_COMMERCE_CLIENT => $agenticCommerceAuthorizationResponseTransfer->getAgenticCommerceClientOrFail(),
        ], [], static::TEMPLATE_RELOGIN);
    }

    /**
     * @param array<string, array<string, mixed>> $formOptions
     */
    protected function createConsent(
        Request $request,
        FormInterface $consentForm,
        string $authorizationRequestId,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        CustomerTransfer $customerTransfer,
        array $formOptions
    ): View {
        $agenticCommerceConsentCollectionResponseTransfer = $this->getFactory()->getAgenticCommerceClient()->createAgenticCommerceConsentCollection(
            $this->getFactory()->createConsentCollectionRequestBuilder()->build(
                $request,
                $consentForm->getData(),
                $formOptions,
                $agenticCommerceAuthorizationResponseTransfer,
                $customerTransfer,
            ),
        );

        $agenticCommerceConsentTransfer = $agenticCommerceConsentCollectionResponseTransfer->getAgenticCommerceConsents()->getIterator()->current();

        if ($agenticCommerceConsentCollectionResponseTransfer->getErrors()->count() > 0 && $this->addConstraintRefusalsToForm($consentForm, $agenticCommerceConsentCollectionResponseTransfer)) {
            return $this->renderConsent($consentForm, $agenticCommerceAuthorizationResponseTransfer, $formOptions);
        }

        $this->getFactory()->createAuthorizationRequestParker()->remove($authorizationRequestId);

        if ($agenticCommerceConsentCollectionResponseTransfer->getErrors()->count() > 0 || $agenticCommerceConsentTransfer?->getAuthorizationCode() === null) {
            return $this->handleConsentNotCreated($agenticCommerceConsentCollectionResponseTransfer, $agenticCommerceAuthorizationResponseTransfer);
        }

        return $this->renderAuthorizationResponse($this->getFactory()->createAuthorizationResponseUrlBuilder()->buildCodeResponseUrl(
            $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail(),
            $agenticCommerceConsentTransfer->getAuthorizationCodeOrFail(),
        ));
    }

    protected function addConstraintRefusalsToForm(
        FormInterface $consentForm,
        AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer
    ): bool {
        $constraintsForm = $consentForm->get(ConsentForm::FIELD_CONSTRAINTS);
        $refusals = [];

        foreach ($agenticCommerceConsentCollectionResponseTransfer->getErrors() as $errorTransfer) {
            $constraintType = (string)$errorTransfer->getEntityIdentifier();

            if (!in_array($errorTransfer->getMessage(), static::CONSTRAINT_REFUSAL_MESSAGES, true) || !$constraintsForm->has($constraintType)) {
                return false;
            }

            $refusals[$constraintType] = (string)$errorTransfer->getMessage();
        }

        foreach ($refusals as $constraintType => $refusalMessage) {
            $constraintsForm->get($constraintType)->addError(new FormError($refusalMessage));
        }

        return true;
    }

    protected function handleConsentNotCreated(
        AgenticCommerceConsentCollectionResponseTransfer $agenticCommerceConsentCollectionResponseTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): View {
        $errorMessages = [];

        foreach ($agenticCommerceConsentCollectionResponseTransfer->getErrors() as $errorTransfer) {
            $errorMessages[] = (string)$errorTransfer->getMessage();
        }

        $this->getLogger()->warning(static::LOG_MESSAGE_CONSENT_NOT_CREATED, [
            static::LOG_CONTEXT_CLIENT_ID => $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail()->getClientId(),
            static::LOG_CONTEXT_ERRORS => $errorMessages,
        ]);

        return $this->renderAuthorizationResponse($this->getFactory()->createAuthorizationResponseUrlBuilder()->buildErrorResponseUrl(
            $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail(),
            AuthorizationResponseUrlBuilder::ERROR_SERVER_ERROR,
        ));
    }

    /**
     * @param array<string, array<string, mixed>> $formOptions
     */
    protected function renderConsent(
        FormInterface $consentForm,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        array $formOptions
    ): View {
        return $this->view(
            $this->getFactory()->createConsentViewDataBuilder()->build($consentForm, $agenticCommerceAuthorizationResponseTransfer, $formOptions),
            [],
            static::TEMPLATE_CONSENT,
        );
    }

    protected function findParkedAuthorization(?string $authorizationRequestId): ?AgenticCommerceAuthorizationResponseTransfer
    {
        if ($authorizationRequestId === null) {
            return null;
        }

        return $this->getFactory()->createAuthorizationRequestParker()->findParkedAuthorization($authorizationRequestId);
    }

    protected function redirectToLogin(string $authorizationRequestId): RedirectResponse
    {
        $this->getFactory()->createAuthorizationRequestParker()->markPending($authorizationRequestId);

        return $this->redirectResponseInternal(CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN);
    }

    protected function redirectToAuthorizationRoute(string $routeName, string $authorizationRequestId): RedirectResponse
    {
        return $this->redirectResponseInternal(
            $routeName,
            [AuthorizeController::PARAMETER_AUTHORIZATION_REQUEST => $authorizationRequestId],
        );
    }

    protected function findAuthorizationRequestId(Request $request): ?string
    {
        $authorizationRequestId = $request->query->all()[AuthorizeController::PARAMETER_AUTHORIZATION_REQUEST] ?? null;

        return is_string($authorizationRequestId) && $authorizationRequestId !== '' ? $authorizationRequestId : null;
    }

    protected function isDenySubmitted(FormInterface $consentForm): bool
    {
        return $consentForm->isSubmitted() && $this->isDenyClicked($consentForm) && !$this->hasOwnRootFormError($consentForm);
    }

    protected function hasOwnRootFormError(FormInterface $consentForm): bool
    {
        foreach ($consentForm->getErrors() as $formError) {
            if ($formError instanceof FormError && $formError->getOrigin() === $consentForm) {
                return true;
            }
        }

        return false;
    }

    protected function isDenyClicked(FormInterface $consentForm): bool
    {
        $denyButton = $consentForm->get(ConsentForm::BUTTON_DENY);

        return $denyButton instanceof SubmitButton && $denyButton->isClicked();
    }
}
