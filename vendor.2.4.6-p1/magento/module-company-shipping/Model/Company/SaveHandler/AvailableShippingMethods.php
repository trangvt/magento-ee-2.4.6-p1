<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Model\Company\SaveHandler;

use Magento\Company\Model\SaveHandlerInterface;
use Magento\CompanyShipping\Model\ResourceModel\CompanyShippingMethod as CompanyShippingMethodResource;
use Magento\CompanyShipping\Model\CompanyShippingMethodFactory;
use Magento\CompanyShipping\Model\CompanyShippingMethod;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;

/**
 * Save handler for company shipping methods extension attributes
 *
 * Save and process Company available and applicable shipping methods
 */
class AvailableShippingMethods implements SaveHandlerInterface
{
    /**
     * @var CompanyShippingMethodResource
     */
    private $companyShippingMethodResource;

    /**
     * @var CompanyShippingMethodFactory
     */
    private $companyShippingMethodFactory;

    /**
     * Company shipping settings field.
     *
     * @var array
     */
    private $companyShippingSettings = [
        'applicable_shipping_method',
        'available_shipping_methods',
        'use_config_settings_shipping'
    ];

    /**
     * CompanyShippingMethods constructor.
     *
     * @param CompanyShippingMethodResource $companyShippingMethodResource
     * @param CompanyShippingMethodFactory $companyShippingMethodFactory
     */
    public function __construct(
        CompanyShippingMethodResource $companyShippingMethodResource,
        CompanyShippingMethodFactory $companyShippingMethodFactory
    ) {
        $this->companyShippingMethodResource = $companyShippingMethodResource;
        $this->companyShippingMethodFactory = $companyShippingMethodFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        CompanyInterface $company,
        CompanyInterface $initialCompany
    ) {
        $needSave = false;
        $extensionAttributes = $company->getExtensionAttributes();
        $initialExtensionAttributes = $initialCompany->getExtensionAttributes();

        foreach ($this->companyShippingSettings as $companyShippingSetting) {
            $method = 'get'
                . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($companyShippingSetting);
            $result = $extensionAttributes->$method();
            $initialResult = $initialExtensionAttributes->$method();

            if (is_array($result)) {
                $result = implode(',', $result);
            }

            if ($result !== $initialResult) {
                $needSave = true;
                break;
            }
        }

        if ($needSave) {
            $this->saveShippingSettings($company);
        }

        $company->setExtensionAttributes($this->eraseShippingSettingsData($extensionAttributes));
    }

    /**
     * Save shipping settings.
     *
     * @param CompanyInterface $company
     * @throws \Exception
     * @return void
     */
    private function saveShippingSettings(CompanyInterface $company)
    {
        /** @var CompanyShippingMethod $shippingSettings */
        $shippingSettings = $this->companyShippingMethodFactory->create()->load($company->getId());
        $extensionAttributes = $company->getExtensionAttributes();

        if (!$shippingSettings->getId()) {
            $shippingSettings->setCompanyId($company->getId());
        }

        $availableShippingMethods = $extensionAttributes->getAvailableShippingMethods();
        $availableMethods = is_array($availableShippingMethods) ?
            implode(',', $availableShippingMethods)
            : (!empty($availableShippingMethods) ? $availableShippingMethods : '');

        $shippingSettings->setApplicableShippingMethod($extensionAttributes->getApplicableShippingMethod());
        $shippingSettings->setAvailableShippingMethods($availableMethods);
        $shippingSettings->setUseConfigSettings($extensionAttributes->getUseConfigSettingsShipping());
        $this->companyShippingMethodResource->save($shippingSettings);
    }

    /**
     * Erase saved attributes to prevent breaking of populateWithArray.
     *
     * @param CompanyExtensionInterface $extensionAttributes
     * @return CompanyExtensionInterface
     */
    private function eraseShippingSettingsData(CompanyExtensionInterface $extensionAttributes)
    {
        foreach ($this->companyShippingSettings as $companyShippingSetting) {
            $method = 'set'
                . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($companyShippingSetting);
            $extensionAttributes->$method(null);
        }

        return $extensionAttributes;
    }
}
