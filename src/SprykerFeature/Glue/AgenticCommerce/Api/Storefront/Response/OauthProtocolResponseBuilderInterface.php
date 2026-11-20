<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Glue\AgenticCommerce\Api\Storefront\Response;

use Generated\Shared\Transfer\AgenticCommerceClientRegistrationResponseTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Symfony\Component\HttpFoundation\Response;

interface OauthProtocolResponseBuilderInterface
{
    public function createTokenResponse(OauthResponseTransfer $oauthResponseTransfer): Response;

    public function createErrorResponse(string $error, ?string $errorDescription = null): Response;

    public function createServerErrorResponse(): Response;

    public function createDelegatedAccessDisabledResponse(int $retryAfterSeconds): Response;

    public function createRevocationResponse(): Response;

    /**
     * @param array<string, mixed> $document
     */
    public function createJsonDocumentResponse(array $document): Response;

    public function createNotFoundResponse(): Response;

    public function createClientInformationResponse(AgenticCommerceClientRegistrationResponseTransfer $registrationResponseTransfer): Response;
}
