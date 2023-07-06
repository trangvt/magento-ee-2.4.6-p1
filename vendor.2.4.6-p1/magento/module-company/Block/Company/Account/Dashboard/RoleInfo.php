<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Company\Block\Company\Account\Dashboard;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\RoleInterface;
use Magento\Company\Model\CompanyContext;

/**
 * Dashboard Customer Role Info
 *
 * @api
 */
class RoleInfo extends Template
{
    /**
     * @var AclInterface
     */
    private $companyAcl;

    /**
     * @var CompanyContext
     */
    private $companyContext;

    /**
     * RoleInfo constructor.
     * @param Context $context
     * @param AclInterface $companyAcl
     * @param CompanyContext $companyContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        AclInterface $companyAcl,
        CompanyContext $companyContext,
        array $data = []
    ) {
        $this->companyAcl = $companyAcl;
        $this->companyContext = $companyContext;
        parent::__construct($context, $data);
    }

    /**
     * Get role names for current customer
     *
     * @return string[]|null
     */
    public function getCustomerRoles()
    {
        $customerId = $this->companyContext->getCustomerId();
        return array_map(
            function (RoleInterface $role) {
                return $role->getRoleName();
            },
            $this->companyAcl->getRolesByUserId($customerId)
        );
    }
}
