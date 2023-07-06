<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\CompanyUser;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyUserTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CompanyUser
     */
    private $companyUser;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->companyUser = $objectManager->getObject(
            CompanyUser::class,
            [
                'userContext' => $this->userContext,
                'customerRepository' => $this->customerRepository,
            ]
        );
    }

    /**
     * Test getCurrentCompanyId method.
     *
     * @return void
     */
    public function testGetCurrentCompanyId()
    {
        $userId = 1;
        $companyId = 1;
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())
            ->method('getCompanyId')
            ->willReturn($companyId);

        $this->assertEquals($companyId, $this->companyUser->getCurrentCompanyId());
    }

    /**
     * Test getCurrentCompanyId method for customer without company attributes.
     *
     * @return void
     */
    public function testGetCurrentCompanyIdWithoutCompanyAttributes()
    {
        $userId = 1;
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')
            ->willReturn(null);

        $this->assertNull($this->companyUser->getCurrentCompanyId());
    }
}
