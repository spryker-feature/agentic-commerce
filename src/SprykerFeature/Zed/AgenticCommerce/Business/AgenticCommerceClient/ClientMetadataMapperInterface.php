<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceClient;

use Generated\Shared\Transfer\AgenticCommerceClientMetadataTransfer;

interface ClientMetadataMapperInterface
{
    /**
     * Maps the registration fields of RFC 7591 (OAuth 2.0 Dynamic Client Registration Protocol) from a decoded JSON object.
     * A field of the wrong type maps to `null` (strings) or an empty list (lists); an empty `client_uri` or `logo_uri` maps to `null`.
     *
     * @param array<mixed> $content
     */
    public function mapRegistrationFields(array $content): AgenticCommerceClientMetadataTransfer;
}
