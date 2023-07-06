<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\ProductItem\Price;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationListInterface;
use Magento\Catalog\Api\Data\PriceUpdateResultInterface;
use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\Catalog\Api\TierPriceStorageInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\Consumer;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\PriceProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Magento/SharedCatalog/Model/ResourceModel/ProductItem/Price/Consumer class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConsumerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManagerMock;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializerMock;

    /**
     * @var TierPriceStorageInterface|MockObject
     */
    private $tierPriceStorage;

    /**
     * @var OperationInterface|MockObject
     */
    private $operation;

    /**
     * @var TierPriceInterface|MockObject
     */
    private $tierPrice;

    /**
     * @var PriceProcessor|MockObject
     */
    private $priceProcessor;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->entityManagerMock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceStorage = $this->getMockBuilder(TierPriceStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->operation = $this->getMockBuilder(OperationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPrice = $this->getMockBuilder(TierPriceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceProcessor = $this
            ->getMockBuilder(PriceProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->consumer = $objectManagerHelper->getObject(
            Consumer::class,
            [
                'logger' => $this->loggerMock,
                'entityManager' => $this->entityManagerMock,
                'serializer' => $this->serializerMock,
                'tierPriceStorage' => $this->tierPriceStorage,
                'priceProcessor' => $this->priceProcessor
            ]
        );
    }

    /**
     * Test for processOperation().
     *
     * @param array $unserializedData
     * @return void
     * @dataProvider processOperationsDataProvider
     */
    public function testProcessOperations(array $unserializedData)
    {
        $serializedData = json_encode($unserializedData);
        $this->operation->expects($this->atLeastOnce())->method('getSerializedData')->willReturn($serializedData);
        $this->serializerMock->expects($this->atLeastOnce())->method('unserialize')->willReturn($unserializedData);
        $priceUpdateResult = $this->getMockBuilder(PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('delete')
            ->with([])
            ->willReturn([$priceUpdateResult]);
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('update')
            ->willReturn([$priceUpdateResult]);
        $this->priceProcessor->expects($this->atLeastOnce())->method('createPricesUpdate')->willReturn([]);
        $this->priceProcessor->expects($this->atLeastOnce())->method('createPricesDelete')->willReturn([]);
        $this->operation->expects($this->atLeastOnce())
            ->method('setStatus')
            ->willReturnSelf();
        $this->operation->expects($this->atLeastOnce())
            ->method('setResultMessage')
            ->willReturnSelf();
        $this->operation->expects($this->atLeastOnce())->method('setResultMessage')->willReturnSelf();
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMockForAbstractClass();
        $operationList->expects($this->atLeastOnce())->method('getItems')->willReturn([$this->operation]);
        $this->entityManagerMock->expects($this->atLeastOnce())->method('save')->with($operationList);

        $this->consumer->processOperations($operationList);
    }

    /**
     * Test for processOperations() with Exception during changing operation status.
     *
     * @param array $unserializedData
     * @return void
     * @dataProvider processOperationsDataProvider
     */
    public function testProcessOperationWhenExceptionOccurs(array $unserializedData)
    {
        $exception = new \Exception('Exception message.');
        $serializedData = json_encode($unserializedData);
        $this->operation->expects($this->atLeastOnce())->method('getSerializedData')->willReturn($serializedData);
        $this->serializerMock->expects($this->atLeastOnce())->method('unserialize')->willReturn($unserializedData);
        $priceUpdateResult = $this->getMockBuilder(PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('delete')
            ->with([])
            ->willReturn([$priceUpdateResult]);
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('update')
            ->willReturn([$priceUpdateResult]);
        $this->priceProcessor->expects($this->atLeastOnce())->method('createPricesUpdate')->willReturn([]);
        $this->priceProcessor->expects($this->atLeastOnce())->method('createPricesDelete')->willReturn([]);
        $this->operation->expects($this->atLeastOnce())
            ->method('setStatus')
            ->willReturnSelf();
        $this->operation->expects($this->atLeastOnce())
            ->method('setResultMessage')
            ->willReturnSelf();
        $this->operation->expects($this->atLeastOnce())->method('setResultMessage')->willReturnSelf();
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMockForAbstractClass();
        $operationList->expects($this->atLeastOnce())->method('getItems')->willReturn([$this->operation]);
        $this->entityManagerMock->expects($this->once())
            ->method('save')->with($operationList)->willThrowException($exception);

        $this->consumer->processOperations($operationList);
    }

    /**
     * Test for processOperations() with CouldNotSaveException.
     *
     * @return void
     */
    public function testProcessOperationsWithCouldNotSaveException()
    {
        $exception = new CouldNotSaveException(__('Exception message.'));
        $priceUpdateResult = $this->getMockBuilder(PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('delete')
            ->with([])
            ->willReturn([$priceUpdateResult]);
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('update')
            ->willThrowException($exception);
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $operationList->expects($this->atLeastOnce())->method('getItems')->willReturn([]);
        $this->entityManagerMock->expects($this->atLeastOnce())->method('save')->with($operationList);

        $this->consumer->processOperations($operationList);
    }

    /**
     * Test for processOperations() with CouldNotDeleteException.
     *
     * @return void
     */
    public function testProcessOperationsWithCouldNotDeleteException()
    {
        $exceptionMessage = 'Exception message.';
        $exception = new CouldNotDeleteException(__($exceptionMessage));
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('delete')
            ->with([])
            ->willThrowException($exception);
        $this->tierPriceStorage->expects($this->never())->method('update');
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $operationList->expects($this->atLeastOnce())->method('getItems')->willReturn([]);
        $this->entityManagerMock->expects($this->atLeastOnce())->method('save')->with($operationList);

        $this->consumer->processOperations($operationList);
    }

    /**
     * Test for processOperations() with Exception.
     *
     * @return void
     */
    public function testProcessOperationsWithException()
    {
        $exceptionMessage = 'Exception message.';
        $exception = new \Exception($exceptionMessage);
        $priceUpdateResult = $this->getMockBuilder(PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tierPriceStorage->expects($this->atLeastOnce())
            ->method('delete')
            ->with([])
            ->willThrowException($exception);
        $this->tierPriceStorage->expects($this->never())
            ->method('update')
            ->with([$this->tierPrice])
            ->willReturn([$priceUpdateResult]);
        $operationList = $this->getMockBuilder(OperationListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $operationList->expects($this->atLeastOnce())->method('getItems')->willReturn([]);
        $this->entityManagerMock->expects($this->atLeastOnce())->method('save')->with($operationList);

        $this->consumer->processOperations($operationList);
    }

    /**
     * Data provider for processOperations method.
     *
     * @return array
     */
    public function processOperationsDataProvider()
    {
        return [
            [
                [
                    'shared_catalog_id' => 1,
                    'entity_id' => 2,
                    'prices' => [
                        [
                            'qty' => 1,
                            'value_type' => 'percent',
                            'percentage_value' => 50,
                            'website_id' => 1,
                        ]
                    ],
                    'entity_link' => 'http://example.com',
                    'product_sku' => 'test_sku',
                    'customer_group' => 3,
                ]
            ],
            [
                [
                    'shared_catalog_id' => 1,
                    'entity_id' => 2,
                    'prices' => [
                        [
                            'qty' => 1,
                            'value_type' => 'fixed',
                            'price' => 20,
                            'website_id' => 1,
                        ]
                    ],
                    'entity_link' => 'http://example.com',
                    'product_sku' => 'test_sku',
                    'customer_group' => 3,
                ]
            ],
        ];
    }
}
