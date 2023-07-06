<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model\RequisitionListItem;

use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;

/**
 * Requisition List Item validator interface
 *
 * @api
 * @since 100.0.0
 */
interface ValidatorInterface
{
    /**
     * Validate list item
     *
     * @param RequisitionListItemInterface $item
     * @return array Item errors
     */
    public function validate(RequisitionListItemInterface $item);
}
