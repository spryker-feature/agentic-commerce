<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerFeature\Zed\AgenticCommerce\Business;

use Generated\Shared\Transfer\AgenticCommerceClientCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceClientCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionRequestTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionResponseTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCollectionTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentCriteriaTransfer;
use Generated\Shared\Transfer\AgenticCommerceConsentRevocationRequestTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerFeature\Zed\AgenticCommerce\Business\AgenticCommerceBusinessFactory getFactory()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\AgenticCommerce\Persistence\AgenticCommerceEntityManagerInterface getEntityManager()
 */
class AgenticCommerceFacade extends AbstractFacade implements AgenticCommerceFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateAgenticCommerceClientCollection(
        AgenticCommerceClientCollectionRequestTransfer $agenticCommerceClientCollectionRequestTransfer
    ): AgenticCommerceClientCollectionResponseTransfer {
        return $this->getFactory()
            ->createAgenticCommerceClientStatusUpdater()
            ->updateAgenticCommerceClientCollection($agenticCommerceClientCollectionRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function revokeAgenticCommerceConsentsBySystem(
        AgenticCommerceConsentRevocationRequestTransfer $agenticCommerceConsentRevocationRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createConsentRevoker()
            ->revokeAgenticCommerceConsentsBySystem($agenticCommerceConsentRevocationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAgenticCommerceConsentCollection(
        AgenticCommerceConsentCriteriaTransfer $agenticCommerceConsentCriteriaTransfer
    ): AgenticCommerceConsentCollectionTransfer {
        return $this->getFactory()
            ->createConsentReader()
            ->getAgenticCommerceConsentCollection($agenticCommerceConsentCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateAgenticCommerceConsentCollection(
        AgenticCommerceConsentCollectionRequestTransfer $agenticCommerceConsentCollectionRequestTransfer
    ): AgenticCommerceConsentCollectionResponseTransfer {
        return $this->getFactory()
            ->createConsentRevoker()
            ->updateAgenticCommerceConsentCollection($agenticCommerceConsentCollectionRequestTransfer);
    }
}
