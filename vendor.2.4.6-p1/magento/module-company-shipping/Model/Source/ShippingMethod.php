<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\CarrierFactory;

/**
 * Model class that provides a list of available shipping methods.
 */
class ShippingMethod implements OptionSourceInterface
{
    /**
     * Scope config.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CarrierFactory
     */
    private $carrierFactory;

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CarrierFactory $carrierFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CarrierFactory $carrierFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->carrierFactory = $carrierFactory;
    }

    /**
     * Returns shipping methods source data
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        $carriers = $this->scopeConfig->getValue('carriers');

        foreach ($carriers as $carrierCode => $carrier) {
            if (!$this->carrierFactory->create($carrierCode)) {
                continue;
            }
            $shippingTitle = $carrier['title'];

            if (!$carrier['active']) {
                $shippingTitle .= __(' (disabled)');
            }

            $options[] = ['value' => $carrierCode, 'label' => $shippingTitle];
        }

        return $options;
    }
}
