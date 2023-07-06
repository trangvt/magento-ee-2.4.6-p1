<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Customer;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Customer\Company;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Model\Customer\Company class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyTest extends TestCase
{
    /**
     * @var CompanyInterfaceFactory|MockObject
     */
    private $companyFactory;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var Structure|MockObject
     */
    private $companyStructure;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    private $customerAttributes;

    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var GroupManagementInterface|MockObject
     */
    private $groupManagement;

    /**
     * @var Company
     */
    private $customerCompany;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->companyFactory = $this->getMockBuilder(CompanyInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyStructure = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->groupManagement = $this->getMockBuilder(GroupManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->customerCompany = $objectManagerHelper->getObject(
            Company::class,
            [
                'companyFactory' => $this->companyFactory,
                'companyRepository' => $this->companyRepository,
                'companyStructure' => $this->companyStructure,
                'customerAttributes' => $this->customerAttributes,
                'customerResource' => $this->customerResource,
                'groupManagement' => $this->groupManagement,
            ]
        );
    }

    /**
     * Test for createCompany method.
     *
     * @return void
     */
    public function testCreateCompany()
    {
        $customerId = 666;
        $companyId = 555;
        $jobTitle = 'job title';
        $company = ['name' => 'company 1'];

        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $companyDataObject = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyDataObject->expects($this->once())->method('setSuperUserId')->with($customerId);
        $companyDataObject->expects($this->once())->method('getId')->willReturn($companyId);
        $this->companyFactory->expects($this->once())
            ->method('create')->with(['data' => $company])->willReturn($companyDataObject);
        $this->companyRepository->expects($this->once())
            ->method('save')->with($companyDataObject)->willReturn($companyDataObject);
        $this->customerAttributes->expects($this->once())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $this->customerAttributes->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $this->customerAttributes->expects($this->once())->method('setJobTitle')->with($jobTitle)->willReturnSelf();
        $this->customerResource->expects($this->once())
            ->method('saveAdvancedCustomAttributes')->with($this->customerAttributes);
        $group = $this->getMockBuilder(GroupInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $group->expects($this->once())->method('getId')->willReturn(1);
        $this->groupManagement->expects($this->once())->method('getDefaultGroup')->willReturn($group);

        $this->assertSame($companyDataObject, $this->customerCompany->createCompany($customer, $company, $jobTitle));
    }
}
