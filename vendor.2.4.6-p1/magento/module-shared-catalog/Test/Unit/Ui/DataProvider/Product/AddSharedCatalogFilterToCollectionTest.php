<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Ui\DataProvider\Product\AddSharedCatalogFilterToCollection;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\SharedCatalog\Ui\DataProvider\Product\AddSharedCatalogFilterToCollection class.
 */
class AddSharedCatalogFilterToCollectionTest extends TestCase
{
    /**
     * @var AddSharedCatalogFilterToCollection
     */
    private $dataProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->dataProvider = $objectManager->getObject(
            AddSharedCatalogFilterToCollection::class
        );
    }

    /**
     * Test addFilter method.
     *
     * @return void
     */
    public function testAddFilter()
    {
        $field = 'entity_id';
        $condition = ['in' => [4, 9]];
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelect', 'getTable', 'distinct'])
            ->getMock();
        $select = $this->getMockBuilder(Select::class)
            ->setMethods(['joinInner', 'where'])
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->atLeastOnce())->method('getSelect')->willReturn($select);
        $collection->expects($this->atLeastOnce())
            ->method('getTable')
            ->withConsecutive(['shared_catalog_product_item'], ['shared_catalog'])
            ->willReturnOnConsecutiveCalls('shared_catalog_product_item', 'shared_catalog');
        $select->expects($this->atLeastOnce())
            ->method('joinInner')
            ->withConsecutive(
                [
                    ['scpi' => 'shared_catalog_product_item'],
                    'scpi.sku=e.sku',
                    []
                ],
                [
                    ['sc' => 'shared_catalog'],
                    'sc.customer_group_id=scpi.customer_group_id',
                    []
                ]
            )
            ->willReturnSelf();
        $select->expects($this->once())
            ->method('where')
            ->with('sc.entity_id IN (?)', $condition['in'])
            ->willReturnSelf();
        $collection->expects($this->once())->method('distinct')->with(true)->willReturnSelf();
        $this->dataProvider->addFilter($collection, $field, $condition);
    }
}
