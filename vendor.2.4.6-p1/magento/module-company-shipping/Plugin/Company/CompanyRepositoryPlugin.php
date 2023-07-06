<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Plugin\Company;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyShipping\Model\CompanyShippingMethodFactory;
use Magento\CompanyShipping\Model\ResourceModel\CompanyShippingMethod;

/**
 * Plugin for adding company extension attributes.
 */
class CompanyRepositoryPlugin
{
    /**
     * @var CompanyShippingMethod
     */
    private $companyShippingMethodResource;

    /**
     * @var CompanyShippingMethodFactory
     */
    private $companyShippingMethodFactory;

    /**
     * @var CompanyExtensionFactory
     */
    private $companyExtensionFactory;

    /**
     * CompanyRepositoryPlugin constructor.
     *
     * @param CompanyShippingMethod $companyShippingMethodResource
     * @param CompanyShippingMethodFactory $companyShippingMethodFactory
     * @param CompanyExtensionFactory $companyExtensionFactory
     */
    public function __construct(
        CompanyShippingMethod $companyShippingMethodResource,
        CompanyShippingMethodFactory $companyShippingMethodFactory,
        CompanyExtensionFactory $companyExtensionFactory
    ) {
        $this->companyShippingMethodResource = $companyShippingMethodResource;
        $this->companyShippingMethodFactory = $companyShippingMethodFactory;
        $this->companyExtensionFactory = $companyExtensionFactory;
    }

    /**
     * After get company.
     *
     * @param CompanyRepositoryInterface $subject
     * @param CompanyInterface $company
     * @return CompanyInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        CompanyRepositoryInterface $subject,
        CompanyInterface $company
    ) {
        $this->initShippingMethodsForCompany($company);
        return $company;
    }

    /**
     * After save company.
     *
     * @param CompanyRepositoryInterface $subject
     * @param CompanyInterface $company
     * @return CompanyInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        CompanyRepositoryInterface $subject,
        CompanyInterface $company
    ) {
        $this->initShippingMethodsForCompany($company);
        return $company;
    }

    /**
     * Init shipping methods for the company
     *
     * @param CompanyInterface $company
     */
    private function initShippingMethodsForCompany(CompanyInterface $company): void
    {
        $companyShippingMethodSettings = $this->companyShippingMethodFactory->create()->load($company->getId());

        if ($companyShippingMethodSettings->getId()) {
            $companyExtension = $company->getExtensionAttributes();
            if ($companyExtension === null) {
                $companyExtension = $this->companyExtensionFactory->create();
            }
            $companyExtension->setApplicableShippingMethod(
                $companyShippingMethodSettings->getApplicableShippingMethod()
            );
            $companyExtension->setAvailableShippingMethods(
                $companyShippingMethodSettings->getAvailableShippingMethods()
            );
            $companyExtension->setUseConfigSettingsShipping($companyShippingMethodSettings->getUseConfigSettings());
            $company->setExtensionAttributes($companyExtension);
        }
    }
}
