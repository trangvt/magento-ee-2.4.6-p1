<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Action\Company;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\Action\Company\ReplaceSuperUser;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for model \Magento\Company\Model\Action\Company\ReplaceSuperUser.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReplaceSuperUserTest extends TestCase
{

    /**
     * @var ReplaceSuperUser
     */
    private $replaceSuperUser;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var Customer|MockObject
     */
    private $customerResourceMock;

    /**
     * @var Structure|MockObject
     */
    private $companyStructureMock;

    /**
     * @var CompanyAttributes|MockObject
     */
    private $companyAttributesMock;

    /**
     * @var AclInterface|MockObject
     */
    private $userRoleManagementMock;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var CustomerInterface|MockObject
     */
    private $oldCustomer;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customerResourceMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyStructureMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyAttributesMock = $this->getMockBuilder(CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userRoleManagementMock = $this->getMockBuilder(AclInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->replaceSuperUser = $objectManager->getObject(
            ReplaceSuperUser::class,
            [
                'companyAttributes'     => $this->companyAttributesMock,
                'companyStructure'      => $this->companyStructureMock,
                'customerRepository'    => $this->customerRepositoryMock,
                'customerResource'      => $this->customerResourceMock,
                'userRoleManagement'    => $this->userRoleManagementMock,
            ]
        );
    }

    /**
     * Test for method \Magento\Company\Model\Action\Company\ReplaceSuperUser::execute
     *
     * @return void
     */
    public function testExecute()
    {
        $customerId = 17;
        $oldSuperUserId = 18;
        $companyId = 33;
        $keepActive = false;

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);

        $this->oldCustomer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryMock->expects($this->atLeastOnce())
            ->method('getById')->with($oldSuperUserId)->willReturn($this->oldCustomer);

        $customerAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyAttributesMock->expects($this->atLeastOnce())
            ->method('getCompanyAttributesByCustomer')->willReturn($customerAttributes);

        $customerAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $oldCustomerAttributes = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->setMethods(['getCompanyAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $oldCompanyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->oldCustomer->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($oldCustomerAttributes);
        $oldCustomerAttributes->expects($this->once())
            ->method('getCompanyAttributes')->willReturn($oldCompanyAttributes);

        $oldCompanyAttributes->method('setStatus')->with(CompanyCustomerInterface::STATUS_INACTIVE)->willReturnSelf();
        $this->customerResourceMock->expects($this->once())
            ->method('saveAdvancedCustomAttributes')->with($oldCompanyAttributes)->willReturnSelf();
        $this->userRoleManagementMock->expects($this->once())
            ->method('assignUserDefaultRole')->with($oldSuperUserId, $companyId);
        $this->companyStructureMock->expects($this->once())
            ->method('moveStructureChildrenToParent')->with($customerId)->willReturnSelf();
        $this->companyStructureMock->expects($this->once())
            ->method('removeCustomerNode')->with($customerId)->willReturnSelf();
        $this->companyStructureMock->expects($this->once())->method('moveCustomerStructure')
            ->with($oldSuperUserId, $customerId, $keepActive)->willReturnSelf();

        $this->oldCustomer->expects($this->atLeastOnce())->method('getId')->willReturn($oldSuperUserId);

        $this->replaceSuperUser->execute($this->customer, $oldSuperUserId, $keepActive);
    }
}
