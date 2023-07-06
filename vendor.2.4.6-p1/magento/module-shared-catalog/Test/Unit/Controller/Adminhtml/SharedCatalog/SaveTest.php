<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Controller\Adminhtml\SharedCatalog;

use Magento\Backend\Model\View\Result\RedirectFactory as BackendRedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;
use Magento\SharedCatalog\Controller\Adminhtml\SharedCatalog\Save;
use Magento\SharedCatalog\Model\SharedCatalogBuilder;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test Admin SharedCatalog Save controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var SharedCatalogFactory|MockObject
     */
    private $sharedCatalogFactory;

    /**
     * @var SharedCatalogRepositoryInterface|MockObject
     */
    private $sharedCatalogRepository;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var SharedCatalogBuilder|MockObject
     */
    private $sharedCatalogBuilder;

    /**
     * @var Save
     */
    private $controller;

    /**
     * @var SharedCatalogInterface|MockObject
     */
    private $sharedCatalog;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->redirect = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultRedirectFactory = $this
            ->getMockBuilder(BackendRedirectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['addSuccess', 'addException', 'addErrorMessage'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogRepository = $this
            ->getMockBuilder(SharedCatalogRepositoryInterface::class)
            ->setMethods(['save', 'get'])
            ->getMockForAbstractClass();
        $this->request = $this
            ->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->sharedCatalogFactory = $this->getMockBuilder(SharedCatalogFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sharedCatalog = $this->getMockBuilder(SharedCatalogInterface::class)
            ->setMethods(['setData', 'setId', 'getName', 'getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->sharedCatalogBuilder = $this->getMockBuilder(SharedCatalogBuilder::class)
            ->setMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Save::class,
            [
                '_request' => $this->request,
                'messageManager' => $this->messageManager,
                'sharedCatalogRepository' => $this->sharedCatalogRepository,
                'sharedCatalogBuilder' => $this->sharedCatalogBuilder,
                'logger' => $this->loggerMock,
                'resultRedirectFactory' => $this->resultRedirectFactory
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @param bool $isContinue
     * @param string $setPathFirstArg
     * @param array $setPathSecondArg
     * @dataProvider executeDataProvider
     * @return void
     */
    public function testExecute($isContinue, $setPathFirstArg, array $setPathSecondArg)
    {
        $sharedCatalogId = 2;
        $successMessage = __('You saved the shared catalog.');

        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willReturn($this->sharedCatalog);
        $this->sharedCatalogRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->sharedCatalog)
            ->willReturn($sharedCatalogId);
        $this->messageManager->expects($this->once())->method('addSuccess')->with($successMessage)->willReturnSelf();
        $this->sharedCatalog->expects($this->once())->method('getId')->willReturn($sharedCatalogId);
        $mapForGerParamMethod = [
            ['back', null, $isContinue],
            [SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM, null, $sharedCatalogId],
        ];
        $this->request->expects($this->exactly(2))->method('getParam')->willReturnMap($mapForGerParamMethod);
        $this->redirect
            ->expects($this->once())
            ->method('setPath')
            ->with($setPathFirstArg, $setPathSecondArg)
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($this->redirect);

        $this->assertEquals($this->redirect, $this->controller->execute());
    }

    /**
     * Data provider for execute method.
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                true,
                'shared_catalog/sharedCatalog/edit',
                ['shared_catalog_id' => 2]
            ],
            [
                false,
                'shared_catalog/sharedCatalog/index',
                []
            ]
        ];
    }

    /**
     * Test execute method throes exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception();
        $exceptionMessage = __('Something went wrong while saving the shared catalog.');

        $sharedCatalogUrlParam = SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM;
        $sharedCatalogId = 23;
        $this->request->expects($this->exactly(1))->method('getParam')->with($sharedCatalogUrlParam)
            ->willReturn($sharedCatalogId);

        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willThrowException($exception);
        $this->messageManager
            ->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, $exceptionMessage)
            ->willReturnSelf();
        $this->redirect
            ->expects($this->once())
            ->method('setPath')
            ->with('shared_catalog/sharedCatalog/index')
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($this->redirect);

        $this->assertEquals($this->redirect, $this->controller->execute());
    }

    /**
     * Test execute method throws LocalizedException.
     *
     * @param int|null $sharedCatalogId
     * @param string $setPathFirstArg
     * @param array $setPathSecondArg
     * @param int $getIdCounter
     * @dataProvider executeWithLocalizedExceptionDataProvider
     * @return void
     */
    public function testExecuteWithLocalizedException(
        $sharedCatalogId,
        $setPathFirstArg,
        array $setPathSecondArg,
        $getIdCounter
    ) {
        $exceptionMessage = 'Localized Exception';
        $exception = new LocalizedException(__($exceptionMessage));

        $this->sharedCatalogBuilder->expects($this->once())->method('build')->willReturn($this->sharedCatalog);
        $this->sharedCatalogRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->sharedCatalog)
            ->willThrowException($exception);
        $this->messageManager
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with($exceptionMessage)
            ->willReturnSelf();
        $this->sharedCatalog->expects($this->exactly($getIdCounter))->method('getId')->willReturn($sharedCatalogId);
        $this->redirect
            ->expects($this->once())
            ->method('setPath')
            ->with($setPathFirstArg, $setPathSecondArg)
            ->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($this->redirect);

        $this->assertEquals($this->redirect, $this->controller->execute());
    }

    /**
     * Data provider for execute() with LocalizedException.
     *
     * @return array
     */
    public function executeWithLocalizedExceptionDataProvider()
    {
        return [
            [
                2,
                'shared_catalog/sharedCatalog/edit',
                ['shared_catalog_id' => 2],
                2
            ],
            [
                null,
                'shared_catalog/sharedCatalog/create',
                [],
                1
            ]
        ];
    }
}
