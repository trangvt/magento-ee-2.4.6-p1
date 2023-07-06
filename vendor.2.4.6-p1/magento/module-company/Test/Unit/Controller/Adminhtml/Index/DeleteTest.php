<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\UrlInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Adminhtml\Index\Delete;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Magento\Company\Controller\Adminhtml\Index\Delete class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteTest extends TestCase
{
    /**
     * @var Delete
     */
    private $delete;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Redirect|MockObject
     */
    private $resultRedirect;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var RawFactory|MockObject
     */
    private $resultRawFactory;

    /**
     * @var UrlInterface|MockObject
     */
    private $url;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRedirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->url = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultRawFactory = $this->getMockBuilder(RawFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->delete = $objectManagerHelper->getObject(
            Delete::class,
            [
                'companyRepository' => $this->companyRepository,
                'resultRawFactory' => $this->resultRawFactory,
                '_request' => $this->request,
                'url' => $this->url,
                'messageManager' => $this->messageManager,
                'logger' => $this->logger,
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
        $companyId = 1;
        $companyName = 'Company name';
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $response = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $this->companyRepository->expects($this->once())->method('deleteById')->with($companyId)->willReturn(true);
        $company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('You have deleted company %companyName.', ['companyName' => 'Company name']))
            ->willReturnSelf();
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('company/index')
            ->willReturn('http://exanple.com/admin/company/index');
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($response);
        $response->expects($this->once())->method('setHeader')->with('Content-type', 'text/plain')->willReturnSelf();
        $response->expects($this->once())->method('setContents')
            ->with(json_encode(['url' => 'http://exanple.com/admin/company/index']))
            ->willReturnSelf();

        $this->assertEquals($response, $this->delete->execute());
    }

    /**
     * Test execute method when company doesn't exist.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $companyId = 1;
        $exception = new NoSuchEntityException();
        $response = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($companyId);
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with('The company no longer exists.')
            ->willReturnSelf();
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('company/*/')
            ->willReturn('http://exanple.com/admin/company/*/');
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($response);
        $response->expects($this->once())->method('setHeader')->with('Content-type', 'text/plain')->willReturnSelf();
        $response->expects($this->once())->method('setContents')
            ->with(json_encode(['url' => 'http://exanple.com/admin/company/*/']))
            ->willReturnSelf();

        $this->assertEquals($response, $this->delete->execute());
    }

    /**
     * Test execute method when company can't be deleted.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $companyId = 1;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $exception = new LocalizedException(__('Exception message'));
        $response = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $this->companyRepository->expects($this->once())
            ->method('deleteById')
            ->with($companyId)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with('Exception message')
            ->willReturnSelf();
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('company/index/edit', ['id' => 1])
            ->willReturn('http://exanple.com/admin/company/edit/id/1');
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($response);
        $response->expects($this->once())->method('setHeader')->with('Content-type', 'text/plain')->willReturnSelf();
        $response->expects($this->once())->method('setContents')
            ->with(json_encode(['url' => 'http://exanple.com/admin/company/edit/id/1']))
            ->willReturnSelf();

        $this->assertEquals($response, $this->delete->execute());
    }

    /**
     * Test execute method when Exception is thrown.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $companyId = 1;
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $exception = new \Exception();
        $response = $this->getMockBuilder(Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->expects($this->once())->method('getParam')->with('id')->willReturn($companyId);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $this->companyRepository->expects($this->once())
            ->method('deleteById')
            ->with($companyId)
            ->willThrowException($exception);
        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Something went wrong. Please try again later.'))
            ->willReturnSelf();
        $this->url->expects($this->once())
            ->method('getUrl')
            ->with('company/index/edit', ['id' => 1])
            ->willReturn('http://exanple.com/admin/company/edit/id/1');
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturnSelf();
        $this->resultRawFactory->expects($this->once())->method('create')->willReturn($response);
        $response->expects($this->once())->method('setHeader')->with('Content-type', 'text/plain')->willReturnSelf();
        $response->expects($this->once())->method('setContents')
            ->with(json_encode(['url' => 'http://exanple.com/admin/company/edit/id/1']))
            ->willReturnSelf();

        $this->assertEquals($response, $this->delete->execute());
    }
}
