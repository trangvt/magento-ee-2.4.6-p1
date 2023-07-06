<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CompanyPayment\Plugin\Company;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyExtensionFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyPayment\Model\CompanyPaymentMethodFactory;
use Magento\CompanyPayment\Model\ResourceModel\CompanyPaymentMethod;

/**
 * Plugin for adding company extension attributes.
 */
class CompanyRepositoryPlugin
{
    /**
     * @var CompanyPaymentMethod
     */
    private $companyPaymentMethodResource;

    /**
     * @var CompanyPaymentMethodFactory
     */
    private $companyPaymentMethodFactory;

    /**
     * @var CompanyExtensionFactory
     */
    private $companyExtensionFactory;

    /**
     * CompanyPaymentMethods constructor.
     *
     * @param CompanyPaymentMethod $companyPaymentMethodResource
     * @param CompanyPaymentMethodFactory $companyPaymentMethodFactory
     * @param CompanyExtensionFactory $companyExtensionFactory
     */
    public function __construct(
        CompanyPaymentMethod $companyPaymentMethodResource,
        CompanyPaymentMethodFactory $companyPaymentMethodFactory,
        CompanyExtensionFactory $companyExtensionFactory
    ) {
        $this->companyPaymentMethodResource = $companyPaymentMethodResource;
        $this->companyPaymentMethodFactory = $companyPaymentMethodFactory;
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
        $this->getPaymentMethodsForCompany($company);
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
        $this->getPaymentMethodsForCompany($company);
        return $company;
    }

    /**
     * Get available and applicable payment methods for the company
     *
     * @param CompanyInterface $company
     */
    private function getPaymentMethodsForCompany(CompanyInterface $company): void
    {
        $availablePaymentMethod = $this->companyPaymentMethodFactory->create()->load($company->getId());

        if ($availablePaymentMethod->getId()) {
            $companyExtension = $company->getExtensionAttributes();
            if ($companyExtension === null) {
                $companyExtension = $this->companyExtensionFactory->create();
            }
            $companyExtension->setApplicablePaymentMethod($availablePaymentMethod->getApplicablePaymentMethod());
            $companyExtension->setAvailablePaymentMethods($availablePaymentMethod->getAvailablePaymentMethods());
            $companyExtension->setUseConfigSettings($availablePaymentMethod->getUseConfigSettings());
            $company->setExtensionAttributes($companyExtension);
        }
    }
}
