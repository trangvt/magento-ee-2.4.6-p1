<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Controller\Item\Copy;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Controller\Item\Copy\FromOrder;
use Magento\RequisitionList\Model\Action\RequestValidator;
use Magento\RequisitionList\Model\RequisitionList\Order\Converter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for FromOrder controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FromOrderTest extends TestCase
{
    /**
     * @var RequestValidator|MockObject
     */
    private $requestValidator;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var Converter|MockObject
     */
    private $converter;

    /**
     * @var FromOrder
     */
    private $fromOrder;

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
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListRepository = $this
            ->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = $this
            ->getMockBuilder(Converter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->fromOrder = $objectManager->getObject(
            FromOrder::class,
            [
                'requestValidator' => $this->requestValidator,
                'orderRepository' => $this->orderRepository,
                'requisitionListRepository' => $this->requisitionListRepository,
                'converter' => $this->converter,
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request,
                'messageManager' => $this->messageManager,
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
        $orderId = 1;
        $listId = 2;
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRefererUrl'])
            ->getMockForAbstractClass();
        $result->expects($this->atLeastOnce())->method('setRefererUrl')->willReturnSelf();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->request->expects($this->atLeastOnce())->method('getParam')->withConsecutive(['order_id'], ['list_id'])
            ->willReturnOnConsecutiveCalls($orderId, $listId);
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepository->expects($this->atLeastOnce())->method('get')->with($orderId)->willReturn($order);
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionList->expects($this->atLeastOnce())->method('getName')->willReturn('name');
        $this->requisitionListRepository->expects($this->atLeastOnce())->method('get')->with($listId)
            ->willReturn($requisitionList);
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->converter->expects($this->atLeastOnce())->method('addItems')->with($order, $requisitionList)
            ->willReturn([$requisitionListItem]);
        $this->messageManager->expects($this->atLeastOnce())->method('addSuccessMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->fromOrder->execute());
    }

    /**
     * Test for execute method with request validation errors.
     *
     * @return void
     */
    public function testExecuteWithRequestValidationErrors()
    {
        $result = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestValidator->expects($this->once())->method('getResult')->with($this->request)->willReturn($result);

        $this->assertEquals($result, $this->fromOrder->execute());
    }

    /**
     * Test for execute method with NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $orderId = 1;
        $listId = 2;
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRefererUrl'])
            ->getMockForAbstractClass();
        $result->expects($this->atLeastOnce())->method('setRefererUrl')->willReturnSelf();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->request->expects($this->atLeastOnce())->method('getParam')->withConsecutive(['order_id'], ['list_id'])
            ->willReturnOnConsecutiveCalls($orderId, $listId);
        $exception = new NoSuchEntityException(__('Exception'));
        $this->orderRepository->expects($this->atLeastOnce())->method('get')->willThrowException($exception);
        $this->messageManager->expects($this->atLeastOnce())->method('addErrorMessage');

        $this->assertInstanceOf(ResultInterface::class, $this->fromOrder->execute());
    }

    /**
     * Test for execute method with Exceptions.
     *
     * @dataProvider exceptionDataProvider
     * @param string $exceptionClass
     * @param string $errorMessage
     *
     * @return void
     */
    public function testExecuteWithLocalizedException($exceptionClass, $errorMessage)
    {
        $orderId = 1;
        $listId = 2;
        $this->requestValidator->expects($this->atLeastOnce())->method('getResult')->willReturn(null);
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRefererUrl'])
            ->getMockForAbstractClass();
        $result->expects($this->atLeastOnce())->method('setRefererUrl')->willReturnSelf();
        $this->resultFactory->expects($this->atLeastOnce())->method('create')->willReturn($result);
        $this->request->expects($this->atLeastOnce())->method('getParam')->withConsecutive(['order_id'], ['list_id'])
            ->willReturnOnConsecutiveCalls($orderId, $listId);
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->orderRepository->expects($this->atLeastOnce())->method('get')->willReturn($order);
        $exception = new $exceptionClass(__($errorMessage));
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListRepository->expects($this->atLeastOnce())->method('get')->with($listId)
            ->willReturn($requisitionList);
        $this->converter->expects($this->atLeastOnce())->method('addItems')->willThrowException($exception);
        $this->messageManager->expects($this->atLeastOnce())->method('addErrorMessage')->with($errorMessage);

        $this->assertInstanceOf(ResultInterface::class, $this->fromOrder->execute());
    }

    /**
     * Data provider for the testExecuteWithLocalizedException test
     *
     * @return array
     */
    public function exceptionDataProvider()
    {
        return [
            [LocalizedException::class, 'Localized error message'],
            [CouldNotSaveException::class, 'Could not save error message'],
            [NoSuchEntityException::class, 'No such entity message']
        ];
    }
}
