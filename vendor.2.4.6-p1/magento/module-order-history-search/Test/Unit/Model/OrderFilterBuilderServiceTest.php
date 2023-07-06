<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\FilterInterface;
use Magento\OrderHistorySearch\Model\Filter\FilterPool;
use Magento\OrderHistorySearch\Model\OrderFilterBuilderService;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Theme\Block\Html\Pager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderFilterBuilderServiceTest.
 *
 * Unit test for Filter builder for order search collection.
 */
class OrderFilterBuilderServiceTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var FilterPool|MockObject
     */
    private $filterPoolMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var Pager|MockObject
     */
    private $pagerMock;

    /**
     * @var OrderFilterBuilderService
     */
    private $orderFilterBuilderServiceModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setPageSize', 'setCurPage', 'setFlag'])
            ->getMock();

        $this->collectionMock->expects($this->once())->method('setFlag')->with('advanced-filtering', true);

        $this->filterPoolMock = $this->getMockBuilder(FilterPool::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->pagerMock = $this->getMockBuilder(Pager::class)->disableOriginalConstructor()
            ->onlyMethods(['getPageVarName', 'getLimitVarName'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->orderFilterBuilderServiceModel = $this->objectManagerHelper->getObject(
            OrderFilterBuilderService::class,
            [
                'filterPool' => $this->filterPoolMock,
                'pager' => $this->pagerMock
            ]
        );
    }

    /**
     * Test applyOrderFilters() method with every parameter defined.
     *
     * @return void
     */
    public function testApplyOrderFiltersWithFullParams(): void
    {
        $collectionMock = $this->collectionMock;

        $filterParams = [
            'invoice-number' => '003',
            'order-date-from' => '12.12.2017',
            'order-date-to' => '12.12.2017',
            'order-number' => '111',
            'order-status' => 'complete',
            'order-total-min' => '10',
            'order-total-max' => '100',
            'product-name-sku' => 'product'
        ];

        $paginationParams = [
            'p' => '1',
            'limit' => '10'
        ];

        $this->pagerMock->expects($this->any())->method('getPageVarName')->willReturn('p');
        $this->pagerMock->expects($this->any())->method('getLimitVarName')->willReturn('limit');

        $collectionMock
            ->expects($this->once())
            ->method('setPageSize')
            ->with((int) $paginationParams['limit'])
            ->willReturnSelf();

        $collectionMock
            ->expects($this->once())
            ->method('setCurPage')
            ->with((int) $paginationParams['p'])
            ->willReturnSelf();

        $filterMock = $this->getMockForAbstractClass(FilterInterface::class);

        $expectedFilterCallCount = count($filterParams);

        $this->filterPoolMock
            ->expects($this->exactly($expectedFilterCallCount))
            ->method('get')
            ->willReturn($filterMock);

        $filterMock
            ->expects($this->exactly($expectedFilterCallCount))
            ->method('applyFilter')
            ->willReturn($collectionMock);

        $withArgs = $willReturnArgs = [];

        foreach ($filterParams as $value) {
            $withArgs[] = [$collectionMock, $value];
            $willReturnArgs[] = $collectionMock;
        }
        $filterMock
            ->method('applyFilter')
            ->withConsecutive(...$withArgs)
            ->willReturnOnConsecutiveCalls(...$willReturnArgs);

        $this->orderFilterBuilderServiceModel->applyOrderFilters(
            $collectionMock,
            array_merge($filterParams, $paginationParams)
        );
    }

    /**
     * Test applyOrderFilters() method with every parameter empty.
     *
     * @return void
     */
    public function testApplyOrderFilterWithEmptyParams(): void
    {
        $collectionMock = $this->collectionMock;

        $filterParams = [
            'invoice-number' => '',
            'order-date-from' => '',
            'order-date-to' => '',
            'order-number' => '',
            'order-status' => '',
            'order-total-min' => '',
            'order-total-max' => '',
            'product-name-sku' => ''
        ];

        $paginationParams = [
            'p' => '',
            'limit' => ''
        ];

        $this->pagerMock->expects($this->any())->method('getPageVarName')->willReturn('p');
        $this->pagerMock->expects($this->any())->method('getLimitVarName')->willReturn('limit');

        $collectionMock
            ->expects($this->never())
            ->method('setPageSize')
            ->with((int) $paginationParams['limit'])
            ->willReturnSelf();

        $collectionMock
            ->expects($this->never())
            ->method('setCurPage')
            ->with((int) $paginationParams['p'])
            ->willReturnSelf();

        $filterMock = $this->getMockForAbstractClass(FilterInterface::class);

        $this->filterPoolMock
            ->expects($this->never())
            ->method('get')
            ->willReturn($filterMock);

        $filterMock
            ->expects($this->never())
            ->method('applyFilter')
            ->willReturn($collectionMock);

        $this->orderFilterBuilderServiceModel->applyOrderFilters(
            $collectionMock,
            array_merge($filterParams, $paginationParams)
        );
    }
}
