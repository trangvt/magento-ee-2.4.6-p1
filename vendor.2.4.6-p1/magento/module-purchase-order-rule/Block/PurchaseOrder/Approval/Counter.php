<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Block\PurchaseOrder\Approval;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover;

/**
 * Renders my approval purchase order counter box
 *
 * @api
 */
class Counter extends Template
{
    /**
     * @var AppliedRuleApprover
     */
    private $appliedRuleApprover;

    /**
     * Counter constructor.
     *
     * @param Template\Context $context
     * @param AppliedRuleApprover $appliedRuleApprover
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        AppliedRuleApprover $appliedRuleApprover,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->appliedRuleApprover = $appliedRuleApprover;
    }

    /**
     * Return awaiting for approval purchase orders count
     *
     * @return int
     * @throws LocalizedException
     */
    public function requiresApprovalCount(): int
    {
        return $this->appliedRuleApprover->getPurchaseOrdersRequireApprovalByCurrentCustomer()->getTotalCount();
    }
}
