<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\OrderAddressProvider;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderAddressSearchResultInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderAddressProviderTest.
 *
 * Unit test for Address provider.
 */
class OrderAddressProviderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CollectionFactoryInterface|MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var OrderAddressRepositoryInterface|MockObject
     */
    private $orderAddressRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var OrderAddressProvider
     */
    private $orderAddressProviderModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->orderCollectionFactoryMock = $this
            ->getMockBuilder(CollectionFactoryInterface::class)
            ->getMock();

        $this->orderAddressRepositoryMock = $this
            ->getMockBuilder(OrderAddressRepositoryInterface::class)
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFilter', 'create'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->orderAddressProviderModel = $this->objectManagerHelper->getObject(
            OrderAddressProvider::class,
            [
                'orderCollectionFactory' => $this->orderCollectionFactoryMock,
                'orderAddressRepository' => $this->orderAddressRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock
            ]
        );
    }

    /**
     * Test applyFilter() method.
     *
     * @return void
     */
    public function testGetByCustomerId(): void
    {
        $customerId = 1;

        $orderCollectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToSelect', 'getAllIds'])
            ->getMock();

        $orderSearchResultMock = $this->getMockBuilder(OrderAddressSearchResultInterface::class)
            ->getMock();

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderCollectionMock->expects($this->once())->method('addFieldToSelect')
            ->with(OrderInterface::ENTITY_ID)->willReturnSelf();
        $orderCollectionMock->expects($this->once())->method('getAllIds')->willReturn([]);
        $this->orderCollectionFactoryMock->expects($this->once())->method('create')->willReturn($orderCollectionMock);

        $this->searchCriteriaBuilderMock->method('addFilter')
            ->withConsecutive(
                [OrderAddressInterface::ADDRESS_TYPE, Address::TYPE_SHIPPING],
                [OrderAddressInterface::PARENT_ID, [], 'in']
            )
            ->willReturnOnConsecutiveCalls($this->searchCriteriaBuilderMock, $this->searchCriteriaBuilderMock);

        $orderSearchResultMock->expects($this->once())->method('getItems')->willReturn([]);
        $this->searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteriaMock);
        $this->orderAddressRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($orderSearchResultMock);

        $this->assertEquals([], $this->orderAddressProviderModel->getByCustomerId($customerId));
    }
}
