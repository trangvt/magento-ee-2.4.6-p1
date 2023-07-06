<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Order\Status;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Order\Status\DataProvider;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class DataProviderTest.
 *
 * Unit test for order status options data provider.
 */
class DataProviderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var StoreManager|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'joinStates',
                    'getSelect',
                    'addAttributeToFilter',
                    'toOptionArray',
                    'getMainTable',
                    'getTable'
                ]
            )->getMock();

        $this->collectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->storeManagerMock = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMock();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMockForAbstractClass();

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->dataProvider = $this->objectManagerHelper
            ->getObject(
                DataProvider::class,
                [
                    'storeManager' => $this->storeManagerMock,
                    'collectionFactory' => $this->collectionFactoryMock
                ]
            );
    }

    /**
     * Test option provider for existing store.
     */
    public function testGetOrderStatusOptions()
    {
        $this->storeMock->expects($this->any())->method('getId')->willReturn(1);
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['joinLeft', 'from', 'group', 'order'])
            ->getMock();
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('from')->willReturnSelf();
        $selectMock->expects($this->once())->method('joinLeft')->willReturnSelf();
        $selectMock->expects($this->once())->method('group')->willReturnSelf();
        $selectMock->expects($this->any())->method('order')->willReturnSelf();
        $this->collectionMock->expects($this->once())->method('toOptionArray')->willReturn(['state' => 'label']);
        $this->assertEquals(['state' => 'label'], $this->dataProvider->getOrderStatusOptions());
    }

    /**
     * Test option provider for existing store regardless storefront visibility.
     */
    public function testGetOrderStatusOptionsAll()
    {
        $this->storeMock->expects($this->any())->method('getId')->willReturn(1);
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['joinLeft', 'from', 'group', 'order'])
            ->getMock();
        $this->collectionMock->expects($this->never())->method('addAttributeToFilter');
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturn($selectMock);
        $selectMock->expects($this->once())->method('from')->willReturnSelf();
        $selectMock->expects($this->once())->method('joinLeft')->willReturnSelf();
        $selectMock->expects($this->once())->method('group')->willReturnSelf();
        $selectMock->expects($this->any())->method('order')->willReturnSelf();
        $this->collectionMock->expects($this->once())->method('toOptionArray')->willReturn(['state' => 'label']);
        $this->assertEquals(['state' => 'label'], $this->dataProvider->getOrderStatusOptions(false));
    }

    /**
     * Test options provider out of store scope.
     */
    public function testGetOrderStatusOptionsNoStore()
    {
        $this->storeManagerMock->expects($this->once())->method('getStore')->willThrowException(
            new NoSuchEntityException()
        );
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'joinLeft', 'group', 'order'])
            ->getMock();
        $this->collectionMock->expects($this->any())->method('getSelect')->willReturn($selectMock);
        $selectMock->expects($this->never())->method('from')->willReturnSelf();
        $selectMock->expects($this->never())->method('joinLeft')->willReturnSelf();
        $selectMock->expects($this->once())->method('group')->willReturnSelf();
        $selectMock->expects($this->once())->method('order')->willReturnSelf();
        $this->collectionMock->expects($this->once())->method('toOptionArray')->willReturn(['state' => 'label']);
        $this->assertEquals(['state' => 'label'], $this->dataProvider->getOrderStatusOptions());
    }
}
