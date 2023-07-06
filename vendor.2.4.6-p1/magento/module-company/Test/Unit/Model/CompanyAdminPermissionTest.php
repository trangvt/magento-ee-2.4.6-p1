<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Model\Company;
use Magento\Company\Model\CompanyAdminPermission;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test CompanyAdminPermission.
 */
class CompanyAdminPermissionTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var CompanyAdminPermission
     */
    private $companyAdminPermission;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->userContext = $this->getMockForAbstractClass(
            UserContextInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getUserId']
        );
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->companyAdminPermission = $objectManager->getObject(
            CompanyAdminPermission::class,
            [
                'customerContext' => $this->userContext,
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository
            ]
        );
    }

    /**
     * Test isCurrentUserCompanyAdmin method.
     *
     * @param int $isCurrentUserCompanyAdmin
     * @param int $customerId
     * @return void
     * @dataProvider isCurrentUserCompanyAdminDataProvider
     */
    public function testIsCurrentUserCompanyAdmin($isCurrentUserCompanyAdmin, $customerId)
    {
        $userId = 1;
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->once())->method('getId')->willReturn($customerId);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->prepareIsUserCompanyAdminMock($customer);
        $company = $this->getMockBuilder(Company::class)
            ->setMethods(['getSuperUserId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository->expects($this->once())->method('get')->willReturn($company);
        $company->expects($this->once())->method('getSuperUserId')->willReturn($userId);
        $this->assertEquals($isCurrentUserCompanyAdmin, $this->companyAdminPermission->isCurrentUserCompanyAdmin());
    }

    /**
     * Test for isCurrentUserCompanyAdmin method when company repository returns NoSuchEntityException.
     *
     * @return void
     */
    public function testIsCurrentUserCompanyAdminWithNoSuchEntityException()
    {
        $userId = 1;
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->userContext->expects($this->once())->method('getUserId')->willReturn($userId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->prepareIsUserCompanyAdminMock($customer);
        $exception = new NoSuchEntityException(__('Exception message'));
        $this->companyRepository->expects($this->once())->method('get')->willThrowException($exception);
        $this->assertFalse($this->companyAdminPermission->isCurrentUserCompanyAdmin());
    }

    /**
     * Data provider for isCurrentUserCompanyAdmin method.
     *
     * @return array
     */
    public function isCurrentUserCompanyAdminDataProvider()
    {
        return [
            [1, 1],
            [0, 2]
        ];
    }

    /**
     * Test isGivenUserCompanyAdmin method.
     *
     * @param int $isCurrentUserCompanyAdmin
     * @param int $customerId
     * @return void
     * @dataProvider isGivenUserCompanyAdminDataProvider
     */
    public function testIsGivenUserCompanyAdmin($isCurrentUserCompanyAdmin, $customerId)
    {
        $userId = 1;
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $customer->expects($this->once())->method('getId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->willReturn($customer);
        $this->prepareIsUserCompanyAdminMock($customer);
        $company = $this->getMockBuilder(Company::class)
            ->setMethods(['getSuperUserId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepository->expects($this->once())->method('get')->willReturn($company);
        $company->expects($this->once())->method('getSuperUserId')->willReturn($userId);

        $this->assertEquals(
            $isCurrentUserCompanyAdmin,
            $this->companyAdminPermission->isGivenUserCompanyAdmin($userId)
        );
    }

    /**
     * Data provider for isCurrentUserCompanyAdmin method.
     *
     * @return array
     */
    public function isGivenUserCompanyAdminDataProvider()
    {
        return [
            [1, 1],
            [0, 2]
        ];
    }

    /**
     * Mock for isUserCompanyAdmin method.
     *
     * @param MockObject $customer
     * @return MockObject
     */
    private function prepareIsUserCompanyAdminMock(MockObject $customer)
    {
        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $customer->expects($this->exactly(3))
            ->method('getExtensionAttributes')
            ->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->exactly(2))->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        return $companyAttributes;
    }
}
