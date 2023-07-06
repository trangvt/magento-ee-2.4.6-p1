<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Adminhtml\Index;

use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Controller\Adminhtml\Index\Edit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends TestCase
{
    /** @var Edit */
    private $controller;

    /** @var Page|\PHPUnit\Framework\MockObject_MockObject */
    private $resultPage;

    /** @var PageFactory|\PHPUnit\Framework\MockObject_MockObject */
    private $resultPageFactory;

    /** @var CompanyRepositoryInterface|\PHPUnit\Framework\MockObject_MockObject */
    private $companyRepository;

    /** @var RequestInterface|\PHPUnit\Framework\MockObject_MockObject */
    private $request;

    /** @var Redirect|\PHPUnit\Framework\MockObject_MockObject */
    private $resultRedirect;

    /** @var ManagerInterface|\PHPUnit\Framework\MockObject_MockObject */
    private $messageManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject_MockObject */
    private $logger;

    /** @var int */
    private $companyId;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->companyId = 1;

        $resultForwardFactory = $this->createMock(ForwardFactory::class);
        $this->resultPage = $this->createMock(Page::class);

        $this->resultPageFactory = $this->createMock(PageFactory::class);
        $this->resultPageFactory->expects($this->once())->method('create')->willReturn($this->resultPage);

        $this->companyRepository = $this->getMockForAbstractClass(CompanyRepositoryInterface::class);

        $this->request = $this->getMockForAbstractClass(RequestInterface::class);
        $this->request->expects($this->once())->method('getParam')->willReturn($this->companyId);

        $this->resultRedirect = $this->createMock(Redirect::class);

        $resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $resultRedirectFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);

        $this->messageManager = $this->getMockForAbstractClass(
            ManagerInterface::class,
            [],
            '',
            false
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class, [], '', false);

        $objectManagerHelper = new ObjectManager($this);
        $this->controller = $objectManagerHelper->getObject(
            Edit::class,
            [
                'resultForwardFactory' => $resultForwardFactory,
                'resultPageFactory' => $this->resultPageFactory,
                'companyRepository' => $this->companyRepository,
                '_request' => $this->request,
                'resultRedirectFactory' => $resultRedirectFactory
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute()
    {
        $company = $this->getMockForAbstractClass(
            CompanyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCompanyName']
        );
        $company->expects($this->once())->method('getCompanyName')->willReturn('Company Name');

        $this->companyRepository->expects($this->once())->method('get')
            ->with($this->companyId)->willReturn($company);

        $page = $this->createMock(Page::class);
        $this->resultPage->expects($this->once())->method('setActiveMenu')
            ->with('Magento_Company::company_index')->willReturn($page);

        $config = $this->createMock(Config::class);
        $this->resultPage->expects($this->once())->method('getConfig')->willReturn($config);

        $title = $this->createMock(Title::class);
        $config->expects($this->once())->method('getTitle')->willReturn($title);
        $title->expects($this->once())->method('prepend')->with('Company Name');

        $this->assertSame($this->resultPage, $this->controller->execute());
    }

    /**
     * Test for method execute with exception.
     *
     * @return void
     */
    public function testExecuteException()
    {
        $exception = new \Exception('Exception message');
        $this->companyRepository->expects($this->once())
            ->method('get')
            ->with($this->companyId)
            ->willThrowException($exception);
        $this->messageManager->expects($this->any())
            ->method('addError')
            ->with('[Company ID: 1] was not found');

        $this->resultRedirect->expects($this->any())->method('setPath')->with('*/*/index')->willReturnSelf();

        $this->assertSame($this->resultRedirect, $this->controller->execute());
    }
}
