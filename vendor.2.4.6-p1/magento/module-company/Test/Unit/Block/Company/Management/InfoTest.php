<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Company\Management;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Block\Company\Management\Info;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class InfoTest extends TestCase
{
    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $urlBuilder;

    /**
     * @var CustomerRepositoryInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $customerRepository;

    /**
     * @var UserContextInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $userContext;

    /**
     * @var CompanyManagementInterface|\PHPUnit\Framework\MockObject_MockObject
     */
    private $companyManagement;

    /**
     * @var Info
     */
    private $info;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->userContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->companyManagement  = $this
            ->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId'])
            ->getMockForAbstractClass();
        $objectManager = new ObjectManager($this);
        $this->info = $objectManager->getObject(
            Info::class,
            [
                'customerRepository' => $this->customerRepository,
                '_urlBuilder' => $this->urlBuilder,
                'userContext' => $this->userContext,
                'companyManagement' => $this->companyManagement,
                'data' => []
            ]
        );
    }

    /**
     * @param CompanyInterface|null $company
     * @param bool $result
     * @dataProvider dataProviderHasCustomerCompany
     */
    public function testHasCustomerCompany($company, $result)
    {
        $this->userContext->expects($this->exactly(1))->method('getUserId')->willReturn(1);
        $this->companyManagement->expects($this->once())->method('getByCustomerId')->with(1)->willReturn($company);
        $this->assertEquals($result, $this->info->hasCustomerCompany());
    }

    /**
     * Test method for getCreateCompanyAccountUrl
     */
    public function testGetCreateCompanyAccountUrl()
    {
        $value = '*/account/createPost';
        $this->urlBuilder->expects($this->any())->method('getUrl')->willReturn($value);
        $this->assertEquals($value, $this->info->getCreateCompanyAccountUrl());
    }

    /**
     * Data provider isCurrentUserCompanyAdmin
     *
     * @return array
     */
    public function dataProviderHasCustomerCompany()
    {
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        return [
            [$company, true],
            [null, false]
        ];
    }
}
