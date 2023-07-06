<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Customer;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Adminhtml\Customer\SuperUserValidator;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\Company\Controller\Adminhtml\Customer\SuperUserValidator class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SuperUserValidatorTest extends TestCase
{
    /**
     * @var CompanyAttributes|MockObject
     */
    private $companyAttributes;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var SuperUserValidator
     */
    private $superUserValidator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyAttributes = $this->getMockBuilder(CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->superUserValidator = $objectManager->getObject(
            SuperUserValidator::class,
            [
                'companyAttributes' => $this->companyAttributes,
                'customerRepository' => $this->customerRepository,
                'resultFactory' => $this->resultFactory,
                'request' => $this->request,
                'companyRepository' => $this->companyRepository,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @param array $customerIds
     * @param int $superUserId
     * @param bool $deletable
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $customerIds,
        $superUserId,
        $deletable
    ) {
        $this->request->expects($this->once())->method('getParam')->with('customer_ids')->willReturn($customerIds);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->once())->method('create')->with('json')->willReturn($resultJson);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyCustomer = $this->getMockBuilder(CompanyCustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->willReturn($customer);
        $this->companyAttributes->expects($this->once())
            ->method('getCompanyAttributesByCustomer')
            ->with($customer)
            ->willReturn($companyCustomer);
        $companyCustomer->expects($this->once())->method('getCompanyId')->willReturn(1);
        $this->companyRepository->expects($this->once())->method('get')->with(1)->willReturn($company);
        $company->expects($this->once())->method('getSuperUserId')->willReturn($superUserId);
        $resultJson->expects($this->once())
            ->method('setData')
            ->with(['deletable' => $deletable])
            ->willReturnSelf();

        $this->assertEquals($resultJson, $this->superUserValidator->execute());
    }

    /**
     * Data provider foe execute method.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [[1], 1, false],
            [[1], 2, true]
        ];
    }

    /**
     * Test execute method if customer doesn't exist.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception'));
        $exception = new NoSuchEntityException($phrase);
        $customerIds = [99999, 100000];
        $this->request->expects($this->once())->method('getParam')->with('customer_ids')->willReturn($customerIds);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory->expects($this->once())->method('create')->with('json')->willReturn($resultJson);
        $this->customerRepository->expects($this->once())
            ->method('getById')
            ->willThrowException($exception);
        $resultJson->expects($this->once())
            ->method('setData')
            ->with(['deletable' => false])
            ->willReturnSelf();

        $this->assertEquals($resultJson, $this->superUserValidator->execute());
    }
}
