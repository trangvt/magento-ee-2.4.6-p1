<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\SharedCatalog;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test for Magento/SharedCatalog/Model/ResourceModel/SharedCatalog/Collection class.
 */
class CollectionTest extends TestCase
{
    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var AbstractDb|MockObject
     */
    private $resource;

    /**
     * @var EntityFactoryInterface|MockObject
     */
    private $entityFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var FetchStrategyInterface|MockObject
     */
    private $fetchStrategy;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManager;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Select|MockObject
     */
    private $select;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->connection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['select', 'getConcatSql', 'prepareSqlCondition'])
            ->getMockForAbstractClass();
        $this->resource = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getMainTable', 'getTable'])
            ->getMockForAbstractClass();
        $this->select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection->expects($this->exactly(2))->method('select')->willReturn($this->select);
        $this->resource->expects($this->once())->method('getConnection')->willReturn($this->connection);
        $this->resource->expects($this->once())->method('getMainTable')
            ->willReturn('shared_catalog');
        $this->resource->expects($this->exactly(2))->method('getTable')
            ->willReturn('shared_catalog');
        $this->entityFactory = $this->getMockBuilder(EntityFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fetchStrategy
            = $this->getMockBuilder(FetchStrategyInterface::class)
                ->disableOriginalConstructor()
                ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->collection = $objectManagerHelper->getObject(
            Collection::class,
            [
                'entityFactory' => $this->entityFactory,
                'logger' => $this->logger,
                'fetchStrategy' => $this->fetchStrategy,
                'eventManager' => $this->eventManager,
                'storeManager' => $this->storeManager,
                'connection' => $this->connection,
                'resource' => $this->resource,
            ]
        );
    }

    /**
     * Test for \Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\Collection::addFieldToFilter.
     *
     * @return void
     */
    public function testAddFieldToFilter()
    {
        $field = 'admin_user';
        $condition = ['like' => '%admin%'];
        $fieldSql = 'CONCAT_WS(\' \', \'customer_entity.firstname\', \'customer_entity.lastname\'';
        $conditionSql = $fieldSql . ' like \'%admin%\'';
        $result = '';
        $whereCallback = function ($resultCondition) use (&$result) {
            $result = $resultCondition;
        };
        $this->connection->expects($this->once())->method('getConcatSql')->willReturn($fieldSql);
        $this->connection->expects($this->once())->method('prepareSqlCondition')->willReturn($conditionSql);
        $this->select->expects($this->any())->method('where')->willReturnCallback($whereCallback);
        $this->collection->addFieldToFilter($field, $condition);
        $this->assertEquals($conditionSql, $result);
    }
}
