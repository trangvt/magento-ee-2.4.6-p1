<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyPayment\Plugin\CustomerBalance\Block\Adminhtml\Sales\Order\Create;

use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment;
use Magento\Payment\Model\Method\Free;
use Magento\Sales\Model\AdminOrder\Create;

/**
 * Plugin for checking is free payment is enabled to use SC on checkout
 */
class PaymentPlugin
{
    /**
     * @var CanUseForCompany
     */
    private $canUseForCompany;

    /**
     * @var Create
     */
    private $orderCreate;

    /**
     * @var Free
     */
    private $freeMethod;

    /**
     * @param CanUseForCompany $canUseForCompany
     * @param Create $orderCreate
     * @param Free $freeMethod
     */
    public function __construct(CanUseForCompany $canUseForCompany, Create $orderCreate, Free $freeMethod)
    {
        $this->canUseForCompany = $canUseForCompany;
        $this->orderCreate = $orderCreate;
        $this->freeMethod = $freeMethod;
    }

    /**
     * Check if free method is available to use SC
     *
     * @param Payment $subject
     * @param bool $result
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanUseCustomerBalance(Payment $subject, $result)
    {
        $quote = $this->orderCreate->getQuote();
        if (!$this->canUseForCompany->isApplicable($this->freeMethod, $quote)) {
            $result = false;
        }
        return $result;
    }
}
