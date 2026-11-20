<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\Authorization;

use Generated\Shared\Transfer\AgenticCommerceAuthorizationDetailsParseResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use JsonException;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface;

class AuthorizationDetailsParser implements AuthorizationDetailsParserInterface
{
    protected const string KEY_TYPE = 'type';

    protected const string JSON_ARRAY_START = '[';

    protected const string ERROR_DESCRIPTION_TOO_LARGE = 'The authorization_details parameter is too large.';

    protected const string ERROR_DESCRIPTION_NOT_JSON = 'The authorization_details parameter is not valid JSON.';

    protected const string ERROR_DESCRIPTION_NOT_A_LIST = 'The authorization_details parameter must be a JSON array of objects.';

    protected const string ERROR_DESCRIPTION_TOO_MANY_OBJECTS = 'The authorization_details parameter has too many objects.';

    protected const string ERROR_DESCRIPTION_OBJECT_WITHOUT_TYPE = 'Every authorization_details object must be a JSON object with a string "type".';

    protected const string ERROR_DESCRIPTION_UNKNOWN_TYPE = 'An authorization_details object has an unknown type.';

    protected const string ERROR_DESCRIPTION_DUPLICATE_TYPE = 'The authorization_details parameter has two objects of one type.';

    protected const string ERROR_DESCRIPTION_INVALID_VALUE = 'An authorization_details object has an invalid value.';

    /**
     * @param list<\SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $agenticCommerceConsentConstraintPlugins
     */
    public function __construct(protected array $agenticCommerceConsentConstraintPlugins)
    {
    }

    public function parse(
        ?string $authorizationDetails,
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): AgenticCommerceAuthorizationDetailsParseResponseTransfer {
        $parseResponseTransfer = (new AgenticCommerceAuthorizationDetailsParseResponseTransfer())->setIsValid(true);

        if ($authorizationDetails === null || $authorizationDetails === '') {
            return $parseResponseTransfer;
        }

        if (strlen($authorizationDetails) > AgenticCommerceConfig::AUTHORIZATION_DETAILS_MAX_BYTES) {
            return $this->createInvalidResponse(static::ERROR_DESCRIPTION_TOO_LARGE);
        }

        try {
            $authorizationDetailsObjects = json_decode($authorizationDetails, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->createInvalidResponse(static::ERROR_DESCRIPTION_NOT_JSON);
        }

        // RFC 9396, section 2: a JSON object with the keys "0", "1" decodes to a list too, but is not a JSON array.
        if (!str_starts_with(ltrim($authorizationDetails), static::JSON_ARRAY_START)) {
            return $this->createInvalidResponse(static::ERROR_DESCRIPTION_NOT_A_LIST);
        }

        $errorDescription = $this->addRequestedConstraints($parseResponseTransfer, $authorizationDetailsObjects, $constraintFieldRequestTransfer);

        return $errorDescription === null ? $parseResponseTransfer : $this->createInvalidResponse($errorDescription);
    }

    protected function addRequestedConstraints(
        AgenticCommerceAuthorizationDetailsParseResponseTransfer $parseResponseTransfer,
        mixed $authorizationDetailsObjects,
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): ?string {
        if (!is_array($authorizationDetailsObjects) || !array_is_list($authorizationDetailsObjects)) {
            return static::ERROR_DESCRIPTION_NOT_A_LIST;
        }

        if (count($authorizationDetailsObjects) > AgenticCommerceConfig::AUTHORIZATION_DETAILS_MAX_OBJECTS) {
            return static::ERROR_DESCRIPTION_TOO_MANY_OBJECTS;
        }

        $pluginsByAuthorizationDetailsType = $this->indexPluginsByAuthorizationDetailsType();

        foreach ($authorizationDetailsObjects as $authorizationDetailsObject) {
            $errorDescription = $this->addRequestedConstraint($parseResponseTransfer, $authorizationDetailsObject, $pluginsByAuthorizationDetailsType, $constraintFieldRequestTransfer);

            if ($errorDescription !== null) {
                return $errorDescription;
            }
        }

        return null;
    }

    /**
     * @param array<string, \SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface> $pluginsByAuthorizationDetailsType
     */
    protected function addRequestedConstraint(
        AgenticCommerceAuthorizationDetailsParseResponseTransfer $parseResponseTransfer,
        mixed $authorizationDetailsObject,
        array $pluginsByAuthorizationDetailsType,
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): ?string {
        $authorizationDetailsType = is_array($authorizationDetailsObject) ? ($authorizationDetailsObject[static::KEY_TYPE] ?? null) : null;

        if (!is_string($authorizationDetailsType)) {
            return static::ERROR_DESCRIPTION_OBJECT_WITHOUT_TYPE;
        }

        $plugin = $pluginsByAuthorizationDetailsType[$authorizationDetailsType] ?? null;

        if ($plugin === null) {
            return static::ERROR_DESCRIPTION_UNKNOWN_TYPE;
        }

        foreach ($parseResponseTransfer->getRequestedConstraints() as $requestedConstraintTransfer) {
            if ($requestedConstraintTransfer->getType() === $plugin->getType()) {
                return static::ERROR_DESCRIPTION_DUPLICATE_TYPE;
            }
        }

        unset($authorizationDetailsObject[static::KEY_TYPE]);

        if ($plugin->validate($authorizationDetailsObject, $constraintFieldRequestTransfer) !== []) {
            return static::ERROR_DESCRIPTION_INVALID_VALUE;
        }

        $parseResponseTransfer->addRequestedConstraint(
            (new AgenticCommerceConsentConstraintTransfer())->setType($plugin->getType())->setValue($authorizationDetailsObject),
        );

        return null;
    }

    /**
     * @return array<string, \SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceConsentConstraintPluginInterface>
     */
    protected function indexPluginsByAuthorizationDetailsType(): array
    {
        $pluginsByAuthorizationDetailsType = [];

        foreach ($this->agenticCommerceConsentConstraintPlugins as $agenticCommerceConsentConstraintPlugin) {
            if ($agenticCommerceConsentConstraintPlugin instanceof AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface) {
                $pluginsByAuthorizationDetailsType[$agenticCommerceConsentConstraintPlugin->getAuthorizationDetailsType()] = $agenticCommerceConsentConstraintPlugin;
            }
        }

        return $pluginsByAuthorizationDetailsType;
    }

    protected function createInvalidResponse(string $errorDescription): AgenticCommerceAuthorizationDetailsParseResponseTransfer
    {
        return (new AgenticCommerceAuthorizationDetailsParseResponseTransfer())
            ->setIsValid(false)
            ->setErrorDescription($errorDescription);
    }
}
