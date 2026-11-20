<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Form;

use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConsentForm extends AbstractType
{
    public const string FIELD_PRINCIPAL = 'principal';

    public const string FIELD_SCOPES = 'scopes';

    public const string FIELD_DURATION_IN_DAYS = 'durationInDays';

    public const string FIELD_CONSTRAINTS = 'constraints';

    public const string BUTTON_ALLOW = 'allow';

    public const string BUTTON_DENY = 'deny';

    public const string OPTION_PRINCIPAL_CHOICES = 'principalChoices';

    public const string OPTION_SCOPE_CHOICES_BY_PRINCIPAL = 'scopeChoicesByPrincipal';

    public const string OPTION_DURATION_CHOICES = 'durationChoices';

    public const string OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL = 'constraintFieldsByPrincipal';

    public const string OPTION_CONSTRAINT_SCOPES = 'constraintScopes';

    public const string OPTION_MONEY_FRACTION_DIGITS = 'moneyFractionDigits';

    public const string FIELD_AMOUNT = 'amount';

    public const string FIELD_PERIOD = 'period';

    public const string PRINCIPAL_VALUE_SEPARATOR = ':';

    protected const string FORM_NAME = 'agenticCommerceConsentForm';

    protected const string VALIDATION_GROUP_DEFAULT = 'Default';

    protected const int DECIMAL_BASE = 10;

    protected const string MONEY_INPUT_INTEGER = 'integer';

    protected const string GLOSSARY_KEY_AMOUNT_POSITIVE = 'agentic_commerce.consent.validation.amount_positive';

    protected const string GLOSSARY_KEY_PERIOD_REQUIRED = 'agentic_commerce.consent.validation.period_required';

    protected const string GLOSSARY_KEY_PERIOD_PLACEHOLDER = 'agentic_commerce.consent.period.placeholder';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            static::OPTION_PRINCIPAL_CHOICES,
            static::OPTION_SCOPE_CHOICES_BY_PRINCIPAL,
            static::OPTION_DURATION_CHOICES,
            static::OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL,
            static::OPTION_CONSTRAINT_SCOPES,
            static::OPTION_MONEY_FRACTION_DIGITS,
        ]);
        $resolver->setAllowedTypes(static::OPTION_MONEY_FRACTION_DIGITS, 'int');

        $resolver->setDefault('allow_extra_fields', true);

        $resolver->setDefault('validation_groups', static function (FormInterface $form): array {
            $denyButton = $form->get(static::BUTTON_DENY);

            return $denyButton instanceof SubmitButton && $denyButton->isClicked() ? [] : [static::VALIDATION_GROUP_DEFAULT];
        });
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(static::FIELD_PRINCIPAL, ChoiceType::class, [
            'choices' => $options[static::OPTION_PRINCIPAL_CHOICES],
            'label' => 'agentic_commerce.consent.principal.label',
            'constraints' => [new NotBlank(), new Choice(['choices' => array_values($options[static::OPTION_PRINCIPAL_CHOICES])])],
        ]);

        $this->addScopesFieldForPrincipal($builder, $options[static::OPTION_SCOPE_CHOICES_BY_PRINCIPAL]);
        $this->addConstraintsFieldForPrincipal(
            $builder,
            $options[static::OPTION_CONSTRAINT_FIELDS_BY_PRINCIPAL],
            $options[static::OPTION_CONSTRAINT_SCOPES],
            $options[static::OPTION_MONEY_FRACTION_DIGITS],
        );

        $builder->add(static::FIELD_DURATION_IN_DAYS, ChoiceType::class, [
            'choices' => $options[static::OPTION_DURATION_CHOICES],
            'expanded' => true,
            'label' => 'agentic_commerce.consent.duration.label',
            'constraints' => [new NotBlank(), new Choice(['choices' => array_values($options[static::OPTION_DURATION_CHOICES])])],
        ]);

        $builder->add(static::BUTTON_ALLOW, SubmitType::class, ['label' => 'agentic_commerce.consent.allow']);
        $builder->add(static::BUTTON_DENY, SubmitType::class, ['label' => 'agentic_commerce.consent.deny']);
    }

    /**
     * @param array<string, array<string, string>> $scopeChoicesByPrincipal
     */
    protected function addScopesFieldForPrincipal(FormBuilderInterface $builder, array $scopeChoicesByPrincipal): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($scopeChoicesByPrincipal): void {
            $data = $event->getData();
            $this->addScopesField($event->getForm(), $scopeChoicesByPrincipal[(string)($data[static::FIELD_PRINCIPAL] ?? '')] ?? []);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($scopeChoicesByPrincipal): void {
            $data = $event->getData();
            $principal = is_array($data) && is_string($data[static::FIELD_PRINCIPAL] ?? null) ? $data[static::FIELD_PRINCIPAL] : '';
            $this->addScopesField($event->getForm(), $scopeChoicesByPrincipal[$principal] ?? []);
        });
    }

    /**
     * @param array<string, string> $scopeChoices Glossary key to scope.
     */
    protected function addScopesField(FormInterface $form, array $scopeChoices): void
    {
        $form->add(static::FIELD_SCOPES, ChoiceType::class, [
            'choices' => $scopeChoices,
            'multiple' => true,
            'expanded' => true,
            'constraints' => [new Count(['min' => 1]), new Choice(['choices' => array_values($scopeChoices), 'multiple' => true])],
        ]);
    }

    /**
     * @param array<string, array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer>> $constraintFieldsByPrincipal
     * @param array<string, string> $constraintScopes Constraint type to the scope it belongs to.
     */
    protected function addConstraintsFieldForPrincipal(
        FormBuilderInterface $builder,
        array $constraintFieldsByPrincipal,
        array $constraintScopes,
        int $moneyFractionDigits
    ): void {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($constraintFieldsByPrincipal, $constraintScopes, $moneyFractionDigits): void {
            $data = $event->getData();
            $this->addConstraintsField(
                $event->getForm(),
                $constraintFieldsByPrincipal[(string)($data[static::FIELD_PRINCIPAL] ?? '')] ?? [],
                array_values($data[static::FIELD_SCOPES] ?? []),
                $constraintScopes,
                $moneyFractionDigits,
            );
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($constraintFieldsByPrincipal, $constraintScopes, $moneyFractionDigits): void {
            $data = $event->getData();

            if (!is_array($data)) {
                return;
            }

            $principal = is_string($data[static::FIELD_PRINCIPAL] ?? null) ? $data[static::FIELD_PRINCIPAL] : '';
            $grantedScopes = is_array($data[static::FIELD_SCOPES] ?? null) ? array_values($data[static::FIELD_SCOPES]) : [];
            $constraintFieldTransfers = $constraintFieldsByPrincipal[$principal] ?? [];
            $constraintValues = is_array($data[static::FIELD_CONSTRAINTS] ?? null) ? $data[static::FIELD_CONSTRAINTS] : [];

            foreach (array_keys($constraintFieldTransfers) as $constraintType) {
                if (!$this->isConstraintScopeGranted((string)$constraintType, $grantedScopes, $constraintScopes)) {
                    unset($constraintValues[$constraintType]);
                }
            }

            $data[static::FIELD_CONSTRAINTS] = $constraintValues;
            $event->setData($data);

            $this->addConstraintsField($event->getForm(), $constraintFieldTransfers, $grantedScopes, $constraintScopes, $moneyFractionDigits);
        });
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer> $constraintFieldTransfers
     * @param list<mixed> $grantedScopes
     * @param array<string, string> $constraintScopes
     */
    protected function addConstraintsField(
        FormInterface $form,
        array $constraintFieldTransfers,
        array $grantedScopes,
        array $constraintScopes,
        int $moneyFractionDigits
    ): void {
        $form->add(static::FIELD_CONSTRAINTS, FormType::class, ['label' => false, 'required' => false]);
        $constraintsForm = $form->get(static::FIELD_CONSTRAINTS);

        foreach ($constraintFieldTransfers as $constraintType => $constraintFieldTransfer) {
            $inputKind = $constraintFieldTransfer->getInputKind();

            if (!in_array($inputKind, AgenticCommerceConfig::SUPPORTED_CONSTRAINT_INPUT_KINDS, true)) {
                continue;
            }

            $isScopeGranted = $this->isConstraintScopeGranted((string)$constraintType, $grantedScopes, $constraintScopes);

            match ($inputKind) {
                AgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MONEY => $this->addMoneyField($constraintsForm, (string)$constraintType, $constraintFieldTransfer, $isScopeGranted, $moneyFractionDigits),
                AgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MONEY_PERIOD => $this->addMoneyPeriodField($constraintsForm, (string)$constraintType, $constraintFieldTransfer, $isScopeGranted, $moneyFractionDigits),
                AgenticCommerceConfig::CONSTRAINT_INPUT_KIND_MULTI_SELECT, AgenticCommerceConfig::CONSTRAINT_INPUT_KIND_CATEGORY_TREE => $this->addOptionsField($constraintsForm, (string)$constraintType, $constraintFieldTransfer, $isScopeGranted),
            };
        }
    }

    protected function addMoneyField(
        FormInterface $constraintsForm,
        string $constraintType,
        AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer,
        bool $isScopeGranted,
        int $moneyFractionDigits
    ): void {
        $constraintsForm->add($constraintType, MoneyType::class, [
            'label' => $constraintFieldTransfer->getLabelGlossaryKey(),
            'constraints' => $isScopeGranted ? $this->createMoneyConstraints($constraintFieldTransfer) : [],
        ] + $this->getMoneyOptions($constraintFieldTransfer, $moneyFractionDigits));
    }

    protected function addMoneyPeriodField(
        FormInterface $constraintsForm,
        string $constraintType,
        AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer,
        bool $isScopeGranted,
        int $moneyFractionDigits
    ): void {
        $constraintsForm->add($constraintType, FormType::class, [
            'label' => $constraintFieldTransfer->getLabelGlossaryKey(),
            'required' => false,
            'error_bubbling' => false,
            'constraints' => $isScopeGranted ? [$this->createPeriodRequiredConstraint()] : [],
        ]);

        $moneyPeriodForm = $constraintsForm->get($constraintType);
        $moneyPeriodForm->add(static::FIELD_AMOUNT, MoneyType::class, [
            'label' => false,
            'error_bubbling' => true,
            'constraints' => $isScopeGranted ? $this->createMoneyConstraints($constraintFieldTransfer) : [],
        ] + $this->getMoneyOptions($constraintFieldTransfer, $moneyFractionDigits));
        $moneyPeriodForm->add(static::FIELD_PERIOD, ChoiceType::class, [
            'label' => false,
            'choices' => array_flip($constraintFieldTransfer->getOptions()),
            'placeholder' => static::GLOSSARY_KEY_PERIOD_PLACEHOLDER,
            'required' => false,
            'error_bubbling' => true,
        ]);
    }

    protected function addOptionsField(
        FormInterface $constraintsForm,
        string $constraintType,
        AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer,
        bool $isScopeGranted
    ): void {
        $optionLabels = $constraintFieldTransfer->getOptions();

        $constraintsForm->add($constraintType, ChoiceType::class, [
            'label' => $constraintFieldTransfer->getLabelGlossaryKey(),
            'choices' => array_map('strval', array_keys($optionLabels)),
            'choice_label' => static fn (string $optionValue): string => (string)($optionLabels[$optionValue] ?? $optionValue),
            'choice_translation_domain' => false,
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'error_bubbling' => false,
            'constraints' => $isScopeGranted && $constraintFieldTransfer->getIsRequired()
                ? [new NotBlank(['message' => AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUIRED])]
                : [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getMoneyOptions(AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer, int $moneyFractionDigits): array
    {
        return [
            'currency' => $constraintFieldTransfer->getCurrencyCode() ?? false,
            'divisor' => static::DECIMAL_BASE ** $moneyFractionDigits,
            'scale' => $moneyFractionDigits,
            'input' => static::MONEY_INPUT_INTEGER,
            'required' => false,
            'invalid_message' => static::GLOSSARY_KEY_AMOUNT_POSITIVE,
        ];
    }

    protected function createPeriodRequiredConstraint(): Callback
    {
        return new Callback(function (mixed $moneyPeriodData, ExecutionContextInterface $context): void {
            if (!is_array($moneyPeriodData)) {
                return;
            }

            $hasAmount = ($moneyPeriodData[static::FIELD_AMOUNT] ?? null) !== null;
            $hasPeriod = ($moneyPeriodData[static::FIELD_PERIOD] ?? null) !== null;

            if ($hasAmount && !$hasPeriod) {
                $context->addViolation(static::GLOSSARY_KEY_PERIOD_REQUIRED);
            }
        });
    }

    /**
     * @return list<\Symfony\Component\Validator\Constraint>
     */
    protected function createMoneyConstraints(AgenticCommerceConstraintFieldTransfer $constraintFieldTransfer): array
    {
        $constraints = [new Positive(['message' => static::GLOSSARY_KEY_AMOUNT_POSITIVE])];

        if ($constraintFieldTransfer->getIsRequired()) {
            array_unshift($constraints, new NotBlank(['message' => AgenticCommerceConfig::ERROR_CONSENT_CONSTRAINT_REQUIRED]));
        }

        return $constraints;
    }

    /**
     * @param list<mixed> $grantedScopes
     * @param array<string, string> $constraintScopes
     */
    protected function isConstraintScopeGranted(string $constraintType, array $grantedScopes, array $constraintScopes): bool
    {
        return !isset($constraintScopes[$constraintType]) || in_array($constraintScopes[$constraintType], $grantedScopes, true);
    }
}
