<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Account;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Company\Controller\Account\Validate;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepositoryMock;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepositoryMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var CustomerSearchResultsInterface|MockObject
     */
    private $customerSearchResultsMock;

    /**
     * @var CompanySearchResultsInterface|MockObject
     */
    private $companySearchResultsMock;

    /**
     * @var Json|MockObject
     */
    private $resultJsonMock;

    /**
     * @var Validate
     */
    private $validate;

    /**
     * Set Up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilderMock
            ->expects($this->any())
            ->method('addFilter')->willReturnSelf();
        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->customerSearchResultsMock = $this->getMockBuilder(CustomerSearchResultsInterface::class)
            ->getMockForAbstractClass();

        $this->customerRepositoryMock
            ->expects($this->any())
            ->method('getList')
            ->willReturn($this->customerSearchResultsMock);

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->companySearchResultsMock = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->getMockForAbstractClass();
        $this->companyRepositoryMock
            ->expects($this->any())
            ->method('getList')
            ->willReturn($this->companySearchResultsMock);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->resultJsonMock = $this->getMockBuilder(Json::class)
            ->onlyMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock->method('create')->willReturn($this->resultJsonMock);
        $this->requestMock = $this->getMockForAbstractClass(
            RequestInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getParam']
        );

        $objectManager = new ObjectManager($this);
        $this->validate = $objectManager->getObject(
            Validate::class,
            [
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'customerRepository' => $this->customerRepositoryMock,
                'companyRepository' => $this->companyRepositoryMock,
                'resultFactory' => $this->resultFactoryMock,
                '_request' => $this->requestMock
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @param int $countCompanyEmail
     * @param string $companyEmail
     * @param int $countCustomerEmail
     * @param string $customerEmail
     * @param array $data
     *
     * @return void
     * @dataProvider dataProviderExecute
     */
    public function testExecute(
        int $countCompanyEmail,
        string $companyEmail,
        int $countCustomerEmail,
        string $customerEmail,
        array $data
    ): void {
        $this->companySearchResultsMock
            ->expects($this->any())
            ->method('getTotalCount')
            ->willReturn($countCompanyEmail);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->withConsecutive(['company_email'], ['customer_email'])
            ->willReturnOnConsecutiveCalls($companyEmail, $customerEmail);
        $this->customerSearchResultsMock
            ->expects($this->any())
            ->method('getTotalCount')
            ->willReturn($countCustomerEmail);
        $resultJsonMock = $this->getMockBuilder(Json::class)
            ->onlyMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $resultJsonMock->setData($data);
        $this->assertEquals($resultJsonMock, $this->validate->execute());
    }

    /**
     * @return array
     */
    public function dataProviderExecute(): array
    {
        return [
            [0, 'company@email.com', 1, 'customer@email.com', ['company_email' => false, 'customer_email' => true]],
            [1, 'company@email.com', 1, 'customer@email.com', ['company_email' => true, 'customer_email' => true]],
            [0, 'company@email.com', 0, 'customer@email.com', ['company_email' => false, 'customer_email' => false]]
        ];
    }
}
