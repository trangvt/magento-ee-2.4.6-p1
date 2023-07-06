<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\PageFactory;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\DuplicatePost;
use Magento\SharedCatalog\Model\Duplicator;
use Magento\SharedCatalog\Model\SharedCatalogBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for class DuplicatePost.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DuplicatePostTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var RedirectFactory|MockObject
     */
    private $redirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var SharedCatalogBuilder|MockObject
     */
    private $sharedCatalogBuilder;

    /**
     * @var Duplicator|MockObject
     */
    private $duplicateManager;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * @var DuplicatePost
     */
    private $duplicateController;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectFactory = $this->getMockBuilder(RedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->redirectFactory->expects($this->once())->method('create')->willReturn($this->redirect);

        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addSuccess'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogBuilder = $this->getMockBuilder(SharedCatalogBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->duplicateManager = $this->getMockBuilder(Duplicator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->duplicateController = $this->objectManager->getObject(
            DuplicatePost::class,
            [
                'resultPageFactory' => $this->resultPageFactory,
                'sharedCatalogBuilder' => $this->sharedCatalogBuilder,
                'duplicateManager' => $this->duplicateManager,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                '_request' => $this->request,
                'resultRedirectFactory' => $this->redirectFactory,
                'resultFactory' => $this->resultFactory,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * Test execute method with throw LocalizedException with edit redirect.
     *
     * @return void
     */
    public function testExecuteWithLocalizedExceptionEdit()
    {
        $this->sharedCatalog->expects($this->exactly(2))->method('getId')->willReturn(1);
        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willReturn($this->sharedCatalog);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('save')->willThrowException(new LocalizedException(new Phrase('error')));
        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->redirect->expects($this->once())->method('setPath')
            ->with('shared_catalog/sharedCatalog/edit')->willReturnSelf();
        $this->duplicateController->execute();
    }

    /**
     * Test execute method with throw LocalizedException with create redirect.
     *
     * @return void
     */
    public function testExecuteWithLocalizedExceptionCreate()
    {
        $this->sharedCatalog->expects($this->once())->method('getId')->willReturn(0);
        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willReturn($this->sharedCatalog);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('save')->willThrowException(new LocalizedException(new Phrase('error')));
        $this->messageManager->expects($this->once())->method('addErrorMessage');
        $this->redirect->expects($this->once())->method('setPath')
            ->with('shared_catalog/sharedCatalog/duplicate')->willReturnSelf();
        $this->duplicateController->execute();
    }

    /**
     * Test execute method with throw Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->sharedCatalog->expects($this->never())->method('getId')->willReturn(0);

        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willReturn($this->sharedCatalog);
        $this->sharedCatalogRepository->expects($this->once())
            ->method('save')->willThrowException(new \Exception());
        $this->messageManager->expects($this->once())->method('addExceptionMessage');
        $this->redirect->expects($this->once())->method('setPath')
            ->with('shared_catalog/sharedCatalog/duplicate')->willReturnSelf();
        $this->duplicateController->execute();
    }

    /**
     * Test execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->exactly(2))->method('getId')->willReturn(1);
        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willReturn($sharedCatalog);
        $this->sharedCatalogRepository->expects($this->once())->method('save');
        $this->duplicateManager->expects($this->atLeastOnce())->method('duplicateCatalog');
        $this->messageManager->expects($this->once())->method('addSuccess');
        $this->redirect->expects($this->once())->method('setPath')
            ->with('shared_catalog/sharedCatalog/index')->willReturnSelf();
        $this->duplicateController->execute();
    }
}
