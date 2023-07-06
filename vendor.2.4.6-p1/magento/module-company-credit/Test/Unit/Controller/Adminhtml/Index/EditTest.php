<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Controller\Adminhtml\Index;

use Exception;
use Magento\CompanyCredit\Controller\Adminhtml\Index\Edit;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\HistoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditTest extends TestCase
{
    /**
     * @var HistoryRepositoryInterface|MockObject
     */
    private $historyRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ResultFactory|MockObject
     */
    private $resultFactory;

    /**
     * @var Edit
     */
    private $edit;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->historyRepository = $this->createMock(
            HistoryRepositoryInterface::class
        );
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->resultFactory = $this->createPartialMock(
            ResultFactory::class,
            ['create']
        );
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->getMockForAbstractClass();

        $serializer = $this->createMock(Json::class);
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $objectManager = new ObjectManager($this);
        $this->edit = $objectManager->getObject(
            Edit::class,
            [
                'historyRepository' => $this->historyRepository,
                'logger' => $this->logger,
                'resultFactory' => $this->resultFactory,
                '_request' => $this->request,
                'serializer' => $serializer
            ]
        );
    }

    /**
     * Test for method execute.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $historyId = 2;
        $historyComments = ['History Comment'];
        $reimburseBalance = $this->prepareMocks($historyId);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);

        $history = $this->getMockForAbstractClass(HistoryInterface::class);
        $this->historyRepository->expects($this->once())
            ->method('get')
            ->with($historyId)
            ->willReturn($history);
        $history->expects($this->once())
            ->method('setPurchaseOrder')
            ->with($reimburseBalance['purchase_order'])
            ->willReturnSelf();
        $history->expects($this->exactly(2))
            ->method('getComment')
            ->willReturn(json_encode($historyComments));
        $history->expects($this->once())
            ->method('setComment')
            ->with(json_encode($historyComments + ['custom' => $reimburseBalance['credit_comment']]))
            ->willReturnSelf();
        $this->historyRepository->expects($this->once())
            ->method('save')
            ->willReturn($history);
        $result->expects($this->once())
            ->method('setData')
            ->with(['status' => 'success'])
            ->willReturnSelf();

        $this->assertEquals($result, $this->edit->execute());
    }

    /**
     * Test for method execute with NoSuchEntityException.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException(): void
    {
        $historyId = null;
        $this->prepareMocks($historyId);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);
        $this->historyRepository->expects($this->once())
            ->method('get')
            ->with($historyId)
            ->willThrowException(new NoSuchEntityException());
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'error' => __('History record no longer exists.')
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($result, $this->edit->execute());
    }

    /**
     * Test for method execute with CouldNotSaveException.
     *
     * @return void
     */
    public function testExecuteWithCouldNotSaveException(): void
    {
        $historyId = 2;
        $historyComments = ['History Comment'];
        $exceptionMessage = 'Could not save company limit.';
        $reimburseBalance = $this->prepareMocks($historyId);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);

        $history = $this->getMockForAbstractClass(HistoryInterface::class);
        $this->historyRepository->expects($this->once())
            ->method('get')
            ->with($historyId)
            ->willReturn($history);
        $history->expects($this->once())
            ->method('setPurchaseOrder')
            ->with($reimburseBalance['purchase_order'])
            ->willReturnSelf();
        $history->expects($this->exactly(2))
            ->method('getComment')
            ->willReturn(json_encode($historyComments));
        $history->expects($this->once())
            ->method('setComment')
            ->with(json_encode($historyComments + ['custom' => $reimburseBalance['credit_comment']]))
            ->willReturnSelf();
        $this->historyRepository->expects($this->once())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(__($exceptionMessage)));
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'error' => $exceptionMessage
                ]
            )
            ->willReturnSelf();

        $this->assertEquals($result, $this->edit->execute());
    }

    /**
     * Test for method execute with Exception.
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        $historyId = null;
        $exception = new Exception('Some exception message');
        $this->prepareMocks($historyId);

        $result = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMockForAbstractClass();
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($result);
        $this->historyRepository->expects($this->once())
            ->method('get')
            ->with($historyId)
            ->willThrowException($exception);
        $result->expects($this->once())
            ->method('setData')
            ->with(
                [
                    'status' => 'error',
                    'error' => __('Something went wrong. Please try again later.')
                ]
            )
            ->willReturnSelf();
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception)
            ->willReturn(null);

        $this->assertEquals($result, $this->edit->execute());
    }

    /**
     * Prepare mocks.
     *
     * @param int|null $historyId
     *
     * @return array
     */
    private function prepareMocks(?int $historyId): array
    {
        $reimburseBalance = [
            'purchase_order' => 'O123',
            'credit_comment' => 'Some Comment',
        ];
        $this->request->expects($this->any())
            ->method('getParam')
            ->withConsecutive(
                ['reimburse_balance'],
                ['history_id'],
                ['history_id']
            )
            ->willReturnOnConsecutiveCalls(
                $reimburseBalance,
                $historyId,
                $historyId
            );

        return $reimburseBalance;
    }
}
