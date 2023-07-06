<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Customer;

use Magento\Company\Controller\Customer\Create;
use Magento\Company\Model\Action\SaveCustomer;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for customer create controller.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Create
     */
    private $create;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContextMock;

    /**
     * @var Structure|MockObject
     */
    private $structureManagerMock;

    /**
     * @var SaveCustomer|MockObject
     */
    private $customerActionMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyContextMock = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureManagerMock = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerActionMock = $this->getMockBuilder(SaveCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->create = $this->objectManagerHelper->getObject(
            Create::class,
            [
                'companyContext' => $this->companyContextMock,
                'structureManager' => $this->structureManagerMock,
                'customerAction' => $this->customerActionMock,
                '_request' => $this->requestMock,
                'resultFactory' => $this->resultFactoryMock
            ]
        );
    }

    /**
     * Test for Execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $customerId = 1;
        $this->requestMock->expects($this->once())->method('getParam')->with('target_id')->willReturn(1);
        $this->companyContextMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManagerMock->expects($this->once())->method('getAllowedIds')->with($customerId)
            ->willReturn([
                'structures' => [$customerId]
            ]);
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['__toArray'])
            ->getMockForAbstractClass();
        $this->customerActionMock->expects($this->once())->method('create')->with($this->requestMock)
            ->willReturn($customerMock);
        $customerMock->expects($this->once())->method('__toArray')->willReturn([]);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')
            ->with([
                'status' => 'ok',
                'message' => __('The customer was successfully created.'),
                'data' => []
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);

        $this->create->execute();
    }

    /**
     * Test for Execute method when customer ID is not allowed.
     *
     * @return void
     */
    public function testExecuteWhenCustomerIdNotAllowed()
    {
        $customerId = 1;
        $this->requestMock->expects($this->once())->method('getParam')->with('target_id')->willReturn(1);
        $this->companyContextMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManagerMock->expects($this->once())->method('getAllowedIds')->with($customerId)
            ->willReturn([
                'structures' => [2]
            ]);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')
            ->with([
                'status' => 'error',
                'message' => __('You are not allowed to do this.'),
                'payload' => []
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);

        $this->create->execute();
    }

    /**
     * Test for Execute method when target_id parameter is absent.
     *
     * @return void
     */
    public function testExecuteWhenTargetIdAbsent()
    {
        $customerId = 1;
        $this->requestMock->expects($this->once())->method('getParam')->with('target_id')->willReturn(null);
        $this->companyContextMock->expects($this->any())->method('getCustomerId')->willReturn($customerId);
        $this->structureManagerMock->expects($this->once())->method('getAllowedIds')->with($customerId)
            ->willReturn([
                'structures' => [$customerId]
            ]);
        $this->structureManagerMock->expects($this->once())->method('getStructureByCustomerId')->with($customerId)
            ->willReturn(null);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')
            ->with([
                'status' => 'error',
                'message' => __('Cannot create the customer.'),
                'payload' => []
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);

        $this->create->execute();
    }

    /**
     * Test for Execute method when InputMismatchException is thrown.
     *
     * @return void
     */
    public function testExecuteWithInputMismatchException()
    {
        $exception = new InputMismatchException(__('Exception message'));
        $this->prepareExcepionMocks($exception);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')
            ->with([
                'status' => 'error',
                'message' => __(
                    'A user with this email address already exists in the system. '
                    . 'Enter a different email address to create this user.'
                ),
                'payload' => [
                    'field' => 'email'
                ]
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);

        $this->create->execute();
    }

    /**
     * Test for Execute method when LocalizedException is thrown.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $exception = new LocalizedException(__('Exception message'));
        $this->prepareExcepionMocks($exception);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')
            ->with([
                'status' => 'error',
                'message' => __('Exception message'),
                'payload' => []
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);

        $this->create->execute();
    }

    /**
     * Test for Execute method when Exception is thrown.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $exception = new \Exception('Something went wrong.');
        $this->prepareExcepionMocks($exception);
        $resultJson = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultJson->expects($this->once())->method('setData')
            ->with([
                'status' => 'error',
                'message' => __('Something went wrong.'),
                'payload' => []
            ])
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($resultJson);

        $this->create->execute();
    }

    /**
     * Prepare mocks for tests with Exceptions.
     *
     * @param \Exception $exception
     * @return void
     */
    private function prepareExcepionMocks(\Exception $exception)
    {
        $customerId = 1;
        $this->requestMock->expects($this->once())->method('getParam')->with('target_id')->willReturn(1);
        $this->companyContextMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManagerMock->expects($this->once())->method('getAllowedIds')->with($customerId)
            ->willReturn([
                'structures' => [$customerId]
            ]);

        $this->customerActionMock->expects($this->once())->method('create')->with($this->requestMock)
            ->willThrowException($exception);
    }
}
