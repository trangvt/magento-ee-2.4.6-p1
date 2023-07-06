<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Company\Model;

use Magento\Company\Api\Data\CompanyInterface;

/**
 * Company save handler interface
 *
 * @api
 * @since 100.0.0
 */
interface SaveHandlerInterface
{
    /**
     * Execute save handler
     *
     * @param CompanyInterface $company
     * @param CompanyInterface $initialCompany
     * @return void
     */
    public function execute(CompanyInterface $company, CompanyInterface $initialCompany);
}
