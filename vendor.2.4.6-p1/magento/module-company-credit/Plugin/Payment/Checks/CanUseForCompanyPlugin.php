<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyCredit\Plugin\Payment\Checks;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;

/**
 * Class CanUseForCompanyPlugin to check payment method applicable to customer
 * @SuppressWarnings("unused")
 */
class CanUseForCompanyPlugin
{
    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * CanUseForCompanyPlugin constructor.
     *
     * @param CompanyManagementInterface $companyManagement
     */
    public function __construct(
        CompanyManagementInterface $companyManagement
    ) {
        $this->companyManagement = $companyManagement;
    }

    /**
     * Check whether payment method is applicable to customer
     *
     * @param CanUseForCompany $subject
     * @param \Closure $proceed
     * @param MethodInterface $paymentMethod
     * @param Quote $quote
     * @return bool
     */
    public function aroundIsApplicable(
        CanUseForCompany $subject,
        \Closure $proceed,
        MethodInterface $paymentMethod,
        Quote $quote
    ) {
        if ($paymentMethod->getCode() == CompanyCreditPaymentConfigProvider::METHOD_NAME) {
            if (!$quote->getCustomerId()
                || !$this->companyManagement->getByCustomerId($quote->getCustomerId())) {
                return false;
            }
        }
        return $proceed($paymentMethod, $quote);
    }
}
