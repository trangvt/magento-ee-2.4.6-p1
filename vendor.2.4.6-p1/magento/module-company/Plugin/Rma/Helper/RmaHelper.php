<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Plugin\Rma\Helper;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Rma\Helper\Data;
use Magento\Sales\Model\Order;

/**
 * Rma helper class for company admin account
 */
class RmaHelper
{

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * Initialize constructor
     *
     * @param UserContextInterface $userContext
     */
    public function __construct(
        UserContextInterface $userContext
    ) {
        $this->userContext = $userContext;
    }

    /**
     * Checks for ability to create RMA after canCreateRma
     *
     * @param Data $subject
     * @param bool $result
     * @param int|Order $order
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanCreateRma(Data $subject, $result, $order)
    {
        $customerId = $this->userContext->getUserId();
        $userType = $this->userContext->getUserType();

        if ($result && $order instanceof Order && $customerId &&
            $userType === UserContextInterface::USER_TYPE_CUSTOMER &&
            (int) $customerId !== (int) $order->getCustomerId()) {
            $result = false;
        }
        return $result;
    }
}
