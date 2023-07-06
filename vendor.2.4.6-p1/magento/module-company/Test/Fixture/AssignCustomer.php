<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Fixture;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\TestFramework\Fixture\DataFixtureInterface;

/**
 * Creating a new company
 */
class AssignCustomer implements DataFixtureInterface
{
    private const COMPANY_ID = 'company_id';
    private const CUSTOMER_ID = 'customer_id';

    /**
     * @var CompanyManagementInterface
     */
    private CompanyManagementInterface $companyManagement;

    /**
     * @var Structure
     */
    private Structure $structureManager;

    /**
     * @param CompanyManagementInterface $companyManagement
     * @param Structure $structureManager
     */
    public function __construct(
        CompanyManagementInterface $companyManagement,
        Structure $structureManager
    ) {
        $this->companyManagement = $companyManagement;
        $this->structureManager = $structureManager;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        if (empty($data[self::COMPANY_ID])) {
            throw new InvalidArgumentException(__('"%field" is required', ['field' => self::COMPANY_ID]));
        }

        if (empty($data[self::CUSTOMER_ID])) {
            throw new InvalidArgumentException(__('"%field" is required', ['field' => self::CUSTOMER_ID]));
        }

        $this->companyManagement->assignCustomer($data[self::COMPANY_ID], $data[self::CUSTOMER_ID]);

        $this->structureManager->addNode(
            $data[self::CUSTOMER_ID],
            StructureInterface::TYPE_CUSTOMER,
            $this->structureManager->getStructureByCustomerId(
                $this->companyManagement->getAdminByCompanyId($data[self::COMPANY_ID])->getId()
            )->getId()
        );
        return null;
    }
}
