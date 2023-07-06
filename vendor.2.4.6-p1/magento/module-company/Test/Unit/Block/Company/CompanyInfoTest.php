<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Block\Company;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Block\Company\CompanyInfo;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyInfoTest extends TestCase
{
    /**
     * @var JsonFactory|MockObject
     */
    protected $resultJsonFactory;

    /**
     * @var RequestInterface|MockObject
     */
    protected $request;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    protected $customerAttributes;

    /**
     * @var CompanyCustomerInterface|MockObject
     */
    protected $companyAttributes;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $customerRepository;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    protected $companyRepository;

    /**
     * @var Customer|MockObject
     */
    protected $customer;

    /**
     * @var UserContextInterface|MockObject
     */
    private $customerContext;

    /**
     * @var CompanyInfo
     */
    protected $companyInfo;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->companyRepository = $this->getMockForAbstractClass(CompanyRepositoryInterface::class);
        $this->customer = $this->createMock(Customer::class);
        $this->customerContext = $this->getMockForAbstractClass(UserContextInterface::class);
        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->companyAttributes = $this->getMockForAbstractClass(CompanyCustomerInterface::class);

        $customerExtension = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );

        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->willReturn($this->customer);

        $this->customerContext->expects($this->any())->method('getUserId')->willReturn(1);

        $this->customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($this->companyAttributes);

        $objectManagerHelper = new ObjectManager($this);
        $this->companyInfo = $objectManagerHelper->getObject(
            CompanyInfo::class,
            [
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository,
                'customerContext' => $this->customerContext,
                'data' => []
            ]
        );
    }

    /**
     * @param string $jobTitle
     * @dataProvider dataProviderGetJobTitle
     */
    public function testGetJobTitle($jobTitle)
    {
        $this->companyAttributes->expects($this->any())
            ->method('getJobTitle')
            ->willReturn($jobTitle);
        $this->assertEquals($jobTitle, $this->companyInfo->getJobTitle());
    }

    /**
     * @param bool $isCompanyExist
     * @param string|null $companyName
     * @dataProvider dataProviderGetCompanyName
     */
    public function testGetCompanyName($isCompanyExist, $companyName = null)
    {
        $company = $this->getMockForAbstractClass(CompanyInterface::class);

        $company->expects($this->any())
            ->method('getCompanyName')
            ->willReturn('Company 1');

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($isCompanyExist ? $company : null);

        $this->assertEquals($isCompanyExist ? $companyName : '', $this->companyInfo->getCompanyName());
    }

    /**
     * @return array
     */
    public function dataProviderGetCompanyName()
    {
        return [
            [true, 'Company 1'],
            [false],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderGetJobTitle()
    {
        return [
            ['My Job']
        ];
    }
}
