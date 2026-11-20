<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Yves\AgenticCommerce\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DisconnectForm extends AbstractType
{
    public const string FIELD_CONSENT_UUID = 'consentUuid';

    protected const string FORM_NAME = 'agenticCommerceDisconnectForm';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(static::FIELD_CONSENT_UUID, HiddenType::class, [
            'constraints' => [new NotBlank()],
        ]);
    }
}
