<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Logger;

use Generated\Shared\Transfer\AuditLoggerConfigCriteriaTransfer;
use Spryker\ApiPlatform\Request\RequestAttribute;
use Spryker\Shared\Log\AuditLoggerTrait;
use SprykerFeature\Glue\AgenticCommerce\AgenticCommerceConfig;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceApiUser;
use SprykerFeature\Glue\AgenticCommerce\Security\AgenticCommerceAuthenticationChallengeBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class AgenticCommerceDenialAuditLogger
{
    use AuditLoggerTrait;

    /**
     * @uses \Spryker\Shared\Log\LogConfig::AUDIT_LOGGER_CHANNEL_NAME_SECURITY
     */
    protected const string AUDIT_LOGGER_CHANNEL_NAME_SECURITY = 'security';

    /**
     * @uses \Spryker\Shared\Log\Handler\TagFilterBufferedStreamHandler::RECORD_KEY_CONTEXT_TAGS
     */
    protected const string AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS = 'tags';

    protected const string TAG_AGENTIC_COMMERCE_DENIAL = 'agentic_commerce_denial';

    protected const string ACTION_ACCESS_DENIED = 'Agentic Commerce Access Denied';

    public function logDenial(Request $request, Response $response, ?UserInterface $user): void
    {
        $context = [
            static::AUDIT_LOGGER_RECORD_KEY_CONTEXT_TAGS => [static::TAG_AGENTIC_COMMERCE_DENIAL],
            'status' => $response->getStatusCode(),
            'error' => $this->findOauthError($response),
            'path' => $request->getPathInfo(),
            'operation' => $request->attributes->get(RequestAttribute::API_OPERATION_NAME),
            'mcp_tool' => $request->attributes->get(AgenticCommerceConfig::ATTRIBUTE_REFUSED_MCP_TOOL),
            'missing_scope' => $request->attributes->get(AgenticCommerceConfig::ATTRIBUTE_MISSING_SCOPE),
        ];

        if ($user instanceof AgenticCommerceApiUser) {
            $context['client_id'] = $user->getAgenticCommerceAccessToken()->getClientId();
            $context['consent_uuid'] = $user->getAgenticCommerceAccessToken()->getConsentUuid();
            $context['customer_reference'] = $user->getCustomerReference();
            $context['protected_resource'] = $user->getResourceName();
        }

        $this->getAuditLogger(
            (new AuditLoggerConfigCriteriaTransfer())->setChannelName(static::AUDIT_LOGGER_CHANNEL_NAME_SECURITY),
        )->info(static::ACTION_ACCESS_DENIED, $context);
    }

    protected function findOauthError(Response $response): ?string
    {
        $body = json_decode((string)$response->getContent(), true);
        $error = is_array($body) ? ($body[AgenticCommerceAuthenticationChallengeBuilder::RESPONSE_KEY_ERROR] ?? null) : null;

        return is_string($error) ? $error : null;
    }
}
