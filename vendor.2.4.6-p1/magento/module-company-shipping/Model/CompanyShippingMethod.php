<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class for getting company shipping settings from current company.
 */
class CompanyShippingMethod extends AbstractModel
{
    /**#@+
     * Data field constants
     */
    private const APPLICABLE_SHIPPING_METHOD = 'applicable_shipping_method';
    private const AVAILABLE_SHIPPING_METHODS = 'available_shipping_methods';
    private const USE_CONFIG_SETTINGS = 'use_config_settings';
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_idFieldName = 'company_id';
        $this->_init(\Magento\CompanyShipping\Model\ResourceModel\CompanyShippingMethod::class);
    }

    /**
     * Set company ID.
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId($companyId)
    {
        return $this->setData($this->_idFieldName, $companyId);
    }

    /**
     * Get company ID.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->getData($this->_idFieldName);
    }

    /**
     * Set applicable shipping method.
     *
     * @param int $shippingMethodType
     * @return $this
     */
    public function setApplicableShippingMethod($shippingMethodType)
    {
        return $this->setData(self::APPLICABLE_SHIPPING_METHOD, $shippingMethodType);
    }

    /**
     * Set available shipping methods.
     *
     * @param int $availableShippingMethods
     * @return $this
     */
    public function setAvailableShippingMethods($availableShippingMethods)
    {
        return $this->setData(self::AVAILABLE_SHIPPING_METHODS, $availableShippingMethods);
    }

    /**
     * Get applicable shipping method.
     *
     * @return int
     */
    public function getApplicableShippingMethod()
    {
        return $this->getData(self::APPLICABLE_SHIPPING_METHOD);
    }

    /**
     * Get available shipping methods.
     *
     * @return int
     */
    public function getAvailableShippingMethods()
    {
        return $this->getData(self::AVAILABLE_SHIPPING_METHODS);
    }

    /**
     * Set use config settings.
     *
     * @param bool $useConfigSettings
     * @return $this
     */
    public function setUseConfigSettings($useConfigSettings)
    {
        return $this->setData(self::USE_CONFIG_SETTINGS, $useConfigSettings);
    }

    /**
     * Get use config settings.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigSettings()
    {
        return $this->getData(self::USE_CONFIG_SETTINGS);
    }
}
