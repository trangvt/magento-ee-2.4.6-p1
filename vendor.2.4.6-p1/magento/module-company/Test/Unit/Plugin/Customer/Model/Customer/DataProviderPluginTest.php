<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Customer\Model\Customer;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Plugin\Customer\Model\Customer\DataProviderPlugin;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses as CustomerDataProvider;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @deprecated tested file is not used
 * Class for test DataProviderPlugin.
 */
class DataProviderPluginTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    protected $customerRepository;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    protected $companyRepository;

    /**
     * @var CustomerDataProvider|MockObject
     */
    protected $customerDataProvider;

    /**
     * @var DataProviderPlugin|MockObject
     */
    protected $customerDataProviderPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );

        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $this->customerDataProvider = $this->createMock(CustomerDataProvider::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->customerDataProviderPlugin = $objectManagerHelper->getObject(
            DataProviderPlugin::class,
            [
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository
            ]
        );
    }

    /**
     * Test for method AfterGetData.
     *
     * @param array|null $data
     * @param array|null $companyData
     * @param array|null $expectedResult
     * @dataProvider dataProviderAfterGetData
     * @return void
     */
    public function testAfterGetData($data, $companyData, $expectedResult)
    {
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyName', 'getId'])
            ->getMockForAbstractClass();

        $companyAttributes = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->setMethods(['getIsSuperUser', 'getCompanyId', 'getStatus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyAttributes', 'getCompanyAttributes'])
            ->getMockForAbstractClass();

        $customer->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($customerExtension);
        $customerExtension->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturn($companyAttributes);

        $companyAttributes->expects($this->any())
            ->method('getIsSuperUser')
            ->willReturn($companyData['is_super_user']);
        $companyAttributes->expects($this->any())
            ->method('getCompanyId')
            ->willReturn($companyData['company_id']);
        $companyAttributes->expects($this->any())
            ->method('getStatus')
            ->willReturn($companyData['status']);

        $this->companyRepository->expects($this->any())
            ->method('get')
            ->willReturn($company);

        $company->expects($this->any())
            ->method('getCompanyName')
            ->willReturn($companyData['company_name']);

        $company->expects($this->any())
            ->method('getId')
            ->willReturn($companyData['company_id']);

        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->willReturn($customer);

        $resultData = $this->customerDataProviderPlugin->afterGetData($this->customerDataProvider, $data);

        $this->assertEquals($expectedResult, $resultData);
    }

    /**
     * Data provider for method testAfterGetData.
     *
     * @return array
     */
    public function dataProviderAfterGetData()
    {
        return [
            [
                [
                    7 => [
                        'customer' => []
                    ]
                ],
                [
                    'status' => 0,
                    'is_super_user' => false,
                    'company_id' => 1,
                    'company_name' => 'Company 1'
                ],
                [
                    7 => [
                        'customer' => [
                            'extension_attributes' => [
                                'company_attributes' => [
                                    'is_super_user' => 1,
                                    'status' => 0,
                                    'company_id' => 1,
                                    'company_name' => 'Company 1'
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            [
                [
                    7 => [
                        'customer' => []
                    ]
                ],
                [
                    'status' => 0,
                    'is_super_user' => false,
                    'company_id' => null,
                    'company_name' => null
                ],
                [
                    7 => [
                        'customer' => [
                            'extension_attributes' => [
                                'company_attributes' => [
                                    'status' => '0',
                                    'is_super_user' => 1,
                                    'company_id' => null,
                                    'company_name' => null
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            [
                null,
                [
                    'status' => null,
                    'is_super_user' => null,
                    'company_id' => null,
                    'company_name' => null
                ],
                null
            ],
        ];
    }
}
