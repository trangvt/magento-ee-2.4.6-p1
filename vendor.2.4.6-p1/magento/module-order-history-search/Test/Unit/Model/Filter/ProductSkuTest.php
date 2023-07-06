<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\ProductSku;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductSkuTest.
 *
 * Unit test for Product SKU filter.
 */
class ProductSkuTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ItemRepository|MockObject
     */
    private $itemRepositoryMock;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilderMock;

    /**
     * @var FilterGroupBuilder|MockObject
     */
    private $filterGroupBuilderMock;

    /**
     * @var SearchCriteriaBuilderFactory|MockObject
     */
    private $searchCriteriaBuilderFactoryMock;

    /**
     * @var ProductSku
     */
    private $productSkuModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->searchCriteriaBuilderFactoryMock = $this
            ->getMockBuilder(SearchCriteriaBuilderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->itemRepositoryMock = $this
            ->getMockBuilder(ItemRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();

        $this->filterBuilderMock = $this
            ->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setField',
                    'setValue',
                    'setConditionType',
                    'create',
                ]
            )
            ->getMock();

        $this->filterGroupBuilderMock = $this
            ->getMockBuilder(FilterGroupBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addFilter',
                    'create',
                ]
            )
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->productSkuModel = $this->objectManagerHelper->getObject(
            ProductSku::class,
            [
                'searchCriteriaBuilderFactory' => $this->searchCriteriaBuilderFactoryMock,
                'filterBuilder' => $this->filterBuilderMock,
                'filterGroupBuilder' => $this->filterGroupBuilderMock,
                'itemRepository' => $this->itemRepositoryMock,
            ]
        );
    }

    /**
     * Test applyFilter() method.
     *
     * @return void
     */
    public function testApplyFilter()
    {
        $collectionMock = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter'])
            ->getMock();

        $value = 'shirt';

        $searchCriteriaMock = $this
            ->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->setMethods(['setFilterGroups'])
            ->getMock();

        $searchCriteriaBuilderMock = $this
            ->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'create',
                    'addFilter',
                ]
            )
            ->getMock();

        $this->searchCriteriaBuilderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilderMock);

        $filterMock = $this
            ->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteriaMock);

        /** Filters */
        $this->filterBuilderMock->expects($this->exactly(2))->method('setField')->willReturnSelf();
        $this->filterBuilderMock->expects($this->exactly(2))->method('setValue')->willReturnSelf();
        $this->filterBuilderMock->expects($this->exactly(2))->method('setConditionType')->willReturnSelf();
        $this->filterBuilderMock->expects($this->exactly(2))->method('create')->willReturn($filterMock);

        $this->filterGroupBuilderMock->expects($this->exactly(2))->method('addFilter')->willReturnSelf();
        $this->filterGroupBuilderMock->expects($this->once())->method('create')->willReturn([]);
        /** Filters End */

        $this->itemRepositoryMock->expects($this->once())->method('getList')->willReturn([]);

        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => []]);

        $this->assertSame(
            $collectionMock,
            $this->productSkuModel->applyFilter($collectionMock, $value)
        );
    }
}
