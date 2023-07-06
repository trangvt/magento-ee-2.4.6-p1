<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\SaveValidator;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\SaveValidator\CompanyAdmin;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for company admin validator.
 */
class CompanyAdminTest extends TestCase
{
    /**
     * @var CompanyInterface|MockObject
     */
    private $company;

    /**
     * @var InputException|MockObject
     */
    private $exception;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyAdmin
     */
    private $companyAdmin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->exception = $this->getMockBuilder(InputException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->companyAdmin = $objectManager->getObject(
            CompanyAdmin::class,
            [
                'company' => $this->company,
                'exception' => $this->exception,
                'customerRepository' => $this->customerRepository,
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
        $superUserId = 1;
        $companyId = 2;
        $this->company->expects($this->atLeastOnce())->method('getSuperUserId')->willReturn($superUserId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository->expects($this->once())->method('getById')->with($superUserId)->willReturn($customer);
        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->setMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $companyAttributes = $this
            ->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getStatus')
            ->willReturn(CompanyCustomerInterface::STATUS_ACTIVE);
        $companyAttributes->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->exception->expects($this->never())->method('addError');
        $this->companyAdmin->execute();
    }

    /**
     * Test for execute method with errors.
     *
     * @return void
     */
    public function testExecuteWithErrors()
    {
        $superUserId = 1;
        $companyId = 2;
        $this->company->expects($this->atLeastOnce())->method('getSuperUserId')->willReturn($superUserId);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository->expects($this->once())->method('getById')->with($superUserId)->willReturn($customer);
        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->setMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $companyAttributes = $this
            ->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getStatus')
            ->willReturn(CompanyCustomerInterface::STATUS_INACTIVE);
        $companyAttributes->expects($this->once())->method('getCompanyId')->willReturn(3);
        $this->company->expects($this->once())->method('getId')->willReturn($companyId);
        $this->exception->expects($this->exactly(2))->method('addError')->withConsecutive(
            [__('The selected user is inactive. To continue, select another user or activate the current user.')],
            [__('This customer is a user of a different company. Enter a different email address to continue.')]
        )->willReturnSelf();
        $this->companyAdmin->execute();
    }
}
