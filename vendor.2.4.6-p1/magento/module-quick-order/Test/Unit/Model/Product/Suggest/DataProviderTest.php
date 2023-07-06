<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickOrder\Test\Unit\Model\Product\Suggest;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\QuickOrder\Model\FulltextSearch;
use Magento\QuickOrder\Model\Product\Suggest\DataProvider as SuggestDataProvider;
use Magento\QuickOrder\Model\ResourceModel\Product\Suggest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Data Provider for Quick Order auto-suggest object.
 */
class DataProviderTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SuggestDataProvider
     */
    private $suggestDataProvider;

    /**
     * @var FulltextSearch|MockObject
     */
    private $fulltextSearchMock;

    /**
     * @var Suggest|MockObject
     */
    private $suggestResourceMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * Result limit parameter for DataProvider constructor.
     *
     * @var int
     */
    private $resultLimit;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultLimit = 2;

        $this->fulltextSearchMock = $this->getMockBuilder(FulltextSearch::class)
            ->disableOriginalConstructor()
            ->setMethods(['search'])
            ->getMock();

        $this->suggestResourceMock = $this->getMockBuilder(Suggest::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareProductCollection'])
            ->getMock();

        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->suggestDataProvider = $this->objectManagerHelper->getObject(
            SuggestDataProvider::class,
            [
                'collectionFactory' => $this->collectionFactoryMock,
                'fulltextSearch' => $this->fulltextSearchMock,
                'suggestResource' => $this->suggestResourceMock,
                'resultLimit' => $this->resultLimit
            ]
        );
    }

    /**
     * Test for getItems() method.
     *
     * @param array $items
     * @param array $expectedResult
     * @dataProvider getItemsDataProvider
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testGetItems(array $items, array $expectedResult)
    {
        $query = 'sku-2';
        $searchResult = $this->getMockBuilder(\Magento\Framework\Api\Search\SearchResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fulltextSearchMock->expects($this->atLeastOnce())->method('search')->willReturn($searchResult);
        $searchResult->expects($this->atLeastOnce())->method('getItems')->willReturn($items);
        $collectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'load',
                'getItems'
            ])
            ->getMock();
        $this->collectionFactoryMock->expects($this->atLeastOnce())->method('create')->willReturn($collectionMock);
        $this->suggestResourceMock->expects($this->atLeastOnce())->method('prepareProductCollection')
            ->with($collectionMock, $searchResult, $this->resultLimit, $query)
            ->willReturn($collectionMock);
        $collectionMock->expects($this->atLeastOnce())->method('load')->willReturnSelf();
        $collectionMock->expects($this->atLeastOnce())->method('getItems')->willReturn($items);

        $this->assertEquals($expectedResult, $this->suggestDataProvider->getItems($query));
    }

    /**
     * Data provider for getItems.
     *
     * @return array
     */
    public function getItemsDataProvider()
    {
        $item1 = $this->createProductMock('sku-1');
        $item2 = $this->createProductMock('sku-2');

        return [
            [
                [
                    $item1,
                    $item2
                ],
                [
                    ['id' => 'sku-2', 'value' => 'sku-2', 'labelSku' => 'sku-2', 'labelProductName' => 'sku-2'],
                    ['id' => 'sku-1', 'value' => 'sku-1', 'labelSku' => 'sku-1', 'labelProductName' => 'sku-1']
                ]
            ]
        ];
    }

    /**
     * Create product mock.
     *
     * @param string $sku
     * @return ProductInterface|MockObject
     */
    private function createProductMock($sku)
    {
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $productMock->expects($this->any())
            ->method('getSku')
            ->willReturn($sku);
        $productMock->expects($this->any())
            ->method('getName')
            ->willReturn($sku);
        return $productMock;
    }
}
