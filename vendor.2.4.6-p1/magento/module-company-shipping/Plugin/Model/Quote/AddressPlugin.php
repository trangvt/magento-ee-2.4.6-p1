<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Plugin\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\CompanyShipping\Model\Config as CompanyShippingConfig;
use Magento\CompanyShipping\Model\Shipping\AvailabilityChecker;

/**
 * Plugin class that manages shipping methods on quote address.
 */
class AddressPlugin
{
    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var CompanyShippingConfig
     */
    private $companyShippingConfig;

    /**
     * @var AvailabilityChecker
     */
    private $availabilityChecker;

    /**
     * AddressPlugin constructor.
     *
     * @param CompanyManagementInterface $companyManagement
     * @param CompanyShippingConfig $companyShippingConfig
     * @param AvailabilityChecker $availabilityChecker
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        CompanyShippingConfig $companyShippingConfig,
        AvailabilityChecker $availabilityChecker
    ) {
        $this->companyManagement = $companyManagement;
        $this->companyShippingConfig = $companyShippingConfig;
        $this->availabilityChecker = $availabilityChecker;
    }

    /**
     * Checking which shipping methods should be added to quote address based on specified settings in B2B features
     *
     * @param Address $subject
     * @param \Closure $proceed
     * @param Rate $rate
     * @return mixed
     */
    public function aroundAddShippingRate(
        Address $subject,
        \Closure $proceed,
        Rate $rate
    ) {
        if ($this->isApplicable($rate, $subject->getQuote())) {
            return $proceed($rate);
        }
        return $subject;
    }

    /**
     * Check if shipping rate is applicable
     *
     * @param Rate $rate
     * @param CartInterface|null $quote
     * @return bool
     */
    private function isApplicable(Rate $rate, CartInterface $quote = null)
    {
        if (!$quote || !$quote->getCustomerId()) {
            return true;
        }

        $customer = $quote->getCustomer();
        $company = null;

        if ($customer && $customer->getId()) {
            $company = $this->companyManagement->getByCustomerId($customer->getId());
        }

        if (!$company) {
            return true;
        }

        return $this->availabilityChecker->isAvailableForCompany($rate->getCarrier(), $company);
    }
}
