<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Plugin\Sales\Block\Order;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Sales\Block\Order\Recent;

/**
 * Plugin for Recent Orders.
 */
class RecentPlugin
{
    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * RecentPlugin constructor.
     *
     * @param CompanyContext $companyContext
     * @param CompanyManagementInterface $companyManagement
     */
    public function __construct(
        CompanyContext $companyContext,
        CompanyManagementInterface $companyManagement
    ) {
        $this->companyContext = $companyContext;
        $this->companyManagement = $companyManagement;
    }

    /**
     * Hide recent orders block if not allowed.
     *
     * @param Recent $subject
     * @param \Closure $proceed
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundToHtml(Recent $subject, \Closure $proceed)
    {
        $resource = $subject->getData('resource');
        return $resource && $this->isVisible($resource) ? $proceed() : '';
    }

    /**
     * Check if customer is allowed to see block
     *
     * @param string $resource
     *
     * @return bool
     */
    private function isVisible(string $resource): bool
    {
        $company = null;
        if ($this->companyContext->getCustomerId()) {
            $company = $this->companyManagement->getByCustomerId($this->companyContext->getCustomerId());
        }

        return !$company || $this->companyContext->isResourceAllowed($resource);
    }
}
