<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Company\Api\AclInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\CompanyManagement;
use Magento\Company\Model\Email\Sender;
use Magento\Company\Model\ResourceModel\Customer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Magento\Company\Model\CompanyManagement class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyManagementTest extends TestCase
{
    /**
     * @var CompanyManagement
     */
    private $companyManagement;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var UserInterfaceFactory|MockObject
     */
    private $userFactory;

    /**
     * @var User|MockObject
     */
    private $user;

    /**
     * @var Company|MockObject
     */
    private $company;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Customer|MockObject
     */
    private $customerResource;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    private $companyAttributes;

    /**
     * @var \Magento\Customer\Model\Data\Customer|MockObject
     */
    private $customer;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Sender|MockObject
     */
    private $companyEmailSender;

    /**
     * @var AclInterface|MockObject
     */
    private $userRoleManagement;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customer = $this->getMockBuilder(\Magento\Customer\Model\Data\Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userRoleManagement = $this->getMockBuilder(AclInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerResource = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyEmailSender = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userFactory = $this->getMockBuilder(UserInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->companyManagement = $objectManagerHelper->getObject(
            CompanyManagement::class,
            [
                'companyRepository' => $this->companyRepository,
                'userFactory' => $this->userFactory,
                'customerRepository' => $this->customerRepository,
                'customerResource' => $this->customerResource,
                'companyEmailSender' => $this->companyEmailSender,
                'logger' => $this->logger,
                'userRoleManagement' => $this->userRoleManagement,
            ]
        );
    }

    /**
     * Test getSalesRepresentative method.
     *
     * @return void
     */
    public function testGetSalesRepresentative()
    {
        $userId = 1;
        $salesRepresentative = 'Firstname Lastname';
        $this->userFactory->expects($this->once())->method('create')->willReturn($this->user);
        $this->user->expects($this->once())->method('load')->willReturnSelf();
        $this->user->expects($this->once())->method('getLastname')->willReturn('Lastname');
        $this->user->expects($this->once())->method('getFirstname')->willReturn('Firstname');

        $this->assertEquals($salesRepresentative, $this->companyManagement->getSalesRepresentative($userId));
    }

    /**
     * Test getSalesRepresentative method without user id.
     *
     * @return void
     */
    public function testGetSalesRepresentativeWithoutUserId()
    {
        $this->assertEquals('', $this->companyManagement->getSalesRepresentative(null));
    }

    /**
     * Test getByCustomerId method.
     *
     * @return void
     */
    public function testGetByCustomerId()
    {
        $customerId = 1;
        $companyId = 2;
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customer);
        $this->customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customerExtension->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyAttributes);
        $this->companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->willReturn($this->company);

        $this->assertEquals($this->company, $this->companyManagement->getByCustomerId($customerId));
    }

    /**
     * Test getByCustomerId method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetByCustomerIdWithNoSuchEntityException()
    {
        $customerId = 1;
        $companyId = 2;
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $exception = new NoSuchEntityException(__('No such entity.'));
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customer);
        $this->customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customerExtension->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyAttributes);
        $this->companyAttributes->expects($this->atLeastOnce())->method('getCompanyId')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->willThrowException($exception);

        $this->assertNull($this->companyManagement->getByCustomerId($customerId));
    }

    /**
     * Test getAdminByCompanyId method.
     *
     * @return void
     */
    public function testGetAdminByCompanyId()
    {
        $companyId = 1;
        $companySuperUserId = 1;
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($this->company);
        $this->company->expects($this->once())->method('getSuperUserId')->willReturn($companySuperUserId);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($companySuperUserId)
            ->willReturn($this->customer);
        $this->assertEquals($this->customer, $this->companyManagement->getAdminByCompanyId($companyId));
    }

    /**
     * Test getAdminByCompanyId method with exception.
     *
     * @return void
     */
    public function testGetAdminByCompanyIdWithException()
    {
        $companyId = 1;
        $exception = new LocalizedException(__('Exception message'));
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturnSelf();

        $this->assertNull($this->companyManagement->getAdminByCompanyId($companyId));
    }

    /**
     * Test for assignCustomer() method if customer is super user.
     *
     * @return void
     */
    public function testAssignCustomerIfCustomerSuperUser()
    {
        $companyId = 1;
        $customerId = 2;

        $this->prepareMocksForAssignCustomerTest($companyId, $customerId, $customerId);
        $this->userRoleManagement->expects($this->never())
            ->method('assignUserDefaultRole');
        $this->companyEmailSender->expects($this->never())
            ->method('sendCustomerCompanyAssignNotificationEmail');

        $this->assertNull($this->companyManagement->assignCustomer($companyId, $customerId));
    }

    /**
     * Prepare mocks for assignCustomer() method test.
     *
     * @param int $companyId
     * @param int $customerId
     * @param int $superUserId
     *
     * @return void
     */
    private function prepareMocksForAssignCustomerTest($companyId, $customerId, $superUserId)
    {
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($this->customer);
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $this->customer->expects($this->atLeastOnce())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customerExtension->expects($this->atLeastOnce())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyAttributes);
        $this->companyAttributes->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $this->companyAttributes->expects($this->once())->method('setCompanyId')->with($companyId)->willReturnSelf();
        $this->customerResource->expects($this->once())
            ->method('saveAdvancedCustomAttributes')
            ->with($this->companyAttributes)
            ->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($this->company);
        $this->customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->company->expects($this->once())->method('getSuperUserId')->willReturn($superUserId);
    }

    /**
     * Test for assignCustomer() method if customer is not super user.
     *
     * @return void
     */
    public function testAssignCustomerIfCustomerNotSuperUser()
    {
        $companyId = 1;
        $customerId = 2;
        $superUserId = 3;

        $this->prepareMocksForAssignCustomerTest($companyId, $customerId, $superUserId);
        $this->userRoleManagement->expects($this->once())
            ->method('assignUserDefaultRole')
            ->with($customerId, $companyId);
        $this->companyEmailSender->expects($this->once())
            ->method('sendCustomerCompanyAssignNotificationEmail')
            ->with($this->customer, $companyId)
            ->willReturnSelf();

        $this->assertNull($this->companyManagement->assignCustomer($companyId, $customerId));
    }
}
