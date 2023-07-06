<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\Sales;

use Magento\CompanyCredit\Model\Sales\OrderLocator;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for \Magento\CompanyCredit\Model\Sales\OrderLocator class.
 */
class OrderLocatorTest extends TestCase
{
    /**
     * @var OrderLocator
     */
    private $orderLocator;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderLocator = (new ObjectManager($this))->getObject(
            OrderLocator::class,
            [
                'orderRepository' => $this->orderRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock
            ]
        );
    }

    /**
     * Test for `getOrderByIncrementId` method.
     *
     * @return void
     */
    public function testGetOrderByIncrementId()
    {
        $incrementId = 1;

        $order = $this->getMockBuilder(OrderInterface::class)
            ->getMockForAbstractClass();

        $searchResult = $this->getMockBuilder(OrderSearchResultInterface::class)
            ->getMockForAbstractClass();
        $searchResult->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([$order]);

        $this->mockGetList($searchResult);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with(OrderInterface::INCREMENT_ID, $incrementId)
            ->willReturnSelf();

        $this->assertEquals(
            $order,
            $this->orderLocator->getOrderByIncrementId($incrementId)
        );
    }

    /**
     * Test for getOrderByIncrementId method with exception.
     *
     * @return void
     */
    public function testGetOrderByIncrementIdWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('No such entity with increment_id = 1');
        $incrementId = 1;

        $searchResult = $this->getMockBuilder(OrderSearchResultInterface::class)
            ->getMockForAbstractClass();
        $searchResult->expects($this->atLeastOnce())
            ->method('getItems')
            ->willReturn([]);

        $this->mockGetList($searchResult);
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilter')
            ->with(OrderInterface::INCREMENT_ID, $incrementId)
            ->willReturnSelf();

        $this->orderLocator->getOrderByIncrementId($incrementId);
    }

    /**
     * Mock getList.
     *
     * @param MockObject $result
     * @return void
     */
    private function mockGetList(MockObject $result)
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($searchCriteria);

        $this->orderRepositoryMock->expects($this->atLeastOnce())
            ->method('getList')
            ->willReturn($result);
    }
}
