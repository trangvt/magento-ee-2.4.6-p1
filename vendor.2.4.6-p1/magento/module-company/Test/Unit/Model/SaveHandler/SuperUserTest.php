<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveHandler;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\CompanySuperUserSave;
use Magento\Company\Model\SaveHandler\SuperUser;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for SuperUser.
 */
class SuperUserTest extends TestCase
{
    const SUPERUSER_TEST_USER_ID = 1;
    const SUPERUSER_TEST_COMPANY_ID = 8;

    /**
     * @var CompanySuperUserSave|MockObject
     */
    private $companySuperUser;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var CompanyInterface|MockObject
     */
    private $initialCompany;

    /**
     * @var CompanyCustomerInterfaceFactory|MockObject
     */
    private $companyCustomerFactory;

    /**
     * @var CustomerInterface|MockObject
     */
    private $admin;

    /**
     * @var CustomerExtensionInterface|MockObject
     */
    private $extensionAttributes;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    private $companyAttributes;

    /**
     * @var SuperUser
     */
    private $object;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'save'])
            ->getMockForAbstractClass();
        $this->companySuperUser = $this->getMockBuilder(CompanySuperUserSave::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveCustomer'])
            ->getMock();
        $this->companyCustomerFactory = $this->getMockBuilder(CompanyCustomerInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->extensionAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'setCompanyAttributes'])
            ->getMockForAbstractClass();
        $this->companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyId'])
            ->getMockForAbstractClass();
        $this->admin = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes'])
            ->getMockForAbstractClass();
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSuperUserId', 'getId', 'getStatus'])
            ->getMockForAbstractClass();
        $this->initialCompany = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSuperUserId'])
            ->getMockForAbstractClass();
        $this->company->expects($this->exactly(1))->method('getId')
            ->willReturn(self::SUPERUSER_TEST_COMPANY_ID);
        $this->company->expects($this->atLeastOnce())->method('getStatus')
            ->willReturn(CompanyInterface::STATUS_APPROVED);
        $this->company->expects($this->exactly(2))->method('getSuperUserId')
            ->willReturn(self::SUPERUSER_TEST_USER_ID);
        $this->initialCompany->expects($this->exactly(3))->method('getSuperUserId')
            ->willReturn(33);
        $this->customerRepository->expects($this->atLeastOnce())
            ->method('getById')->withConsecutive([self::SUPERUSER_TEST_USER_ID], [33])
            ->willReturnOnConsecutiveCalls($this->admin, null);
        $this->companySuperUser->expects($this->once())->method('saveCustomer')->with($this->admin);

        $objectManagerHelper = new ObjectManager($this);
        $this->object = $objectManagerHelper->getObject(
            SuperUser::class,
            [
                'customerRepository' => $this->customerRepository,
                'companySuperUser' => $this->companySuperUser,
                'companyCustomerAttributes' => $this->companyCustomerFactory
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->admin->expects($this->exactly(2))->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->exactly(2))->method('getCompanyAttributes')
            ->willReturn($this->companyAttributes);
        $this->companyAttributes->expects($this->once())->method('setCompanyId');

        $this->object->execute($this->company, $this->initialCompany);
    }

    /**
     * Test for execute method when company attributes absent.
     *
     * @return void
     */
    public function testExecuteWithAbsentCompanyAttributes()
    {
        $this->admin->expects($this->exactly(3))->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->exactly(2))->method('getCompanyAttributes')
            ->willReturnOnConsecutiveCalls(
                null,
                $this->companyAttributes
            );
        $this->companyCustomerFactory->expects($this->once())->method('create')->willReturn($this->companyAttributes);
        $this->extensionAttributes->expects($this->once())->method('setCompanyAttributes')
            ->with($this->companyAttributes);
        $this->companyAttributes->expects($this->once())->method('setCompanyId');

        $this->object->execute($this->company, $this->initialCompany);
    }
}
