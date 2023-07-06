<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Plugin\Company;

use Magento\Company\Model\Company\DataProvider;
use Magento\Company\Api\Data\CompanyInterface;

/**
 * Class for adding shipping settings data to company data provider.
 */
class DataProviderPlugin
{
    /**
     * Around get settings data plugin.
     *
     * @param DataProvider $subject
     * @param \Closure $proceed
     * @param CompanyInterface $company
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetSettingsData(
        DataProvider $subject,
        $proceed,
        CompanyInterface $company
    ) {
        $extensionAttributes = $company->getExtensionAttributes();

        $settings = [
            'extension_attributes' => [
                'applicable_shipping_method' => $extensionAttributes->getApplicableShippingMethod(),
                'available_shipping_methods' => $extensionAttributes->getAvailableShippingMethods(),
                'use_config_settings_shipping' => $extensionAttributes->getUseConfigSettingsShipping(),
            ]
        ];

        $originalSettings = $proceed($company);

        return array_replace_recursive($originalSettings, $settings);
    }
}
