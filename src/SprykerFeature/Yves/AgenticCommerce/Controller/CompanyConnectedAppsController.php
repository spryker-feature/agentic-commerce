<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Controller;

use Generated\Shared\Transfer\AgenticCommerceCompanyAdminRevocationRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceCompanyConsentCriteriaTransfer;
use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\AgenticCommerce\Plugin\Permission\ManageCompanyAgenticCommercePermissionPlugin;
use SprykerFeature\Yves\AgenticCommerce\Form\DisconnectForm;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin;
use SprykerShop\Yves\ShopApplication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class CompanyConnectedAppsController extends AbstractController
{
    public const string TEMPLATE_COMPANY_CONNECTED_APPS = '@AgenticCommerce/views/company-connected-apps/company-connected-apps.twig';

    protected const string GLOSSARY_KEY_REVOKED = 'agentic_commerce.company_apps.revoked';

    protected const string GLOSSARY_KEY_REVOKE_FAILED = 'agentic_commerce.company_apps.revoke_failed';

    protected const string VIEW_PARAMETER_CONSENTS = 'agenticCommerceConsents';

    protected const string VIEW_PARAMETER_REVOKE_FORMS = 'revokeForms';

    protected const string VIEW_PARAMETER_SCOPE_GLOSSARY_KEYS = 'scopeGlossaryKeys';

    public function indexAction(): View|RedirectResponse
    {
        $this->assertIsConfigured();

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null) {
            return $this->createLoginRedirectResponse();
        }

        $companyUserTransfer = $this->getCompanyUserAllowedToManage($customerTransfer);

        $agenticCommerceConsentTransfers = $this->getFactory()->getAgenticCommerceClient()->getAgenticCommerceConsentsByCompany(
            (new AgenticCommerceCompanyConsentCriteriaTransfer())->setAgenticCommerceCompanyConsentConditions(
                (new AgenticCommerceCompanyConsentConditionsTransfer())
                    ->setIdCompany($companyUserTransfer->getFkCompanyOrFail())
                    ->setCompanyUserUuid($companyUserTransfer->getUuidOrFail()),
            ),
        )->getAgenticCommerceConsents();

        $revokeFormViews = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $revokeFormViews[$agenticCommerceConsentTransfer->getUuidOrFail()] = $this->getFactory()
                ->getDisconnectForm([DisconnectForm::FIELD_CONSENT_UUID => $agenticCommerceConsentTransfer->getUuidOrFail()])
                ->createView();
        }

        return $this->view([
            static::VIEW_PARAMETER_CONSENTS => $agenticCommerceConsentTransfers,
            static::VIEW_PARAMETER_REVOKE_FORMS => $revokeFormViews,
            static::VIEW_PARAMETER_SCOPE_GLOSSARY_KEYS => $this->getFactory()->createScopeGlossaryKeyReader()->getScopeGlossaryKeys($agenticCommerceConsentTransfers),
        ], [], static::TEMPLATE_COMPANY_CONNECTED_APPS);
    }

    public function revokeAction(Request $request): RedirectResponse
    {
        $this->assertIsConfigured();

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null) {
            return $this->createLoginRedirectResponse();
        }

        $companyUserTransfer = $this->getCompanyUserAllowedToManage($customerTransfer);

        $revokeForm = $this->getFactory()->getDisconnectForm()->handleRequest($request);
        $companyConnectedAppsResponse = new RedirectResponse(
            $this->getFactory()->getRouter()->generate(AgenticCommerceRouteProviderPlugin::ROUTE_NAME_COMPANY_CONNECTED_APPS),
        );

        if (!$revokeForm->isSubmitted() || !$revokeForm->isValid()) {
            $this->addErrorMessage(static::GLOSSARY_KEY_REVOKE_FAILED);

            return $companyConnectedAppsResponse;
        }

        $agenticCommerceConsentCollectionResponseTransfer = $this->getFactory()->getAgenticCommerceClient()->revokeAgenticCommerceConsentByCompanyAdmin(
            (new AgenticCommerceCompanyAdminRevocationRequestTransfer())
                ->setConsentUuid((string)$revokeForm->getData()[DisconnectForm::FIELD_CONSENT_UUID])
                ->setIdCompany($companyUserTransfer->getFkCompanyOrFail())
                ->setCompanyUserUuid($companyUserTransfer->getUuidOrFail())
                ->setRequestClientData($this->getFactory()->createRequestClientDataMapper()->mapRequestToRequestClientData($request)),
        );

        if ($agenticCommerceConsentCollectionResponseTransfer->getErrors()->count() > 0) {
            foreach ($agenticCommerceConsentCollectionResponseTransfer->getErrors() as $errorTransfer) {
                $this->addErrorMessage((string)$errorTransfer->getMessage());
            }

            return $companyConnectedAppsResponse;
        }

        $this->addSuccessMessage(static::GLOSSARY_KEY_REVOKED);

        return $companyConnectedAppsResponse;
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    protected function getCompanyUserAllowedToManage(CustomerTransfer $customerTransfer): CompanyUserTransfer
    {
        $companyUserTransfer = $customerTransfer->getCompanyUserTransfer();

        if ($companyUserTransfer === null || !$this->getFactory()->getPermissionClient()->can(ManageCompanyAgenticCommercePermissionPlugin::KEY)) {
            throw new AccessDeniedHttpException();
        }

        return $companyUserTransfer;
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function assertIsConfigured(): void
    {
        if (!$this->getFactory()->getConfig()->isConfigured()) {
            throw new NotFoundHttpException();
        }
    }

    protected function createLoginRedirectResponse(): RedirectResponse
    {
        return new RedirectResponse($this->getFactory()->getRouter()->generate(CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN));
    }
}
