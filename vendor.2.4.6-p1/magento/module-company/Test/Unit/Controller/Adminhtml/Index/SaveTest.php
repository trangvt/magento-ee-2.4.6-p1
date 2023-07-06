<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\Session;
use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Company\Controller\Adminhtml\Index\Save;
use Magento\Company\Model\CompanySuperUserGet;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for adminhtml company save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var DataObjectProcessor|MockObject
     */
    private $dataObjectProcessor;

    /**
     * @var CompanySuperUserGet|MockObject
     */
    private $companySuperUserGet;

    /**
     * @var CompanyInterfaceFactory|MockObject
     */
    private $companyDataFactory;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var DataObjectHelper|MockObject
     */
    private $dataObjectHelper;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * @var CompanyInterface|MockObject
     */
    private $companyMock;

    /**
     * @var Save
     */
    private $save;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->dataObjectProcessor = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companySuperUserGet = $this->getMockBuilder(CompanySuperUserGet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyDataFactory = $this->getMockBuilder(CompanyInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataObjectHelper = $this->getMockBuilder(DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirectFactory = $this->getMockBuilder(BackendRedirectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCompanyData'])
            ->getMock();
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->save = $objectManagerHelper->getObject(
            Save::class,
            [
                'dataObjectProcessor' => $this->dataObjectProcessor,
                'superUser' => $this->companySuperUserGet,
                'companyDataFactory' => $this->companyDataFactory,
                'companyRepository' => $this->companyRepository,
                'dataObjectHelper' => $this->dataObjectHelper,
                '_request' => $this->request,
                '_eventManager' => $this->eventManager,
                'messageManager' => $this->messageManager,
                'resultRedirectFactory' => $this->resultRedirectFactory,
                '_session' => $this->session,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $companyId = 1;
        $params = [
            [
                CompanyInterface::COMPANY_ID => $companyId,
                CompanyInterface::EMAIL => 'exampl@test.com',
                CompanyInterface::NAME => 'Example Company Name',
                CompanyInterface::REGION_ID => 2,
                CompanyInterface::COUNTRY_ID => 'US',
                CompanyInterface::REGION => 'Alabama',
            ],
            'company_admin' => [
                CompanyInterface::EMAIL => 'exampl@test.com',
            ]
        ];
        $this->request->expects($this->exactly(3))
            ->method('getParam')
            ->withConsecutive(['id'], ['id'], ['back'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId, false);
        $this->request->expects($this->any())->method('getParams')->willReturn($params);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturn($company);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companySuperUserGet->expects($this->once())->method('getUserForCompanyAdmin')->willReturn($customerMock);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('adminhtml_company_save_after', ['company' => $company, 'request' => $this->request]);
        $company->expects($this->once())->method('getCompanyName')->willReturn($params[0][CompanyInterface::NAME]);
        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('You have saved company %companyName.', ['companyName' => $params[0][CompanyInterface::NAME]]))
            ->willReturnSelf();
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setPath')->with('company/index')->willReturnSelf();
        $this->assertEquals($result, $this->save->execute());
    }

    /**
     * Test for execute method with empty company id.
     *
     * @return void
     */
    public function testExecuteWithEmptyCompanyId()
    {
        $params = [
            [
                CompanyInterface::EMAIL => 'exampl@test.com',
                CompanyInterface::NAME => 'Example Company Name',
                CompanyInterface::REGION_ID => 2,
                CompanyInterface::COUNTRY_ID => 'US',
                CompanyInterface::REGION => 'Alabama',
            ],
            'company_admin' => [
                CompanyInterface::EMAIL => 'exampl@test.com',
            ]
        ];
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['id'], ['back'])
            ->willReturnOnConsecutiveCalls(null, true);
        $this->request->expects($this->any())->method('getParams')->willReturn($params);
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyDataFactory->expects($this->once())->method('create')->willReturn($company);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($company)->willReturn($company);
        $company->expects($this->once())->method('getId')->willReturn(null);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companySuperUserGet->expects($this->once())->method('getUserForCompanyAdmin')->willReturn($customerMock);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('adminhtml_company_save_after', ['company' => $company, 'request' => $this->request]);
        $company->expects($this->once())->method('getCompanyName')->willReturn($params[0][CompanyInterface::NAME]);
        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('You have created company %companyName.', ['companyName' => $params[0][CompanyInterface::NAME]]))
            ->willReturnSelf();
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setPath')->with('company/index/new')->willReturnSelf();
        $this->assertEquals($result, $this->save->execute());
    }

    /**
     * Test for execute() method with localized exception.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->prepareExceptionsMocks();
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('adminhtml_company_save_after', ['company' => $this->companyMock, 'request' => $this->request])
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with($exception->getMessage())
            ->willReturnSelf();
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setPath')->with('company/index/edit')->willReturnSelf();
        $this->assertEquals($result, $this->save->execute());
    }

    /**
     * Test for execute() method with exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->prepareExceptionsMocks();
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception();
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('adminhtml_company_save_after', ['company' => $this->companyMock, 'request' => $this->request])
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, $phrase)
            ->willReturnSelf();
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($result);
        $result->expects($this->once())->method('setPath')->with('company/index/edit')->willReturnSelf();
        $this->assertEquals($result, $this->save->execute());
    }

    /**
     * Prepare mocks for Execute method test when exceptions are thrown.
     *
     * @return void
     */
    private function prepareExceptionsMocks()
    {
        $companyId = 1;
        $params = [
            [
                CompanyInterface::EMAIL => 'exampl@test.com',
                CompanyInterface::NAME => 'Example Company Name',
                CompanyInterface::REGION_ID => 2,
                CompanyInterface::COUNTRY_ID => 'US',
                CompanyInterface::REGION => 'Alabama',
            ],
            'company_admin' => [
                CompanyInterface::EMAIL => 'exampl@test.com',
            ]
        ];
        $companyData = [
            CompanyInterface::EMAIL => 'example@test.com',
        ];
        $this->request->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['id'], ['id'])
            ->willReturnOnConsecutiveCalls($companyId, $companyId);
        $this->request->expects($this->any())->method('getParams')->willReturn($params);
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willReturn($this->companyMock);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->willReturnSelf();
        $this->companyRepository->expects($this->once())->method('save')->with($this->companyMock)
            ->willReturn($this->companyMock);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companySuperUserGet->expects($this->once())->method('getUserForCompanyAdmin')->willReturn($customerMock);
        $this->companyMock->expects($this->atLeastOnce())->method('getId')->willReturn($companyId);
        $this->dataObjectProcessor->expects($this->once())
            ->method('buildOutputDataArray')
            ->with($this->companyMock, CompanyInterface::class)
            ->willReturn($companyData);
        $this->session->expects($this->once())->method('setCompanyData')->with($companyData)->willReturnSelf();
    }
}
