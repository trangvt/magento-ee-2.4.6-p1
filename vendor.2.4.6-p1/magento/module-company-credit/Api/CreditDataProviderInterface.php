<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Api;

/**
 * CreditDataProvider interface.
 *
 * @api
 * @since 100.0.0
 */
interface CreditDataProviderInterface
{
    /**
     * Get credit data for company.
     *
     * @param int $companyId
     * @return \Magento\CompanyCredit\Api\Data\CreditDataInterface
     */
    public function get($companyId);
}
