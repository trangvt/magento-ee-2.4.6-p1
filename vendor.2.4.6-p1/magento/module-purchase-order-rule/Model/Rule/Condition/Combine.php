<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Rule\Condition;

use Magento\Framework\Event\ManagerInterface;
use Magento\Rule\Model\Condition\Context;

/**
 * Rule condition for combining multiple rules together
 */
class Combine extends \Magento\SalesRule\Model\Rule\Condition\Combine
{
    /**
     * @param Context $context
     * @param ManagerInterface $eventManager
     * @param Address $conditionAddress
     * @param array $data
     */
    public function __construct(
        Context $context,
        ManagerInterface $eventManager,
        Address $conditionAddress,
        array $data = []
    ) {
        parent::__construct($context, $eventManager, $conditionAddress, $data);
        $this->setType(Combine::class);
    }
}
