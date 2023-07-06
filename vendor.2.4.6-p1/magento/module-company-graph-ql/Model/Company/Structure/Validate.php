<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyGraphQl\Model\Company\Structure;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Company\Structure as CompanyStructure;
use Magento\CompanyGraphQl\Model\Company\StructureFactory;
use Magento\CompanyGraphQl\Model\Company\Users\Customer;
use Magento\Framework\Data\Tree\Node;

/**
 * Validate company structure
 */
class Validate
{
    /**
     * @var CompanyStructure
     */
    private $companyStructure;

    /**
     * @var StructureFactory
     */
    private $structureFactory;

    /**
     * @var Customer
     */
    private $customerUser;

    /**
     * @param CompanyStructure $companyStructure
     * @param Customer $customerUser
     * @param StructureFactory $structureFactory
     */
    public function __construct(
        CompanyStructure $companyStructure,
        Customer $customerUser,
        StructureFactory $structureFactory
    ) {
        $this->companyStructure = $companyStructure;
        $this->structureFactory = $structureFactory;
        $this->customerUser = $customerUser;
    }

    /**
     * Validate structure based on company
     *
     * @param Node $tree
     * @param CompanyInterface $company
     * @return bool
     */
    public function validateStructureRootId(Node $tree, CompanyInterface $company):bool
    {
        $userCompanyAttributes = null;

        switch ((int)$tree->getEntityType()) {
            case StructureInterface::TYPE_TEAM:
                if ($teamStructure = $this->companyStructure->getStructureByTeamId($tree->getEntityId())) {
                    $parentCustomerStructure = $this->structureFactory->create()
                        ->getTeamParentCustomerStructure($teamStructure);
                    $customer = $this->customerUser->getCustomerById((int)$parentCustomerStructure->getEntityId());
                    $userCompanyAttributes = $this->customerUser->getCustomerCompanyAttributes($customer);
                }
                break;
            case StructureInterface::TYPE_CUSTOMER:
                $customer = $this->customerUser->getCustomerById((int)$tree->getEntityId());
                $userCompanyAttributes = $this->customerUser->getCustomerCompanyAttributes($customer);
                break;
        }

        return !($userCompanyAttributes !== null
            && (int)$userCompanyAttributes->getCompanyId() !== (int)$company->getId());
    }
}
