<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Company\Test\Unit\Controller\Customer;

use Magento\Company\Controller\Customer\Save;
use Magento\Company\Model\Action\SaveCustomer;
use Magento\Company\Model\Company\Structure;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for customer save controller.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends TestCase
{
    /**
     * @var SaveCustomer|MockObject
     */
    private $customerAction;

    /**
     * @var Structure|MockObject
     */
    private $structureManager;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Json|MockObject
     */
    private $resultJson;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CompanyContext|MockObject
     */
    private $companyContext;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Save
     */
    private $save;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->customerAction = $this->getMockBuilder(SaveCustomer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->structureManager = $this->getMockBuilder(Structure::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultJson = $this->getMockBuilder(Json::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyContext = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->save = $objectManagerHelper->getObject(
            Save::class,
            [
                'customerAction' => $this->customerAction,
                'structureManager' => $this->structureManager,
                '_request' => $this->request,
                'resultFactory' => $this->resultFactory,
                'logger' => $this->logger,
                'companyContext' => $this->companyContext,
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
        $customerId = 1;
        $customerData = ['customer_data'];
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['users' => [1, 5, 8]]);
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods(['__toArray'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerAction->expects($this->once())->method('update')->with($this->request)->willReturn($customer);
        $customer->expects($this->once())->method('__toArray')->willReturn($customerData);
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'ok',
                'message' => 'The customer was successfully updated.',
                'data' => $customerData,
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);

        $this->assertEquals($this->resultJson, $this->save->execute());
    }

    /**
     * Test for execute method with localized exception.
     *
     * @return void
     */
    public function testExecuteWithLocalizedException()
    {
        $customerId = 1;
        $exceptionMessage = 'Customer save error';
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['users' => [1, 5, 8]]);
        $this->customerAction->expects($this->once())->method('update')->with($this->request)
            ->willThrowException(new LocalizedException(__($exceptionMessage)));
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'error',
                'message' => $exceptionMessage,
                'payload' => [],
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);

        $this->assertEquals($this->resultJson, $this->save->execute());
    }

    /**
     * Test for execute method with generic exception.
     *
     * @return void
     */
    public function testExecuteWithGenericException()
    {
        $customerId = 1;
        $exception = new \Exception();
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['users' => [1, 5, 8]]);
        $this->customerAction->expects($this->once())
            ->method('update')->with($this->request)->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);
        $this->resultJson->expects($this->once())->method('setData')->with(
            [
                'status' => 'error',
                'message' => 'Something went wrong.',
                'payload' => [],
            ]
        )->willReturnSelf();
        $this->resultFactory->expects($this->once())->method('create')
            ->with(ResultFactory::TYPE_JSON)->willReturn($this->resultJson);

        $this->assertEquals($this->resultJson, $this->save->execute());
    }

    /**
     * Test execute method with InputMismatchException exception.
     *
     * @return void
     */
    public function testExecuteWithInputMismatchException()
    {
        $this->expectException('Magento\Framework\Exception\State\InputMismatchException');
        $this->expectExceptionMessage('You are not allowed to do this.');
        $customerId = 1;
        $this->request->expects($this->once())->method('getParam')->with('customer_id')->willReturn($customerId);
        $this->companyContext->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->structureManager->expects($this->once())
            ->method('getAllowedIds')
            ->with($customerId)
            ->willReturn(['users' => [5, 8]]);

        $this->save->execute();
    }
}
