<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Model\Customer;

use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Model\Customer\Permission;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagement;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var Permission
     */
    private $permission;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyManagement = $this->createMock(
            CompanyManagementInterface::class
        );
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->authorization = $this->createMock(
            AuthorizationInterface::class
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->permission = $objectManagerHelper->getObject(
            Permission::class,
            [
                'companyManagement' => $this->companyManagement,
                'customerRepository' => $this->customerRepository,
                'authorization' => $this->authorization,
            ]
        );
    }

    /**
     * Test isCheckoutAllowed method.
     *
     * @param int $status
     * @param $isNegotiableQuoteActive
     * @param string $resource
     * @param bool $isAllowed
     * @param $counter
     * @param bool $expectedResult
     * @dataProvider isCheckoutAllowedDataProvider
     */
    public function testIsCheckoutAllowed(
        $status,
        $isNegotiableQuoteActive,
        $resource,
        $isAllowed,
        $counter,
        $expectedResult
    ) {
        $customerId = 1;
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $company = $this->createMock(
            CompanyInterface::class
        );
        $customer->expects($this->atLeastOnce())->method('getId')->willReturn($customerId);
        $this->companyManagement->expects($this->atLeastOnce())->method('getByCustomerId')->willReturn($company);
        $company->expects($this->once())->method('getStatus')->willReturn($status);
        $this->authorization->expects($counter)
            ->method('isAllowed')
            ->with($resource)
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->permission->isCheckoutAllowed($customer, $isNegotiableQuoteActive));
    }

    /**
     * Data provider for isCheckoutAllowed method.
     *
     * @return array
     */
    public function isCheckoutAllowedDataProvider()
    {
        return [
            [CompanyInterface::STATUS_BLOCKED, true, 'Magento_NegotiableQuote::checkout', false, $this->never(), false],
            [CompanyInterface::STATUS_APPROVED, false, 'Magento_Sales::place_order', false, $this->once(), false],
            [CompanyInterface::STATUS_APPROVED, true, 'Magento_NegotiableQuote::checkout', true, $this->once(), true],
        ];
    }

    /**
     * Data provider for isCompanyLocked method.
     *
     * @return array
     */
    public function isCompanyLockedDataProvider()
    {
        return [
            [CompanyInterface::STATUS_REJECTED, true],
            [CompanyInterface::STATUS_PENDING, true],
            [CompanyInterface::STATUS_APPROVED, false],
        ];
    }

    /**
     * Test isLoginAllowed method.
     *
     * @param int $status
     * @param bool $expectedResult
     * @return void
     * @dataProvider isLoginAllowedDataProvider
     */
    public function testIsLoginAllowed($status, $expectedResult)
    {
        $customerId = 1;
        $customer = $this->createMock(
            CustomerInterface::class
        );
        $company = $this->createMock(
            CompanyInterface::class
        );
        $extensionAttributes = $this->getMockForAbstractClass(
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
        $customer->expects($this->once())->method('getId')->willReturn($customerId);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->willReturn($company);
        $company->expects($this->once())->method('getStatus')->willReturn(CompanyInterface::STATUS_APPROVED);
        $customer->expects($this->exactly(3))->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $extensionAttributes->expects($this->exactly(2))
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getStatus')->willReturn($status);

        $this->assertEquals($expectedResult, $this->permission->isLoginAllowed($customer));
    }

    /**
     * Data provider for isLoginAllowed method.
     *
     * @return array
     */
    public function isLoginAllowedDataProvider()
    {
        return [
            [CompanyCustomerInterface::STATUS_INACTIVE, false],
            [CompanyCustomerInterface::STATUS_ACTIVE, true],
        ];
    }
}
