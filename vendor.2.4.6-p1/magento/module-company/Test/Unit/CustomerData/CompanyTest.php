<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\CustomerData;

use Magento\Company\CustomerData\Company;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Model\Customer\PermissionInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for \Magento\Company\CustomerData\Company model.
 */
class CompanyTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var PermissionInterface|MockObject
     */
    private $permission;

    /**
     * @var Company
     */
    private $customerData;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->companyContext = $this->createMock(CompanyContext::class);
        $this->permission  = $this
            ->getMockBuilder(PermissionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCheckoutAllowed', 'isLoginAllowed', 'isCompanyLocked'])
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->customerData = $objectManager->getObject(
            Company::class,
            [
                'customerRepository' => $this->customerRepository,
                'companyContext' => $this->companyContext,
                'permission' => $this->permission
            ]
        );
    }

    /**
     * Test getSectionData.
     *
     * @param CustomerInterface|null $customer
     * @param bool $isCheckoutAllowed
     * @param bool $isLoginAllowed
     * @param array $expectedResult
     * @param $counter
     * @return void
     * @dataProvider dataProviderGetSectionData
     */
    public function testGetSectionData(
        $customer,
        $isCheckoutAllowed,
        $isLoginAllowed,
        array $expectedResult,
        $counter
    ) {
        $customerId = 1;

        $this->companyContext->expects($this->atLeastOnce())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->with($customerId)->willReturn($customer);
        if ($customer) {
            $this->permission->expects($this->once())
                ->method('isCheckoutAllowed')
                ->with($customer)
                ->willReturn($isCheckoutAllowed);
            $this->companyContext->expects($this->once())->method('isStorefrontRegistrationAllowed')->willReturn(false);
            $this->permission->expects($this->once())
                ->method('isLoginAllowed')
                ->with($customer)
                ->willReturn($isLoginAllowed);
        }
        $this->permission->expects($counter)->method('isCompanyBlocked')->willReturn(false);

        $this->assertEquals($expectedResult, $this->customerData->getSectionData());
    }

    /**
     * Data provider getSectionData.
     *
     * @return array
     */
    public function dataProviderGetSectionData()
    {
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        return [
            [
                $customer,
                true,
                true,
                [
                    'is_checkout_allowed' => true,
                    'is_login_allowed' => true,
                    'is_enabled' => false,
                    'is_company_blocked' => false,
                    'has_customer_company' => false,
                    'is_storefront_registration_allowed' => false,
                    'is_company_admin' => false
                ],
                $this->once()
            ],
            [
                $customer,
                false,
                false,
                [
                    'is_checkout_allowed' => false,
                    'is_login_allowed' => false,
                    'is_enabled' => false,
                    'is_company_blocked' => false,
                    'has_customer_company' => false,
                    'is_storefront_registration_allowed' => false,
                    'is_company_admin' => false
                ],
                $this->once()
            ],
            [
                $customer,
                true,
                false,
                [
                    'is_checkout_allowed' => true,
                    'is_login_allowed' => false,
                    'is_enabled' => false,
                    'is_company_blocked' => false,
                    'has_customer_company' => false,
                    'is_storefront_registration_allowed' => false,
                    'is_company_admin' => false
                ],
                $this->once()
            ],
            [
                $customer,
                false,
                true,
                [
                    'is_checkout_allowed' => false,
                    'is_login_allowed' => true,
                    'is_enabled' => false,
                    'is_company_blocked' => false,
                    'has_customer_company' => false,
                    'is_storefront_registration_allowed' => false,
                    'is_company_admin' => false
                ],
                $this->once()
            ],
            [null, false, false, [], $this->never()]
        ];
    }
}
