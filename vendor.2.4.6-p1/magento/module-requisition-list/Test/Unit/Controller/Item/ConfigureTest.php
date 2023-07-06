<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\Catalog\Helper\Product\View;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Controller\Item\Configure;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionList\Items;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for configure.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigureTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidator;

    /**
     * @var Items|MockObject
     */
    private $requisitionListItemRepository;

    /**
     * @var PageFactory|MockObject
     */
    private $resultPageFactory;

    /**
     * @var DataObjectFactory|MockObject
     */
    private $dataObjectFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var OptionsManagement|MockObject
     */
    private $optionsManagement;

    /**
     * @var View|MockObject
     */
    private $productViewHelper;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Configure
     */
    private $configure;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestValidator = $this->getMockBuilder(RequestValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemRepository = $this
            ->getMockBuilder(Items::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->optionsManagement = $this->getMockBuilder(OptionsManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productViewHelper = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareAndRender'])
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['addError'])
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->configure = $objectManager->getObject(
            Configure::class,
            [
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'requestValidator' => $this->requestValidator,
                'requisitionListItemRepository' => $this->requisitionListItemRepository,
                'resultPageFactory' => $this->resultPageFactory,
                'dataObjectFactory' => $this->dataObjectFactory,
                'optionsManagement' => $this->optionsManagement,
                'productViewHelper' => $this->productViewHelper,
                'logger' => $this->logger,
                'messageManager' => $this->messageManager,
            ]
        );
    }

    /**
     * Test for execute().
     *
     * @return void
     */
    public function testExecute()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $resultPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMock();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultPage);
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemRepository->expects($this->any())->method('get')->willReturn($item);
        $params = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $params->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('create')->willReturn($params);
        $this->productViewHelper->expects($this->atLeastOnce())->method('prepareAndRender')->willReturnSelf();

        $this->assertInstanceOf(ResultInterface::class, $this->configure->execute());
    }

    /**
     * Test for execute() with LocalizedException.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $resultPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMock();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultPage);
        $phrase = new Phrase('Exception');
        $exception = new LocalizedException(__($phrase));
        $this->requisitionListItemRepository->expects($this->any())->method('get')->willThrowException($exception);
        $this->messageManager->expects($this->atLeastOnce())->method('addError')->willReturnSelf();

        $this->assertInstanceOf(ResultInterface::class, $this->configure->execute());
    }

    /**
     * Test for execute() with Exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->request->expects($this->any())->method('getParam')->willReturn(1);
        $resultPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPath'])
            ->getMock();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($resultPage);
        $exception = new \Exception();
        $this->requisitionListItemRepository->expects($this->any())->method('get')->willThrowException($exception);

        $this->assertInstanceOf(ResultInterface::class, $this->configure->execute());
    }
}
