<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

use SprykerFeature\Zed\AgenticCommerce\Business\Consent\ConsentConstraintReaderInterface;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface;

class AuthorizationDetailsBuilder implements AuthorizationDetailsBuilderInterface
{
    protected const string KEY_TYPE = 'type';

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $agenticCommerceConsentConstraintPlugins
     */
    public function __construct(
        protected array $agenticCommerceConsentConstraintPlugins,
        protected ConsentConstraintReaderInterface $consentConstraintReader
    ) {
    }

    /**
     * A constraint whose plugin is not authorization details aware is left out.
     *
     * @return list<array<string, mixed>>
     */
    public function buildAuthorizationDetails(string $consentUuid): array
    {
        $authorizationDetailsTypes = $this->indexAuthorizationDetailsTypesByConstraintType();
        $authorizationDetails = [];

        foreach ($this->consentConstraintReader->getConsentConstraints($consentUuid) as $agenticCommerceConsentConstraintTransfer) {
            $authorizationDetailsType = $authorizationDetailsTypes[$agenticCommerceConsentConstraintTransfer->getType()] ?? null;

            if ($authorizationDetailsType !== null) {
                $authorizationDetails[] = [static::KEY_TYPE => $authorizationDetailsType] + $agenticCommerceConsentConstraintTransfer->getValue();
            }
        }

        return $authorizationDetails;
    }

    /**
     * @return array<string, string>
     */
    protected function indexAuthorizationDetailsTypesByConstraintType(): array
    {
        $authorizationDetailsTypes = [];

        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if ($agenticCommerceConsentConstraintPlugin instanceof AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface) {
                $authorizationDetailsTypes[$agenticCommerceConsentConstraintPlugin->getType()] = $agenticCommerceConsentConstraintPlugin->getAuthorizationDetailsType();
            }
        }

        return $authorizationDetailsTypes;
    }
}
