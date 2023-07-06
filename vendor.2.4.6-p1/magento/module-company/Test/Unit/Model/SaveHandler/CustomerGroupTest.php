<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveHandler;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Company\Model\SaveHandler\CustomerGroup;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\SaveHandler\CustomerGroup class.
 */
class CustomerGroupTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var CustomerGroup
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            CustomerGroup::class,
            [
                'customerRepository' => $this->customerRepository,
                'customerResource' => $this->customerResource,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $companyId = 1;
        $customerId = 100;
        $companyGroupId = 10;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $initialCompany = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $initialCompany->expects($this->once())->method('getId')->willReturn(1);
        $company->expects($this->atLeastOnce())->method('getCustomerGroupId')->willReturn($companyGroupId);
        $initialCompany->expects($this->once())->method('getCustomerGroupId')->willReturn(11);
        $company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->customerResource->expects($this->once())
            ->method('getCustomerIdsByCompanyId')->with($companyId)->willReturn([$customerId]);
        $this->customerRepository->expects($this->once())
            ->method('getById')->with($customerId)->willReturn($customer);
        $customer->expects($this->once())->method('setGroupId')->with($companyGroupId)->willReturnSelf();
        $this->customerRepository->expects($this->once())->method('save')->with($customer)->willReturn($customer);
        $this->model->execute($company, $initialCompany);
    }
}
