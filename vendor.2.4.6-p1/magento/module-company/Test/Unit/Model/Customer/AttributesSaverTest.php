<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Customer;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Company\Model\Company;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\Customer\AttributesSaver;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Company/Model/Customer/AttributesSaver model.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AttributesSaverTest extends TestCase
{
    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var CompanyCustomerInterfaceFactory|MockObject
     */
    private $companyCustomerAttributesFactory;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var Sender|MockObject
     */
    private $companyEmailSender;

    /**
     * @var Structure|MockObject
     */
    private $companyStructure;

    /**
     * @var Company|MockObject
     */
    private $company;

    /**
     * @var AclInterface|MockObject
     */
    private $userRoleManagement;

    /**
     * @var CustomerInterface|MockObject
     */
    private $customer;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    private $companyAttributes;

    /**
     * @var AttributesSaver
     */
    private $attributesSaver;

    /**
     * Set up.
     *
     * @return void.
     */
    protected function setUp(): void
    {
        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveAdvancedCustomAttributes'])
            ->getMock();
        $this->companyCustomerAttributesFactory =
            $this->getMockBuilder(CompanyCustomerInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->setMethods(['create'])
                ->getMock();
        $this->companyManagement = $this->getMockBuilder(CompanyManagementInterface::class)
            ->setMethods(['getByCustomerId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyEmailSender = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveAttributes', 'sendUserStatusChangeNotificationEmail'])
            ->getMock();
        $this->userRoleManagement = $this->getMockBuilder(AclInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteRoles'])
            ->getMockForAbstractClass();
        $this->companyStructure = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->setMethods(['moveStructureChildrenToParent', 'removeCustomerNode', 'getStructureByCustomerId', 'addNode'])
            ->getMock();
        $this->company = $this->getMockBuilder(Company::class)
            ->setMethods(['getSuperUserId', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $this->companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId', 'setCustomerId', 'getStatus'])
            ->getMockForAbstractClass();
        $customerExtensionAttributes = $this
            ->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes', 'setCompanyAttributes'])
            ->getMockForAbstractClass();
        $this->customer->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $objectManagerHelper = new ObjectManager($this);
        $this->attributesSaver = $objectManagerHelper->getObject(AttributesSaver::class, [
            'customerResource' => $this->customerResource,
            'companyStructure' => $this->companyStructure,
            'companyManagement' => $this->companyManagement,
            'companyEmailSender' => $this->companyEmailSender,
            'userRoleManagement' => $this->userRoleManagement
        ]);
    }

    /**
     * Test for saveAttributes method.
     *
     * @param int|string $companyCustomerStatus
     * @dataProvider saveAttributesDataProvider
     * @return void
     */
    public function testSaveAttributes($companyCustomerStatus)
    {
        $adminId = 4;
        $companyId = 1;

        $this->companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn(1);
        $this->companyAttributes->expects($this->once())->method('setCustomerId');
        $this->companyAttributes
            ->expects($this->exactly(3))
            ->method('getStatus')
            ->willReturn($companyCustomerStatus);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->willReturn($this->company);

        $admin = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $admin->expects($this->once())->method('getId')->willReturn($adminId);

        $this->companyManagement->expects($this->atLeastOnce())
            ->method('getAdminByCompanyId')
            ->with($companyId)
            ->willReturn($admin);

        $this->company->expects($this->once())->method('getId')->willReturn(1);
        $this->company->expects($this->atLeastOnce())->method('getSuperUserId')->willReturn(25);
        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn(25);
        $this->companyEmailSender->expects($this->once())->method('sendUserStatusChangeNotificationEmail');
        $this->userRoleManagement->expects($this->once())->method('deleteRoles');
        $this->customerResource->expects($this->once())->method('saveAdvancedCustomAttributes');
        $this->companyStructure->expects($this->once())->method('moveStructureChildrenToParent')->willReturnSelf();
        $this->companyStructure->expects($this->once())->method('removeCustomerNode');

        $adminCompanyStructure = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();
        $adminCompanyStructure->expects($this->once())->method('getId')->willReturn(33);
        $this->companyStructure->expects($this->once())->method('getStructureByCustomerId')
            ->with($adminId)->willReturn($adminCompanyStructure);

        $this->attributesSaver->saveAttributes(
            $this->customer,
            $this->companyAttributes,
            $companyId,
            true,
            CompanyCustomerInterface::STATUS_INACTIVE
        );
    }

    /**
     * Data Provider for testSaveAttributes() method.
     *
     * @return array
     */
    public function saveAttributesDataProvider()
    {
        return [
            [CompanyCustomerInterface::STATUS_ACTIVE],
            ['1']
        ];
    }

    /**
     * Test for exception in saveAttributes method.
     *
     * @return void
     */
    public function testSaveAttributesWithException()
    {
        $this->expectException('Magento\Framework\Exception\CouldNotSaveException');
        $this->companyAttributes->expects($this->once())->method('getCompanyId')->willReturn(1);
        $this->companyAttributes
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn(CompanyCustomerInterface::STATUS_INACTIVE);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->willReturn($this->company);
        $this->company->expects($this->once())->method('getId')->willReturn(1);
        $this->company->expects($this->atLeastOnce())->method('getSuperUserId')->willReturn(25);
        $this->customer->expects($this->exactly(3))->method('getId')->willReturn(25);
        $this->attributesSaver->saveAttributes(
            $this->customer,
            $this->companyAttributes,
            1,
            true,
            1
        );
    }
}
