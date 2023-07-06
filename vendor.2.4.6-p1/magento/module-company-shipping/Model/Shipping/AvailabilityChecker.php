<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model\Shipping;

use Magento\CompanyShipping\Model\Config;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyShipping\Model\Source\CompanyApplicableShippingMethod;

/**
 * Class for checking available shipping method per company
 */
class AvailabilityChecker
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Is shipping method available for company.
     *
     * @param string $shippingMethodCode
     * @param CompanyInterface $company
     * @return bool
     */
    public function isAvailableForCompany(
        $shippingMethodCode,
        CompanyInterface $company
    ) {
        $companyExtensionAttributes = $company->getExtensionAttributes();

        if (!$companyExtensionAttributes->getUseConfigSettingsShipping()) {
            if ($companyExtensionAttributes->getApplicableShippingMethod() ==
                CompanyApplicableShippingMethod::ALL_SHIPPING_METHODS_VALUE) {
                return true;
            }
            if ($companyExtensionAttributes->getApplicableShippingMethod() ==
                CompanyApplicableShippingMethod::SELECTED_SHIPPING_METHODS_VALUE
            ) {
                return $this->isMethodSelected(
                    $shippingMethodCode,
                    explode(',', $companyExtensionAttributes->getAvailableShippingMethods())
                );
            }
        }

        return $this->isAvailableInB2bConfig($shippingMethodCode);
    }

    /**
     * Is method available in b2b stores configuration.
     *
     * @param string $shippingMethodCode
     * @return bool
     */
    private function isAvailableInB2bConfig($shippingMethodCode)
    {
        return !$this->config->isSelectedShippingMethodsApplied()
        || $this->isMethodSelected($shippingMethodCode, $this->config->getSelectedShippingMethods());
    }

    /**
     * Check if method is selected.
     *
     * @param string $shippingMethodCode
     * @param array $methods
     * @return bool
     */
    private function isMethodSelected($shippingMethodCode, $methods)
    {
        return in_array($shippingMethodCode, $methods);
    }
}
