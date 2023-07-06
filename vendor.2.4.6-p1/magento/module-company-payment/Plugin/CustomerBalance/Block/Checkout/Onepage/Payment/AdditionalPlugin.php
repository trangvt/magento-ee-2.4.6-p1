<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyPayment\Plugin\CustomerBalance\Block\Checkout\Onepage\Payment;

use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\CustomerBalance\Block\Checkout\Onepage\Payment\Additional;
use Magento\Payment\Model\Method\Free;

/**
 * Plugin for checking if Free payment is allowed for company
 */
class AdditionalPlugin
{

    /**
     * @var CanUseForCompany
     */
    private $canUseForCompany;

    /**
     * @var Free
     */
    private $freeMethod;

    /**
     * @param CanUseForCompany $canUseForCompany
     * @param Free $freeMethod
     */
    public function __construct(CanUseForCompany $canUseForCompany, Free $freeMethod)
    {
        $this->canUseForCompany = $canUseForCompany;
        $this->freeMethod = $freeMethod;
    }

    /**
     * Check if free method is available for company to allow SC
     *
     * @param Additional $subject
     * @param bool $result
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsAllowed(Additional $subject, $result)
    {
        $quote = $subject->getQuote();
        if (!$this->canUseForCompany->isApplicable($this->freeMethod, $quote)) {
            $result = false;
        }
        return $result;
    }
}
