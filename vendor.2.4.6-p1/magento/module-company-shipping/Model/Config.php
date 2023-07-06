<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CompanyShipping\Model\Source\ApplicableShippingMethod;

/**
 * Config class to get current B2B shipping methods configuration
 */
class Config
{
    /**
     * Configuration path for applicable B2B shipping methods.
     */
    private const CONFIG_PATH_B2B_APPLICABLE_SHIPPING_METHODS =
        'btob/default_b2b_shipping_methods/applicable_shipping_methods';

    /**
     * Configuration path for available B2B shipping methods.
     */
    private const CONFIG_PATH_B2B_AVAILABLE_SHIPPING_METHODS =
        'btob/default_b2b_shipping_methods/available_shipping_methods';

    /**
     * Scope config.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns selected shipping methods
     *
     * @return array
     */
    public function getSelectedShippingMethods()
    {
        $shippingMethods = [];
        if ($this->isSelectedShippingMethodsApplied()) {
            $availableShippingMethods = $this->getAvailableShippingMethods();
            $shippingMethods = explode(',', $availableShippingMethods);
        }

        return $shippingMethods;
    }

    /**
     * Get applicable shipping method.
     *
     * @return int
     */
    public function getApplicableShippingMethod()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_B2B_APPLICABLE_SHIPPING_METHODS);
    }

    /**
     * Get available shipping methods.
     *
     * @return string
     */
    public function getAvailableShippingMethods()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_B2B_AVAILABLE_SHIPPING_METHODS);
    }

    /**
     * Checks if b2b selected shipping methods applied.
     *
     * @return boolean
     */
    public function isSelectedShippingMethodsApplied()
    {
        return $this->getApplicableShippingMethod() == ApplicableShippingMethod::SELECTED_SHIPPING_METHODS_VALUE;
    }
}
