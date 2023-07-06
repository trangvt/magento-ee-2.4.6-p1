<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider;

use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;
use Magento\SharedCatalog\Ui\DataProvider\Collection\SharedCatalogFactory;
use Magento\SharedCatalog\Ui\DataProvider\SharedCatalog;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for UI DataProvider\SharedCatalog.
 */
class SharedCatalogTest extends TestCase
{
    /**
     * @var SharedCatalogFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var FilterPool|MockObject
     */
    private $filterPool;

    /**
     * @var SharedCatalog|MockObject
     */
    private $sharedCatalogMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->filterPool = $this
            ->getMockBuilder(FilterPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Test for getCatalogDetailsData().
     *
     * @return void
     */
    public function testGetCatalogDetailsData()
    {
        $name = 'sample Name';
        $description = 'sample description';
        $customerGroupId = '123';
        $type = 'sample type';
        $taxClassId = 234;
        $createdAt = 'sample created at';
        $createdBy = 'sample created by';
        $sharedCatalog = $this->getMockBuilder(DocumentInterface::class)
            ->setMethods([
                'getName', 'getDescription', 'getCustomerGroupId', 'getType', 'getTaxClassId',
                'getCreatedAt', 'getCreatedBy'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $sharedCatalog->expects($this->once())->method('getName')->willReturn($name);
        $sharedCatalog->expects($this->once())->method('getDescription')->willReturn($description);
        $sharedCatalog->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $sharedCatalog->expects($this->once())->method('getType')->willReturn($type);
        $sharedCatalog->expects($this->once())->method('getTaxClassId')->willReturn($taxClassId);
        $sharedCatalog->expects($this->once())->method('getCreatedAt')->willReturn($createdAt);
        $sharedCatalog->expects($this->once())->method('getCreatedBy')->willReturn($createdBy);
        $this->collectionFactory = $this
            ->getMockBuilder(SharedCatalogFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogMock = $this->objectManager->getObject(
            SharedCatalog::class,
            [
                'collectionFactory' => $this->collectionFactory,
                'filterPool' => $this->filterPool,
            ]
        );
        $this->sharedCatalogMock->getCatalogDetailsData($sharedCatalog);
    }

    /**
     * Data provider for getData().
     *
     * @return array
     */
    public function testGetDataDataProvider()
    {
        return [
            [true],
            [null],
        ];
    }

    /**
     * Test getData().
     *
     * @param array $loadedData
     * @dataProvider testGetDataDataProvider
     * @return void
     */
    public function testGetData($loadedData)
    {
        if (isset($loadedData)) {
            $this->checkLoadedDataIsSetCase();
        } else {
            $this->checkLoadedDataNotSetCase();
        }
    }

    /**
     * Case for test getData(): loaded data is set.
     *
     * @return void
     */
    private function checkLoadedDataIsSetCase()
    {
        $data = 'sample data';
        $this->collectionFactory = $this
            ->getMockBuilder(SharedCatalogFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->sharedCatalogMock = $this->objectManager->getObject(
            SharedCatalog::class,
            [
                '',
                '',
                '',
                'collectionFactory' => $this->collectionFactory,
                'filterPool' => $this->filterPool,
                'meta' => [],
                'data' => [],
            ]
        );

        $reflection = new \ReflectionClass($this->sharedCatalogMock);
        $reflectionProperty = $reflection->getProperty('loadedData');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->sharedCatalogMock, $data);
        $actualResult = $this->sharedCatalogMock->getData();
        $this->assertEquals($data, $actualResult);
    }

    /**
     * Case for test getData(): loaded data not set.
     *
     * @return void
     */
    private function checkLoadedDataNotSetCase()
    {
        $id = 1234;
        $expectedResult = [
            $id => [
                'catalog_details' => [
                    'name' => null,
                    'description' => null,
                    'customer_group_id' => null,
                    'type' => null,
                    'tax_class_id' => null,
                    'created_at' => null,
                    'created_by' => null
                ],
                'shared_catalog_id' => $id
            ]
        ];
        $sharedCatalog = $this->getMockBuilder(DocumentInterface::class)
            ->setMethods([
                'getName', 'getDescription', 'getCustomerGroupId', 'getType', 'getTaxClassId',
                'getCreatedAt', 'getCreatedBy'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        //number inside the exactly() clause must be 2*(number of elements in array $items)
        $sharedCatalog->expects($this->exactly(2))->method('getId')->willReturn($id);

        $items = [
            $sharedCatalog,
        ];
        $collection = $this
            ->getMockBuilder(AbstractCollection::class)
            ->setMethods(['getItems'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $collection->expects($this->once())->method('getItems')->willReturn($items);

        $this->collectionFactory = $this
            ->getMockBuilder(SharedCatalogFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);

        $modifiers = $this->getMockBuilder(PoolInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $modifier = $this->getMockBuilder(ModifierInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $modifier->expects($this->once())->method('modifyData')->willReturnArgument(0);
        $modifiers->expects($this->once())->method('getModifiersInstances')->willReturn([$modifier]);

        $this->sharedCatalogMock = $this->objectManager->getObject(
            SharedCatalog::class,
            [
                'collectionFactory' => $this->collectionFactory,
                'filterPool' => $this->filterPool,
                'modifiers' => $modifiers,
            ]
        );
        $actualResult = $this->sharedCatalogMock->getData();
        $this->assertEquals($expectedResult, $actualResult);
    }
}
