<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Plugin\Sales\Controller\Order;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\AuthorizationInterface;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Company\Plugin\Sales\Controller\Order\ViewPlugin;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Order\View;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\Company\Plugin\Sales\Controller\Order\ViewPlugin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewPluginTest extends TestCase
{
    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authorization;

    /**
     * @var Structure|MockObject
     */
    private $companyStructure;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var ViewPlugin
     */
    private $viewPlugin;

    /**
     * @var View|MockObject
     */
    private $controller;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resultRedirectFactory =
            $this->getMockBuilder(RedirectFactory::class)
                ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->orderRepository = $this
            ->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->request = $this
            ->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->authorization = $this
            ->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyStructure = $this
            ->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userContext = $this
            ->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId'])
            ->getMockForAbstractClass();

        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->setMethods(['isCurrentUserCompanyUser', 'isModuleActive'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->viewPlugin = $objectManager->getObject(
            ViewPlugin::class,
            [
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'orderRepository' => $this->orderRepository,
                'request' => $this->request,
                'authorization' => $this->authorization,
                'companyStructure' => $this->companyStructure,
                'userContext' => $this->userContext,
                'companyContext' => $this->companyContext
            ]
        );
    }

    /**
     * Test aroundExecute() method.
     *
     * @return void
     */
    public function testAroundExecute()
    {
        $closure = function () {
            return;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn(0);
        $this->assertEquals($closure(), $this->viewPlugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method with exception.
     *
     * @return void
     */
    public function testAroundExecuteWithException()
    {
        $orderId = 1;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () use ($result) {
            return $result;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn(2);
        $this->request->expects($this->once())->method('getParam')->with('order_id')->willReturn($orderId);
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)
            ->willThrowException(new NoSuchEntityException());
        $this->assertEquals($result, $this->viewPlugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method with exception.
     *
     * @return void
     */
    public function testAroundExecuteWithNoOrderIdException()
    {
        $orderId = null;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () use ($result) {
            return $result;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn(2);
        $this->request->expects($this->once())->method('getParam')->with('order_id')->willReturn($orderId);
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)
            ->willThrowException(new InputException());
        $this->assertEquals($result, $this->viewPlugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method with disabled module.
     *
     * @return void
     */
    public function testAroundExecuteWithDisabledModule()
    {
        $orderId = 1;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () use ($result) {
            return $result;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn(2);
        $this->request->expects($this->once())->method('getParam')->with('order_id')->willReturn($orderId);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)->willReturn($order);
        $order->expects($this->once())->method('getCustomerId')->willReturn(3);
        $this->authorization->expects($this->once())
            ->method('isAllowed')->with('Magento_Sales::view_orders_sub')->willReturn(true);
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(false);
        $this->companyContext->expects($this->once())->method('isCurrentUserCompanyUser')->willReturn(true);
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resultRedirect->expects($this->once())->method('setPath')->with('company/accessdenied')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $this->assertEquals($resultRedirect, $this->viewPlugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method without view permissions.
     *
     * @return void
     */
    public function testAroundExecuteWithoutViewPermissions()
    {
        $orderId = 1;
        $customerId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () use ($result) {
            return $result;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($customerId);
        $this->request->expects($this->once())->method('getParam')->with('order_id')->willReturn($orderId);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)->willReturn($order);
        $order->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->companyContext->expects($this->atLeastOnce())->method('isCurrentUserCompanyUser')->willReturn(true);
        $this->authorization->expects($this->once())
            ->method('isAllowed')->with('Magento_Sales::view_orders')->willReturn(false);
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resultRedirect->expects($this->once())->method('setPath')->with('company/accessdenied')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $this->assertEquals($resultRedirect, $this->viewPlugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method without view children permissions.
     *
     * @return void
     */
    public function testAroundExecuteWithoutViewChildrenPermissions()
    {
        $orderId = 1;
        $customerId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () use ($result) {
            return $result;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($customerId);
        $this->request->expects($this->once())->method('getParam')->with('order_id')->willReturn($orderId);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)->willReturn($order);
        $order->expects($this->once())->method('getCustomerId')->willReturn(3);
        $this->authorization->expects($this->atLeastOnce())->method('isAllowed')
            ->withConsecutive(['Magento_Sales::view_orders_sub'], ['Magento_Sales::view_orders'])->willReturn(true);
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(true);
        $this->companyContext->expects($this->atLeastOnce())->method('isCurrentUserCompanyUser')->willReturn(false);
        $this->companyStructure->expects($this->once())
            ->method('getAllowedChildrenIds')->with($customerId)->willReturn([4, 5]);
        $resultRedirect = $this->getMockBuilder(Redirect::class)
            ->setMethods(['setPath'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resultRedirect->expects($this->once())->method('setPath')->with('noroute')->willReturnSelf();
        $this->resultRedirectFactory->expects($this->once())->method('create')->willReturn($resultRedirect);
        $this->assertEquals($resultRedirect, $this->viewPlugin->aroundExecute($this->controller, $closure));
    }

    /**
     * Test aroundExecute() method with result.
     *
     * @return void
     */
    public function testAroundExecuteWithResult()
    {
        $orderId = 1;
        $customerId = 2;
        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $closure = function () use ($result) {
            return $result;
        };
        $this->userContext->expects($this->atLeastOnce())->method('getUserId')->willReturn($customerId);
        $this->request->expects($this->once())->method('getParam')->with('order_id')->willReturn($orderId);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository->expects($this->once())->method('get')->with($orderId)->willReturn($order);
        $order->expects($this->once())->method('getCustomerId')->willReturn(3);
        $this->authorization->expects($this->atLeastOnce())->method('isAllowed')
            ->withConsecutive(['Magento_Sales::view_orders_sub'], ['Magento_Sales::view_orders'])->willReturn(true);
        $this->companyContext->expects($this->once())->method('isModuleActive')->willReturn(true);
        $this->companyContext->expects($this->atLeastOnce())->method('isCurrentUserCompanyUser')->willReturn(true);
        $this->companyStructure->expects($this->once())
            ->method('getAllowedChildrenIds')->with($customerId)->willReturn([3, 4, 5]);
        $this->assertEquals($result, $this->viewPlugin->aroundExecute($this->controller, $closure));
    }
}
