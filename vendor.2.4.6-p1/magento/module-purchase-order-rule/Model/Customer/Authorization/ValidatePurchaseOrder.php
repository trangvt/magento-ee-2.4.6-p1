<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model\Customer\Authorization;

use Magento\Company\Model\CompanyAdminPermission;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\CompanyManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrder\Api\Data\PurchaseOrderInterface;
use Magento\PurchaseOrder\Model\Customer\Authorization\ActionInterface;

/**
 * Validate Purchase Order authorization class.
 */
class ValidatePurchaseOrder implements ActionInterface
{
    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * @param CompanyManagement $companyManagement
     * @param CompanyAdminPermission $companyAdminPermission
     * @param CompanyContext $companyContext
     */
    public function __construct(
        CompanyManagement $companyManagement,
        CompanyAdminPermission $companyAdminPermission,
        CompanyContext $companyContext
    ) {
        $this->companyAdminPermission = $companyAdminPermission;
        $this->companyManagement = $companyManagement;
        $this->companyContext = $companyContext;
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(PurchaseOrderInterface $purchaseOrder) : bool
    {
        try {
            $purchaseOrderCompanyId = (int)$purchaseOrder->getCompanyId();
            return $this->companyContext->isCurrentUserCompanyUser()
                && $purchaseOrderCompanyId === $this->getCurrentCustomerCompanyId()
                && $this->companyAdminPermission->isCurrentUserCompanyAdmin();
        } catch (NoSuchEntityException $exception) {
            return false;
        } catch (LocalizedException $exception) {
            return false;
        }
    }

    /**
     * Get Company Id for current customer
     *
     * @return int
     */
    private function getCurrentCustomerCompanyId(): int
    {
        return (int)$this->companyManagement->getByCustomerId(
            $this->companyContext->getCustomerId()
        )->getId();
    }
}
