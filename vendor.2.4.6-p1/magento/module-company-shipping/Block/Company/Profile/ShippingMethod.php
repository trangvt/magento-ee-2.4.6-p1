<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Block\Company\Profile;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Shipping\Model\Config as ShippingConfig;
use Magento\CompanyShipping\Model\Config as CompanyShippingConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\CompanyShipping\Model\Shipping\AvailabilityChecker;

/**
 * Shipping Method Profile block
 *
 * @api
 */
class ShippingMethod extends Template
{
    /**
     * @var array
     */
    private $shippingMethods = [];

    /**
     * @var ShippingConfig
     */
    private $shippingConfig;

    /**
     * @var CompanyShippingConfig
     */
    private $companyShippingConfig;

    /**
     * @var CompanyManagementInterface
     */
    private $companyManagement;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AvailabilityChecker
     */
    private $availabilityChecker;

    /**
     * ShippingMethod constructor.
     *
     * @param Context $context
     * @param ShippingConfig $shippingConfig
     * @param CompanyShippingConfig $companyShippingConfig
     * @param CompanyManagementInterface $companyManagement
     * @param UserContextInterface $userContext
     * @param AvailabilityChecker $availabilityChecker
     * @param array $data
     */
    public function __construct(
        Context $context,
        ShippingConfig $shippingConfig,
        CompanyShippingConfig $companyShippingConfig,
        CompanyManagementInterface $companyManagement,
        UserContextInterface $userContext,
        AvailabilityChecker $availabilityChecker,
        array $data = []
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->companyShippingConfig = $companyShippingConfig;
        $this->companyManagement = $companyManagement;
        $this->userContext = $userContext;
        $this->availabilityChecker = $availabilityChecker;
        parent::__construct($context, $data);
    }

    /**
     * Get shipping methods
     *
     * @return array
     */
    public function getShippingMethods()
    {
        if (!$this->shippingMethods) {
            $storeId = $this->_storeManager->getStore()->getId();
            $shippingMethodList = $this->shippingConfig->getActiveCarriers($storeId);
            usort(
                $shippingMethodList,
                function ($comparedObject, $nextObject) {
                    if ($comparedObject->getSortOrder() == $nextObject->getSortOrder()) {
                        return 0;
                    }
                    return ($comparedObject->getSortOrder() < $nextObject->getSortOrder()) ? -1 : 1;
                }
            );

            $company = $this->companyManagement->getByCustomerId($this->userContext->getUserId());
            foreach ($shippingMethodList as $shippingMethod) {
                if ($this->availabilityChecker->isAvailableForCompany($shippingMethod->getCarrierCode(), $company)) {
                    $this->shippingMethods[] = $shippingMethod->getConfigData('title');
                }
            }
        }

        return $this->shippingMethods;
    }

    /**
     * Company has enabled shipping methods.
     *
     * @return bool
     */
    public function hasShippingMethods()
    {
        return count($this->getShippingMethods()) > 0;
    }
}
