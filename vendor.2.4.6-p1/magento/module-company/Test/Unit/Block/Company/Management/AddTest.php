<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Company\Management;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\RoleManagementInterface;
use Magento\Company\Block\Company\Management\Add;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var RoleManagementInterface|MockObject
     */
    private $roleManagement;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Add
     */
    private $add;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->roleManagement = $this->getMockForAbstractClass(RoleManagementInterface::class);
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);

        $objectManager = new ObjectManager($this);
        $this->add = $objectManager->getObject(
            Add::class,
            [
                'userContext' => $this->userContext,
                'roleManagement' => $this->roleManagement,
                'customerRepository' => $this->customerRepository,
                'data' => []
            ]
        );
    }

    /**
     * Test for getRoles method.
     *
     * @return void
     */
    public function testGetRoles()
    {
        $customerId = 1;
        $companyId = 2;
        $expectedResult = ['roles'];
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->with($customerId)->willReturn($customer);
        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $companyAttributes = $this->getMockForAbstractClass(CompanyCustomerInterface::class);
        $customer->expects($this->once())->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->once())
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $this->roleManagement->expects($this->once())
            ->method('getRolesByCompanyId')->with($companyId)->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->add->getRoles());
    }
}
