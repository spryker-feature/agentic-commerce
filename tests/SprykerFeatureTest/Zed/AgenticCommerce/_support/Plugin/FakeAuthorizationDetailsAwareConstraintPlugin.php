<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeatureTest\Zed\AgenticCommerce\Plugin;

use Generated\Shared\Transfer\AgenticCommerceConstraintFieldRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConstraintFieldTransfer;
use Generated\Shared\Transfer\AgenticCommercePrincipalTransfer;
use SprykerFeature\Shared\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Zed\AgenticCommerce\Dependency\Plugin\AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface;

class FakeAuthorizationDetailsAwareConstraintPlugin implements AgenticCommerceAuthorizationDetailsAwareConstraintPluginInterface
{
    public const string TYPE = 'test_limit';

    public const string AUTHORIZATION_DETAILS_TYPE = AgenticCommerceConfig::AUTHORIZATION_DETAILS_TYPE_BASE . 'test-limit';

    public const string VALUE_KEY_LIMIT = 'limit';

    public const string VALUE_KEY_ITEMS = 'items';

    protected int $fieldDefinitionCallCount = 0;

    /**
     * @param list<\Generated\Shared\Transfer\ErrorTransfer> $validationErrorTransfers
     * @param array<string, string> $options
     * @param list<\Generated\Shared\Transfer\ErrorTransfer> $validationErrorTransfersWithPrincipal
     */
    public function __construct(
        protected bool $isApplicable = true,
        protected bool $isRequired = false,
        protected array $validationErrorTransfers = [],
        protected array $options = [],
        protected array $validationErrorTransfersWithPrincipal = []
    ) {
    }

    public function getType(): string
    {
        return static::TYPE;
    }

    public function getAuthorizationDetailsType(): string
    {
        return static::AUTHORIZATION_DETAILS_TYPE;
    }

    public function isApplicable(AgenticCommercePrincipalTransfer $principalTransfer, array $scopes): bool
    {
        return $this->isApplicable;
    }

    public function isRequired(AgenticCommercePrincipalTransfer $principalTransfer, array $scopes): bool
    {
        return $this->isRequired;
    }

    public function getFieldDefinition(
        AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer
    ): AgenticCommerceConstraintFieldTransfer {
        $this->fieldDefinitionCallCount++;

        return (new AgenticCommerceConstraintFieldTransfer())
            ->setValueKey(static::VALUE_KEY_ITEMS)
            ->setOptions($this->options);
    }

    public function validate(array $value, AgenticCommerceConstraintFieldRequestTransfer $constraintFieldRequestTransfer): array
    {
        if ($constraintFieldRequestTransfer->getPrincipal() !== null && $this->validationErrorTransfersWithPrincipal !== []) {
            return $this->validationErrorTransfersWithPrincipal;
        }

        return $this->validationErrorTransfers;
    }

    public function isNarrowerOrEqual(array $requestedValue, array $submittedValue): bool
    {
        $requestedLimit = $requestedValue[static::VALUE_KEY_LIMIT] ?? null;
        $submittedLimit = $submittedValue[static::VALUE_KEY_LIMIT] ?? null;

        if (!is_int($requestedLimit) || !is_int($submittedLimit)) {
            return false;
        }

        return $submittedLimit <= $requestedLimit;
    }

    public function getFieldDefinitionCallCount(): int
    {
        return $this->fieldDefinitionCallCount;
    }
}
