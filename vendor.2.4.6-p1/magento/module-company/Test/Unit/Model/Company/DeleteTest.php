<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Company;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\StructureInterface;
use Magento\Company\Api\TeamRepositoryInterface;
use Magento\Company\Model\Company\Delete;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\ResourceModel\Company;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Company\Model\StructureRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\Company\Delete class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends TestCase
{
    /**
     * @var Company|MockObject
     */
    private $companyResource;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var TeamRepositoryInterface|MockObject
     */
    private $teamRepository;

    /**
     * @var StructureRepository|MockObject
     */
    private $structureRepository;

    /**
     * @var Delete
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyResource = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->structureManager = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->teamRepository = $this->getMockBuilder(TeamRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->structureRepository = $this->getMockBuilder(StructureRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Delete::class,
            [
                'companyResource' => $this->companyResource,
                'customerRepository' => $this->customerRepository,
                'customerResource' => $this->customerResource,
                'structureManager' => $this->structureManager,
                'teamRepository' => $this->teamRepository,
                'structureRepository' => $this->structureRepository,
            ]
        );
    }

    /**
     * Test delete method.
     *
     * @return void
     */
    public function testDelete()
    {
        $superUserId = 1;
        $allowedIds = ['users' => [2]];
        $company = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company->expects($this->atLeastOnce())->method('getSuperUserId')->willReturn($superUserId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($superUserId)
            ->willReturn($allowedIds);
        $team = $this->getMockBuilder(StructureInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->structureManager->expects($this->once())
            ->method('getUserChildTeams')
            ->willReturn([$team]);

        $this->structureManager->expects($this->once())
            ->method('removeCustomerNode')
            ->with(2);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with(2)
            ->willReturn($customer);
        $customer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())
            ->method('setCompanyId')
            ->with(0)
            ->willReturnSelf();
        $companyAttributes->expects($this->once())
            ->method('setStatus')
            ->with(CompanyCustomerInterface::STATUS_INACTIVE)
            ->willReturnSelf();

        $this->customerRepository->expects($this->once())
            ->method('save')
            ->with($customer)
            ->willReturn($customer);
        $this->companyResource->expects($this->once())
            ->method('delete')
            ->with($company)
            ->willReturnSelf();

        $this->model->delete($company);
    }
}
