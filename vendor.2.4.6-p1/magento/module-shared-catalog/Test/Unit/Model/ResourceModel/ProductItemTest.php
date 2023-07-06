<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductItem resource model.
 */
class ProductItemTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resources;

    /**
     * @var ProductItem
     */
    private $productItem;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resources = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->productItem = $objectManager->getObject(
            ProductItem::class,
            [
                '_resources' => $this->resources,
            ]
        );
    }

    /**
     * Test for createItems method.
     *
     * @return void
     */
    public function testCreateItems()
    {
        $customerGroupId = 1;
        $productSkus = ['SKU1', 'SKU2'];
        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resources->expects($this->once())->method('getConnection')->with('default')->willReturn($connection);
        $this->resources->expects($this->once())->method('getTableName')
            ->with('shared_catalog_product_item', 'default')
            ->willReturn('shared_catalog_product_item');
        $connection->expects($this->once())->method('insertMultiple')
            ->with(
                'shared_catalog_product_item',
                [
                    ['sku' => $productSkus[0], 'customer_group_id' => $customerGroupId],
                    ['sku' => $productSkus[1], 'customer_group_id' => $customerGroupId]
                ]
            )->willReturn(2);
        $this->productItem->createItems($productSkus, $customerGroupId);
    }

    /**
     * Test for deleteItems method.
     *
     * @return void
     */
    public function testDeleteItems()
    {
        $customerGroupId = 1;
        $productSkus = ['SKU1', 'SKU2'];
        $tableName = 'shared_catalog_product_item';
        $deleteQuery = 'DELETE FROM...';
        $connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resources->expects($this->atLeastOnce())
            ->method('getConnection')->with('default')->willReturn($connection);
        $this->resources->expects($this->once())
            ->method('getTableName')->with($tableName, 'default')->willReturn($tableName);
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('select')->willReturn($select);
        $select->expects($this->once())->method('from')->with($tableName)->willReturnSelf();
        $select->expects($this->exactly(2))->method('where')->withConsecutive(
            ['sku IN (?)', $productSkus],
            ['customer_group_id = ?', $customerGroupId]
        )->willReturnSelf();
        $connection->expects($this->once())
            ->method('deleteFromSelect')->with($select, $tableName)->willReturn($deleteQuery);
        $dbStatement = $this->getMockBuilder(\Zend_Db_Statement_Interface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())
            ->method('query')->with($deleteQuery)->willReturn($dbStatement);
        $this->productItem->deleteItems($productSkus, $customerGroupId);
    }
}
