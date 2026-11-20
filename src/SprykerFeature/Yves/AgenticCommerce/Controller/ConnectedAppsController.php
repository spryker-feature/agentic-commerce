<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Controller;

use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConditionsTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Form\DisconnectForm;
use SprykerFeature\Yves\AgenticCommerce\Plugin\Router\AgenticCommerceRouteProviderPlugin;
use SprykerShop\Yves\CustomerPage\Plugin\Router\CustomerPageRouteProviderPlugin;
use SprykerShop\Yves\ShopApplication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceFactory getFactory()
 * @method \SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig getConfig()
 */
class ConnectedAppsController extends AbstractController
{
    public const string TEMPLATE_CONNECTED_APPS = '@AgenticCommerce/views/connected-apps/connected-apps.twig';

    protected const string GLOSSARY_KEY_DISCONNECTED = 'agentic_commerce.apps.disconnected';

    protected const string GLOSSARY_KEY_DISCONNECT_FAILED = 'agentic_commerce.apps.disconnect_failed';

    protected const string GLOSSARY_PARAMETER_APP = '%app%';

    protected const string VIEW_PARAMETER_CONSENTS = 'agenticCommerceConsents';

    protected const string VIEW_PARAMETER_DISCONNECT_FORMS = 'disconnectForms';

    protected const string VIEW_PARAMETER_PRINCIPAL_LABELS = 'principalLabels';

    protected const string VIEW_PARAMETER_SCOPE_GLOSSARY_KEYS = 'scopeGlossaryKeys';

    public function indexAction(): View|RedirectResponse
    {
        $this->assertIsConfigured();

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null) {
            return new RedirectResponse($this->getFactory()->getRouter()->generate(CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN));
        }

        $agenticCommerceConsentTransfers = $this->getFactory()->getAgenticCommerceClient()->getAgenticCommerceConsentCollection(
            $this->createActiveConsentsCriteria($customerTransfer),
        )->getAgenticCommerceConsents();

        $disconnectFormViews = [];

        foreach ($agenticCommerceConsentTransfers as $agenticCommerceConsentTransfer) {
            $disconnectFormViews[$agenticCommerceConsentTransfer->getUuidOrFail()] = $this->getFactory()
                ->getDisconnectForm([DisconnectForm::FIELD_CONSENT_UUID => $agenticCommerceConsentTransfer->getUuidOrFail()])
                ->createView();
        }

        return $this->view([
            static::VIEW_PARAMETER_CONSENTS => $agenticCommerceConsentTransfers,
            static::VIEW_PARAMETER_DISCONNECT_FORMS => $disconnectFormViews,
            static::VIEW_PARAMETER_PRINCIPAL_LABELS => $this->getFactory()->createPrincipalLabelResolver()->resolvePrincipalLabels($agenticCommerceConsentTransfers, $customerTransfer),
            static::VIEW_PARAMETER_SCOPE_GLOSSARY_KEYS => $this->getFactory()->createScopeGlossaryKeyReader()->getScopeGlossaryKeys($agenticCommerceConsentTransfers),
        ], [], static::TEMPLATE_CONNECTED_APPS);
    }

    public function disconnectAction(Request $request): RedirectResponse
    {
        $this->assertIsConfigured();

        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null) {
            return new RedirectResponse($this->getFactory()->getRouter()->generate(CustomerPageRouteProviderPlugin::ROUTE_NAME_LOGIN));
        }

        $disconnectForm = $this->getFactory()->getDisconnectForm()->handleRequest($request);
        $connectedAppsResponse = new RedirectResponse($this->getFactory()->getRouter()->generate(AgenticCommerceRouteProviderPlugin::ROUTE_NAME_CONNECTED_APPS));

        if (!$disconnectForm->isSubmitted() || !$disconnectForm->isValid()) {
            $this->addErrorMessage(static::GLOSSARY_KEY_DISCONNECT_FAILED);

            return $connectedAppsResponse;
        }

        $consentUuid = (string)$disconnectForm->getData()[DisconnectForm::FIELD_CONSENT_UUID];
        $agenticCommerceConsentTransfer = $this->findActiveConsent($consentUuid, $customerTransfer);

        if ($agenticCommerceConsentTransfer === null || !$this->revokeConsent($request, $consentUuid, $customerTransfer)) {
            $this->addErrorMessage(static::GLOSSARY_KEY_DISCONNECT_FAILED);

            return $connectedAppsResponse;
        }

        $this->addSuccessMessage($this->getFactory()->getGlossaryStorageClient()->translate(
            static::GLOSSARY_KEY_DISCONNECTED,
            $this->getLocale(),
            [static::GLOSSARY_PARAMETER_APP => (string)$agenticCommerceConsentTransfer->getAgenticCommerceClient()?->getName()],
        ));

        return $connectedAppsResponse;
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

    protected function findActiveConsent(string $consentUuid, CustomerTransfer $customerTransfer): ?AgenticCommerceConsentTransfer
    {
        $agenticCommerceConsentCriteriaTransfer = $this->createActiveConsentsCriteria($customerTransfer);
        $agenticCommerceConsentCriteriaTransfer->getAgenticCommerceConsentConditionsOrFail()->addUuid($consentUuid);

        return $this->getFactory()->getAgenticCommerceClient()
            ->getAgenticCommerceConsentCollection($agenticCommerceConsentCriteriaTransfer)
            ->getAgenticCommerceConsents()
            ->getIterator()
            ->current();
    }

    protected function revokeConsent(Request $request, string $consentUuid, CustomerTransfer $customerTransfer): bool
    {
        $agenticCommerceConsentCollectionResponseTransfer = $this->getFactory()->getAgenticCommerceClient()->updateAgenticCommerceConsentCollection(
            (new AgenticCommerceConsentCollectionRequestTransfer())
                ->setIsTransactional(true)
                ->setRequestClientData($this->getFactory()->createRequestClientDataMapper()->mapRequestToRequestClientData($request))
                ->addAgenticCommerceConsent(
                    (new AgenticCommerceConsentTransfer())
                        ->setUuid($consentUuid)
                        ->setCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                        ->setStatus(SharedAgenticCommerceConfig::CONSENT_STATUS_REVOKED)
                        ->setRevokedBy(SharedAgenticCommerceConfig::CONSENT_REVOKED_BY_CUSTOMER),
                ),
        );

        return $agenticCommerceConsentCollectionResponseTransfer->getErrors()->count() === 0;
    }

    protected function createActiveConsentsCriteria(CustomerTransfer $customerTransfer): AgenticCommerceConsentCriteriaTransfer
    {
        return (new AgenticCommerceConsentCriteriaTransfer())->setAgenticCommerceConsentConditions(
            (new AgenticCommerceConsentConditionsTransfer())
                ->addCustomerReference($customerTransfer->getCustomerReferenceOrFail())
                ->addStatus(SharedAgenticCommerceConfig::CONSENT_STATUS_ACTIVE),
        );
    }
}
