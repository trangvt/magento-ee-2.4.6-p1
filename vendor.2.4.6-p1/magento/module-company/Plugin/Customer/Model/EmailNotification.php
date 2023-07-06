<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Plugin\Customer\Model;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\EmailNotificationInterface;

/**
 * Override Email notification class to skip sending welcome emails if the customer is inactive
 */
class EmailNotification
{
    /**
     * Disable sending welcome email to the customer if the customer is marked as inactive while creating in the admin
     *
     * @param EmailNotificationInterface $subject
     * @param \Closure $proceed
     * @param CustomerInterface $customer
     * @param string $type
     * @param string $backUrl
     * @param int|null $storeId
     * @param string $sendemailStoreId
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundNewAccount(
        EmailNotificationInterface $subject,
        \Closure $proceed,
        CustomerInterface $customer,
        $type = null,
        $backUrl = '',
        $storeId = 0,
        $sendemailStoreId = null
    ): void {
        if ($this->isActive($customer)) {
            $proceed($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        }
    }

    /**
     * Is company customer active
     *
     * @param CustomerInterface $customer
     * @return bool
     */
    private function isActive(CustomerInterface $customer): bool
    {
        if ($customer->getExtensionAttributes() && $customer->getExtensionAttributes()->getCompanyAttributes()) {
            return (int)$customer->getExtensionAttributes()->getCompanyAttributes()->getStatus()
                === CompanyCustomerInterface::STATUS_ACTIVE;
        }
        return false;
    }
}
