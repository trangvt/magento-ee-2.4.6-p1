<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Console\Request;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Item\Delete;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionList;
use Magento\RequisitionList\Model\RequisitionList\Items;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class ActionTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class ActionTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    protected $requestValidator;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    protected $requisitionListRepository;

    /**
     * @var Items|MockObject
     */
    protected $requisitionListItemRepository;

    /**
     * @var Delete
     */
    protected $mock;

    /**
     * @var Request|MockObject
     */
    protected $request;

    /**
     * @var Redirect|MockObject
     */
    protected $resultRedirect;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var RequisitionList|MockObject
     */
    protected $requisitionList;

    /**
     * @var StockRegistryInterface|MockObject
     */
    protected $stockRegistry;

    /**
     * @var string
     */
    protected $mockClass;

    /**
     * Prepare requisition list
     */
    abstract protected function prepareRequisitionList();

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->resultRedirect = $this->createPartialMock(
            Redirect::class,
            ['setPath', 'setRefererUrl']
        );
        $this->request = $this->createMock(Request::class);
        $this->requestValidator = $this->createMock(RequestValidator::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->requisitionList = $this->createMock(RequisitionList::class);
        $this->requisitionListRepository =
            $this->getMockForAbstractClass(RequisitionListRepositoryInterface::class);
        $this->requisitionListItemRepository =
            $this->createMock(Items::class);
        $this->resultFactory = $this->createPartialMock(ResultFactory::class, ['create']);
        $this->stockRegistry = $this->getMockForAbstractClass(StockRegistryInterface::class);
        $objectManager = new ObjectManager($this);
        $this->mock = $objectManager->getObject(
            'Magento\RequisitionList\Controller\Item\\' . $this->mockClass,
            [
                'request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'requestValidator' => $this->requestValidator,
                'requisitionListRepository' => $this->requisitionListRepository,
                'logger' => $this->logger,
                'requisitionListItemRepository' => $this->requisitionListItemRepository,
                'stockRegistry' => $this->stockRegistry,
            ]
        );
    }

    /**
     * Test execute method
     */
    public function testExecute()
    {
        $this->prepareRequest();
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $this->prepareResultRedirect();
        $this->prepareRequisitionList();

        $this->assertInstanceOf(Redirect::class, $this->mock->execute());
    }

    /**
     * Test execute method not allowed action
     */
    public function testExecuteWithNotAllowedAction()
    {
        $this->resultRedirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn($this->resultRedirect);
        $this->requisitionList->expects($this->never())->method('getItems');

        $this->assertInstanceOf(Redirect::class, $this->mock->execute());
    }

    /**
     * Test execute with Exception
     */
    public function testExecuteWithException()
    {
        $this->prepareRequest();
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $this->prepareResultRedirect();
        $this->prepareRequisitionList();
        $exception = new \Exception();
        $this->requisitionListRepository->expects($this->any())->method('save')->willThrowException($exception);

        $this->assertInstanceOf(ResultInterface::class, $this->mock->execute());
    }

    /**
     * Test execute with LocalizedException
     */
    public function testExecuteWithLocalizedException()
    {
        $this->prepareRequest();
        $this->requestValidator->expects($this->any())->method('getResult')->willReturn(null);
        $this->prepareResultRedirect();
        $this->prepareRequisitionList();
        $phrase = new Phrase('exception');
        $localizedException = new LocalizedException($phrase);
        $this->requisitionListRepository->expects($this->any())->method('save')
            ->willThrowException($localizedException);

        $this->assertInstanceOf(ResultInterface::class, $this->mock->execute());
    }

    /**
     * Prepare request
     */
    protected function prepareRequest()
    {
        $this->request->expects($this->any())->method('getParam')->willReturnMap(
            [
                ['requisition_id', null, 1],
                ['selected', null, '1, 2, 3, 4, 5'],
                ['qty', null, ['sku' => 1]]
            ]
        );
    }

    /**
     * Prepare result redirect
     */
    protected function prepareResultRedirect()
    {
        $this->resultRedirect->expects($this->any())->method('setPath')->willReturnSelf();
        $this->resultRedirect->expects($this->any())->method('setRefererUrl')->willReturnSelf();
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->resultRedirect);
    }
}
