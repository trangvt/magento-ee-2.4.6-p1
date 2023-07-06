<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Adminhtml\Index\AddUser;
use Magento\Company\Model\Customer\CompanyAttributes;
use Magento\Company\Model\CustomerRetriever;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for Magento\Company\Controller\Adminhtml\Index\AddUser class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddUserTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var AddUser
     */
    private $addUser;

    /**
     * @var CustomerRetriever|MockObject
     */
    private $customerRetrieverMock;

    /**
     * @var CompanyManagementInterface|MockObject
     */
    private $companyManagementMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * Setup.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerRetrieverMock = $this->getMockBuilder(CustomerRetriever::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->addUser = $this->objectManagerHelper->getObject(
            AddUser::class,
            [
                '_request' => $this->request,
                'customerRetriever' => $this->customerRetrieverMock,
                'companyManagement' => $this->companyManagementMock,
                'logger' => $this->loggerMock,
                'resultFactory' => $this->resultFactoryMock,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->prepareResponseMock();
        $email = 'test@test.com';
        $websiteId = 2;
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['email'], ['website_id'])
            ->willReturnOnConsecutiveCalls($email, $websiteId);
        $companyAttributes = $this->getMockBuilder(CompanyAttributes::class)
            ->disableOriginalConstructor()
            ->setMethods(['getJobTitle', 'getStatus'])
            ->getMock();

        $companyAttributes->expects($this->once())->method('getJobTitle')->willReturn('job title');
        $customerExtension = $this->getMockBuilder(CustomerExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyAttributes'])
            ->getMockForAbstractClass();
        $customerExtension->expects($this->atLeastOnce())->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customer->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($customerExtension);
        $this->customerRetrieverMock
            ->expects($this->once())
            ->method('retrieveForWebsite')
            ->with($email, $websiteId)
            ->willReturn($customer);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyManagementMock->expects($this->once())->method('getByCustomerId')->willReturn($company);

        $this->assertInstanceOf(JsonResult::class, $this->addUser->execute());
    }

    /**
     * Test execute with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->prepareResponseMock();
        $exception = new \Exception();
        $this->request->expects($this->once())->method('getParam')->with('email')->willThrowException($exception);

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $this->addUser->execute());
    }

    /**
     * Test execute with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->prepareResponseMock();
        $email = 'test';
        $this->request->expects($this->once())->method('getParam')->with('email')->willReturn($email);

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $this->addUser->execute());
    }

    /**
     * Test execute with NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $this->prepareResponseMock();
        $phrase = new Phrase(__('Exception'));
        $exception = new NoSuchEntityException($phrase);
        $this->request->expects($this->once())->method('getParam')->with('email')->willThrowException($exception);

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $this->addUser->execute());
    }

    /**
     * Prepare response mock.
     *
     * @return void
     */
    private function prepareResponseMock()
    {
        $responseMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($responseMock);
    }
}
