<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\Customer\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin assign company customer group id to customers after shared catalog was changed.
 */
class SessionPlugin
{
    /**
     * Get customer group id.
     *
     * @param Session $subject
     * @param int $groupId
     * @return int
     */
    public function afterGetCustomerGroupId(Session $subject, $groupId)
    {
        try {
            if ($subject->getCustomerData()) {
                if ($groupId != $subject->getCustomerData()->getGroupId()) {
                    $customerGroupId = $subject->getCustomerData()->getGroupId();
                    $subject->setCustomerGroupId($customerGroupId);
                    return $customerGroupId;
                }
            }
            return $groupId;
        } catch (NoSuchEntityException $e) {
            return $groupId;
        }
    }
}
