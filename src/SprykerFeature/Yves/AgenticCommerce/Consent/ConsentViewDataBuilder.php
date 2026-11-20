<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Consent;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Controller\AbstractAuthorizationController;
use SprykerFeature\Yves\AgenticCommerce\Controller\ConsentController;
use SprykerFeature\Yves\AgenticCommerce\Form\ConsentForm;
use Symfony\Component\Form\FormInterface;

class ConsentViewDataBuilder implements ConsentViewDataBuilderInterface
{
    protected const string VIEW_PARAMETER_CONSENT_FORM = 'consentForm';

    protected const string VIEW_PARAMETER_IS_VERIFIED_CLIENT = 'isVerifiedClient';

    protected const string VIEW_PARAMETER_IS_LOOPBACK_REDIRECT = 'isLoopbackRedirect';

    protected const string VIEW_PARAMETER_CLIENT_HOST = 'clientHost';

    protected const string VIEW_PARAMETER_TRUST_LEVEL = 'trustLevel';

    protected const string VIEW_PARAMETER_CLIENT_ID_HOST = 'clientIdHost';

    protected const string VIEW_PARAMETER_SHOW_LOGO = 'showLogo';

    protected const string VIEW_PARAMETER_HAS_GRANTABLE_SCOPES = 'hasGrantableScopes';

    protected const string VIEW_PARAMETER_IS_ORDER_PLACEMENT_OFFERED = 'isOrderPlacementOffered';

    protected const string VIEW_PARAMETER_CONSTRAINT_FIELDS = 'constraintFields';

    protected const string VIEW_PARAMETER_CONSTRAINT_SCOPES = 'constraintScopes';

    /**
     * @param array<string, mixed> $formOptions
     *
     * @return array<string, mixed>
     */
    public function build(
        FormInterface $consentForm,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        array $formOptions
    ): array {
        $agenticCommerceAuthorizationRequestTransfer = $agenticCommerceAuthorizationResponseTransfer->getAuthorizationRequestOrFail();
        $agenticCommerceClientTransfer = $agenticCommerceAuthorizationResponseTransfer->getAgenticCommerceClientOrFail();
        $clientHost = parse_url((string)$agenticCommerceClientTransfer->getClientUri(), PHP_URL_HOST);
        $trustLevel = $agenticCommerceClientTransfer->getTrustLevel();
        $clientIdHost = $trustLevel === SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_DOMAIN_VERIFIED
            ? parse_url((string)$agenticCommerceClientTransfer->getClientIdentifier(), PHP_URL_HOST)
            : null;
        $principal = (string)$consentForm->get(ConsentForm::FIELD_PRINCIPAL)->getData();

        return [
            static::VIEW_PARAMETER_CONSENT_FORM => $consentForm->createView(),
            ConsentController::VIEW_PARAMETER_AGENTIC_COMMERCE_CLIENT => $agenticCommerceClientTransfer,
            static::VIEW_PARAMETER_IS_VERIFIED_CLIENT => $trustLevel === SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_VERIFIED,
            static::VIEW_PARAMETER_CLIENT_HOST => is_string($clientHost) ? $clientHost : null,
            static::VIEW_PARAMETER_TRUST_LEVEL => $trustLevel,
            static::VIEW_PARAMETER_CLIENT_ID_HOST => is_string($clientIdHost) ? $clientIdHost : null,
            static::VIEW_PARAMETER_SHOW_LOGO => $trustLevel !== SharedAgenticCommerceConfig::CLIENT_TRUST_LEVEL_UNVERIFIED,
            static::VIEW_PARAMETER_HAS_GRANTABLE_SCOPES => array_filter($formOptions[ConsentForm::OPTION_SCOPE_CHOICES_BY_PRINCIPAL]) !== [],
            static::VIEW_PARAMETER_IS_ORDER_PLACEMENT_OFFERED => in_array(SharedAgenticCommerceConfig::SCOPE_ORDER_PLACE, $formOptions[ConsentForm::OPTION_SCOPE_CHOICES_BY_PRINCIPAL][$principal] ?? [], true),
            static::VIEW_PARAMETER_CONSTRAINT_FIELDS => $formOptions[ConsentForm::OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL][$principal] ?? [],
            static::VIEW_PARAMETER_CONSTRAINT_SCOPES => $formOptions[ConsentForm::OPTION_CONSTRAINT_SCOPES],
            AbstractAuthorizationController::VIEW_PARAMETER_REDIRECT_HOST => (string)parse_url((string)$agenticCommerceAuthorizationRequestTransfer->getRedirectUri(), PHP_URL_HOST),
            static::VIEW_PARAMETER_IS_LOOPBACK_REDIRECT => (bool)$agenticCommerceAuthorizationRequestTransfer->getIsLoopbackRedirect(),
        ];
    }
}
