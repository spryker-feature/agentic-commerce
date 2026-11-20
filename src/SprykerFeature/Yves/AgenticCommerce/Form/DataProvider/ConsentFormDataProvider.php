<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Form\DataProvider;

use ArrayObject;
use Generated\Shared\Transfer\AgenticCommerceAuthorizationResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\AgenticCommerceGrantableScopesRequestTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Store\StoreClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Client\AgenticCommerce\AgenticCommerceClientInterface;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig as SharedAgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Yves\AgenticCommerce\Form\ConsentForm;

class ConsentFormDataProvider implements ConsentFormDataProviderInterface
{
    use LoggerTrait;

    protected const string FORMAT_AMBIGUOUS_PRINCIPAL_LABEL = '%s (%s)';

    protected const string LOG_MESSAGE_UNSUPPORTED_INPUT_KIND = 'A delegated access constraint field has an input kind the consent form does not support; the field is left out.';

    protected const string LOG_CONTEXT_CONSTRAINT_TYPE = 'constraintType';

    protected const string LOG_CONTEXT_INPUT_KIND = 'inputKind';

    protected const int DEFAULT_MONEY_FRACTION_DIGITS = 2;

    public function __construct(
        protected AgenticCommerceClientInterface $agenticCommerceClient,
        protected StoreClientInterface $storeClient,
        protected CurrencyClientInterface $currencyClient,
        protected AgenticCommerceConfig $agenticCommerceConfig
    ) {
    }

    /**
     * @param array<string, mixed> $formOptions The options {@link getOptions()} built for the same request.
     *
     * @return array<string, mixed>
     */
    public function getData(
        CustomerTransfer $customerTransfer,
        array $formOptions,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): array {
        $principal = $this->getPreselectedPrincipal($customerTransfer, array_values($formOptions[ConsentForm::OPTION_PRINCIPAL_CHOICES]));

        return [
            ConsentForm::FIELD_PRINCIPAL => $principal,
            ConsentForm::FIELD_SCOPES => array_values($formOptions[ConsentForm::OPTION_SCOPE_CHOICES_BY_PRINCIPAL][$principal] ?? []),
            ConsentForm::FIELD_DURATION_IN_DAYS => array_values($formOptions[ConsentForm::OPTION_DURATION_CHOICES])[0] ?? null,
            ConsentForm::FIELD_CONSTRAINTS => $this->getRequestedConstraintValues(
                $agenticCommerceAuthorizationResponseTransfer,
                $formOptions[ConsentForm::OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL][$principal] ?? [],
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        CustomerTransfer $customerTransfer
    ): array {
        $principalChoices = [];
        $scopeChoicesByPrincipal = [];
        $constraintFieldsByPrincipal = [];
        $currentStoreTransfer = $this->storeClient->getCurrentStore();
        $grantableScopes = array_values($agenticCommerceAuthorizationResponseTransfer->getGrantableScopes());

        // Unpaginated on purpose: the candidates are the principals of one customer.
        foreach ($this->agenticCommerceClient->getAgenticCommercePrincipalCandidates($customerTransfer)->getPrincipals() as $agenticCommercePrincipalTransfer) {
            $principal = $this->buildPrincipalValue($agenticCommercePrincipalTransfer);
            $principalLabel = $this->getPrincipalLabel($agenticCommercePrincipalTransfer);

            if (isset($principalChoices[$principalLabel])) {
                $principalLabel = sprintf(static::FORMAT_AMBIGUOUS_PRINCIPAL_LABEL, $principalLabel, $agenticCommercePrincipalTransfer->getPrincipalReferenceOrFail());
            }

            $principalChoices[$principalLabel] = $principal;
            $scopeChoicesByPrincipal[$principal] = $this->getScopeChoices($agenticCommercePrincipalTransfer, $grantableScopes);
            $constraintFieldsByPrincipal[$principal] = $this->getConstraintFields($agenticCommercePrincipalTransfer, array_values($scopeChoicesByPrincipal[$principal]), $currentStoreTransfer, $agenticCommerceAuthorizationResponseTransfer);
        }

        return [
            ConsentForm::OPTION_PRINCIPAL_CHOICES => $principalChoices,
            ConsentForm::OPTION_SCOPE_CHOICES_BY_PRINCIPAL => $scopeChoicesByPrincipal,
            ConsentForm::OPTION_DURATION_CHOICES => $this->getDurationChoices(),
            ConsentForm::OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL => $constraintFieldsByPrincipal,
            ConsentForm::OPTION_CONSTRAINT_SCOPES => $this->agenticCommerceConfig->getConsentConstraintScopes(),
            ConsentForm::OPTION_MONEY_FRACTION_DIGITS => $this->getMoneyFractionDigits($currentStoreTransfer),
        ];
    }

    public function mapPrincipalValueToPrincipal(string $principal): AgenticCommercePrincipalTransfer
    {
        [$principalType, $principalReference] = explode(ConsentForm::PRINCIPAL_VALUE_SEPARATOR, $principal, 2) + [1 => ''];

        return (new AgenticCommercePrincipalTransfer())
            ->setPrincipalType($principalType)
            ->setPrincipalReference($principalReference);
    }

    /**
     * @param array<string, mixed> $constraintValues Constraint type to the submitted value; a money value is in cents.
     * @param array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer> $constraintFieldTransfers
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\AgenticCommerceConsentConstraintTransfer>
     */
    public function mapConstraintValuesToConsentConstraints(array $constraintValues, array $constraintFieldTransfers): ArrayObject
    {
        $agenticCommerceConsentConstraintTransfers = new ArrayObject();

        foreach ($constraintFieldTransfers as $constraintType => $constraintFieldTransfer) {
            $constraintValue = $this->mapSubmittedValueToConstraintValue($constraintValues[$constraintType] ?? null, $constraintFieldTransfer);

            if ($constraintValue === null) {
                continue;
            }

            $agenticCommerceConsentConstraintTransfers->append(
                (new AgenticCommerceConsentConstraintTransfer())
                    ->setType((string)$constraintType)
                    ->setValue($constraintValue),
            );
        }

        return $agenticCommerceConsentConstraintTransfers;
    }

    /**
     * @return array<string, mixed>|null Null when the field was left empty.
     */
    protected function mapSubmittedValueToConstraintValue(mixed $submittedValue, AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer): ?array
    {
        if (!in_array($constraintFieldTransfer->getInputKind(), SharedAgenticCommerceConfig::SUPPORTED_CONSTRAINT_INPUT_KINDS, true)) {
            return null;
        }

        return match ($constraintFieldTransfer->getInputKind()) {
            SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MONEY => $submittedValue === null ? null : [
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_AMOUNT => $submittedValue,
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CURRENCY => $constraintFieldTransfer->getCurrencyCode(),
            ],
            SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MONEY_PERIOD => !is_array($submittedValue) || ($submittedValue[ConsentForm::FIELD_AMOUNT] ?? null) === null ? null : [
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_AMOUNT => $submittedValue[ConsentForm::FIELD_AMOUNT],
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_CURRENCY => $constraintFieldTransfer->getCurrencyCode(),
                SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_PERIOD => $submittedValue[ConsentForm::FIELD_PERIOD] ?? null,
            ],
            SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MULTI_SELECT, SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_CATEGORY_TREE => !is_array($submittedValue) || $submittedValue === [] || $constraintFieldTransfer->getValueKey() === null ? null : [
                $constraintFieldTransfer->getValueKeyOrFail() => array_map('strval', array_values($submittedValue)),
            ],
        };
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer> $constraintFieldTransfers
     *
     * @return array<string, mixed> Constraint type to the form value of its field.
     */
    protected function getRequestedConstraintValues(
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer,
        array $constraintFieldTransfers
    ): array {
        $requestedConstraintValues = [];

        foreach ($agenticCommerceAuthorizationResponseTransfer->getRequestedConstraints() as $requestedConstraintTransfer) {
            $constraintType = $requestedConstraintTransfer->getTypeOrFail();
            $constraintFieldTransfer = $constraintFieldTransfers[$constraintType] ?? null;

            if ($constraintFieldTransfer === null) {
                continue;
            }

            $requestedConstraintValues[$constraintType] = $this->mapConstraintValueToFormValue($requestedConstraintTransfer->getValue(), $constraintFieldTransfer);
        }

        return $requestedConstraintValues;
    }

    /**
     * @param array<string, mixed> $constraintValue
     */
    protected function mapConstraintValueToFormValue(array $constraintValue, AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer): mixed
    {
        if (!in_array($constraintFieldTransfer->getInputKind(), SharedAgenticCommerceConfig::SUPPORTED_CONSTRAINT_INPUT_KINDS, true)) {
            return null;
        }

        return match ($constraintFieldTransfer->getInputKind()) {
            SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MONEY => $constraintValue[SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_AMOUNT] ?? null,
            SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MONEY_PERIOD => [
                ConsentForm::FIELD_AMOUNT => $constraintValue[SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_AMOUNT] ?? null,
                ConsentForm::FIELD_PERIOD => $constraintValue[SharedAgenticCommerceConfig::CONSTRAINT_VALUE_KEY_PERIOD] ?? null,
            ],
            SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MULTI_SELECT, SharedAgenticCommerceConfig::CONSTRAINT_INPUT_KIND_CATEGORY_TREE => $constraintValue[$constraintFieldTransfer->getValueKey()] ?? [],
        };
    }

    protected function getMoneyFractionDigits(StoreTransfer $storeTransfer): int
    {
        return $this->currencyClient->fromIsoCode($storeTransfer->getDefaultCurrencyIsoCodeOrFail())->getFractionDigits() ?? static::DEFAULT_MONEY_FRACTION_DIGITS;
    }

    /**
     * @return array<int, int> Duration label to duration in days; PHP keeps the numeric labels as int keys.
     */
    protected function getDurationChoices(): array
    {
        $consentDurationsInDays = $this->agenticCommerceClient->getAgenticCommerceConsentDurationsInDays();

        return array_combine(array_map('strval', $consentDurationsInDays), $consentDurationsInDays);
    }

    /**
     * @param list<string> $principalChoices
     */
    protected function getPreselectedPrincipal(CustomerTransfer $customerTransfer, array $principalChoices): ?string
    {
        $companyUserUuid = $customerTransfer->getCompanyUserTransfer()?->getUuid();

        if ($companyUserUuid !== null) {
            $currentCompanyUserPrincipal = SharedAgenticCommerceConfig::PRINCIPAL_TYPE_COMPANY_USER . ConsentForm::PRINCIPAL_VALUE_SEPARATOR . $companyUserUuid;

            if (in_array($currentCompanyUserPrincipal, $principalChoices, true)) {
                return $currentCompanyUserPrincipal;
            }
        }

        return $principalChoices[0] ?? null;
    }

    protected function buildPrincipalValue(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): string
    {
        return $agenticCommercePrincipalTransfer->getPrincipalTypeOrFail()
            . ConsentForm::PRINCIPAL_VALUE_SEPARATOR
            . $agenticCommercePrincipalTransfer->getPrincipalReferenceOrFail();
    }

    protected function getPrincipalLabel(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer): string
    {
        return $agenticCommercePrincipalTransfer->getDisplayName() ?: $agenticCommercePrincipalTransfer->getPrincipalReferenceOrFail();
    }

    /**
     * @param list<string> $grantableScopes
     *
     * @return array<string, string> Glossary key to scope.
     */
    protected function getScopeChoices(AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer, array $grantableScopes): array
    {
        if ($grantableScopes === []) {
            return [];
        }

        $agenticCommerceGrantableScopesResponseTransfer = $this->agenticCommerceClient->getAgenticCommerceGrantableScopes(
            (new AgenticCommerceGrantableScopesRequestTransfer())
                ->setPrincipal($agenticCommercePrincipalTransfer)
                ->setRequestedScopes($grantableScopes),
        );

        $scopeChoices = [];

        foreach ($agenticCommerceGrantableScopesResponseTransfer->getScopes() as $agenticCommerceScopeTransfer) {
            $scopeChoices[$agenticCommerceScopeTransfer->getGlossaryKeyOrFail()] = $agenticCommerceScopeTransfer->getNameOrFail();
        }

        return $scopeChoices;
    }

    /**
     * @param list<string> $scopes
     *
     * @return array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer> Constraint type to its field.
     */
    protected function getConstraintFields(
        AgenticCommercePrincipalTransfer $agenticCommercePrincipalTransfer,
        array $scopes,
        StoreTransfer $storeTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): array {
        if ($scopes === []) {
            return [];
        }

        $agenticCommerceConstraintFieldCollectionTransfer = $this->agenticCommerceClient->getAgenticCommerceConsentConstraintFields(
            (new AgenticCommerceConstraintFieldRequestTransfer())
                ->setPrincipal($agenticCommercePrincipalTransfer)
                ->setScopes($scopes)
                ->setStoreName($storeTransfer->getName())
                ->setCurrencyCode($storeTransfer->getDefaultCurrencyIsoCode()),
        );

        $constraintFieldTransfers = [];

        foreach ($agenticCommerceConstraintFieldCollectionTransfer->getFields() as $agenticCommerceConstraintFieldTransfer) {
            if (!in_array($agenticCommerceConstraintFieldTransfer->getInputKind(), SharedAgenticCommerceConfig::SUPPORTED_CONSTRAINT_INPUT_KINDS, true)) {
                $this->getLogger()->warning(static::LOG_MESSAGE_UNSUPPORTED_INPUT_KIND, [
                    static::LOG_CONTEXT_CONSTRAINT_TYPE => $agenticCommerceConstraintFieldTransfer->getType(),
                    static::LOG_CONTEXT_INPUT_KIND => $agenticCommerceConstraintFieldTransfer->getInputKind(),
                ]);

                continue;
            }

            $constraintFieldTransfers[$agenticCommerceConstraintFieldTransfer->getTypeOrFail()] = $this->restrictOptionsToRequestedValues(
                $agenticCommerceConstraintFieldTransfer,
                $agenticCommerceAuthorizationResponseTransfer,
            );
        }

        return $constraintFieldTransfers;
    }

    protected function restrictOptionsToRequestedValues(
        AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer,
        AgenticCommerceAuthorizationResponseTransfer $agenticCommerceAuthorizationResponseTransfer
    ): AgenticCommerceConstraintFieldTransfer {
        if ($constraintFieldTransfer->getValueKey() === null) {
            return $constraintFieldTransfer;
        }

        foreach ($agenticCommerceAuthorizationResponseTransfer->getRequestedConstraints() as $requestedConstraintTransfer) {
            if ($requestedConstraintTransfer->getType() !== $constraintFieldTransfer->getType()) {
                continue;
            }

            $requestedValues = array_values(array_map('strval', $requestedConstraintTransfer->getValue()[$constraintFieldTransfer->getValueKeyOrFail()] ?? []));
            $optionParents = $constraintFieldTransfer->getOptionParents();
            $options = [];

            foreach ($constraintFieldTransfer->getOptions() as $optionValue => $optionLabel) {
                if ($this->isOptionOrAncestorRequested((string)$optionValue, $requestedValues, $optionParents)) {
                    $options[$optionValue] = $optionLabel;
                }
            }

            $constraintFieldTransfer->setOptions($options);
            $constraintFieldTransfer->setOptionParents(array_intersect_key($optionParents, $options));
        }

        return $constraintFieldTransfer;
    }

    /**
     * @param list<string> $requestedValues
     * @param array<string, string> $optionParents
     */
    protected function isOptionOrAncestorRequested(string $optionValue, array $requestedValues, array $optionParents): bool
    {
        $visitedOptionValues = [];

        while ($optionValue !== '' && !isset($visitedOptionValues[$optionValue])) {
            if (in_array($optionValue, $requestedValues, true)) {
                return true;
            }

            $visitedOptionValues[$optionValue] = true;
            $optionValue = (string)($optionParents[$optionValue] ?? '');
        }

        return false;
    }
}
