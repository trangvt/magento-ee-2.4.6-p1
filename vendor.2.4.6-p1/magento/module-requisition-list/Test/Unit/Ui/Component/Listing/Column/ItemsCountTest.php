<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Ui\Component\Listing\Column\ItemsCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ItemsCountTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ItemsCount|MockObject
     */
    private $itemsCount;

    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepositoryMock;

    /**
     * Setup
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionListRepositoryMock = $this->getMockBuilder(
            RequisitionListRepositoryInterface::class
        )->disableOriginalConstructor()
            ->getMock();

        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $context->expects($this->never())
            ->method('getProcessor')
            ->willReturn($processorMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->itemsCount = $this->objectManagerHelper->getObject(
            ItemsCount::class,
            [
                'context' => $context,
                'requisitionListRepository' => $this->requisitionListRepositoryMock
            ]
        );
    }

    /**
     * Test prepareDataSource
     *
     * @param array $listMap
     * @param array $inputDataSource
     * @param array $expectedDataSource
     * @return void
     *
     * @dataProvider prepareDataSourceProvider
     */
    public function testPrepareDataSource(array $listMap, array $inputDataSource, array $expectedDataSource)
    {
        $this->requisitionListRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturnMap($listMap);

        $this->assertEquals(
            $expectedDataSource,
            $this->itemsCount->prepareDataSource($inputDataSource)
        );
    }

    /**
     * Test prepareDataSource with exception
     *
     * @return void
     */
    public function testPrepareDataSourceException()
    {
        $this->requisitionListRepositoryMock->expects($this->any())
            ->method('get')
            ->willThrowException(new NoSuchEntityException());

        $inputDataSource = $this->buildDataSourceMock([
            [
                'entity_id' => 13
            ]
        ]);
        $expectedDataSource = $this->buildDataSourceMock([
            [
                'entity_id' => 13,
                'items' => 0
            ]
        ]);

        $this->assertEquals(
            $expectedDataSource,
            $this->itemsCount->prepareDataSource($inputDataSource)
        );
    }

    /**
     * Data provider for prepareDataSource
     *
     * @return array
     */
    public function prepareDataSourceProvider()
    {
        return [
            [
                [
                    [1, $this->buildListMock([1, 2, 3])],
                    [2, $this->buildListMock([])]
                ],
                $this->buildDataSourceMock([
                    [
                        'entity_id' => 1
                    ],
                    [
                        'entity_id' => 2
                    ]
                ]),
                $this->buildDataSourceMock([
                    [
                        'entity_id' => 1,
                        'items' => 3
                    ],
                    [
                        'entity_id' => 2,
                        'items' => 0
                    ]
                ])
            ]
        ];
    }

    /**
     * @param array $items
     * @return RequisitionListInterface|MockObject
     */
    private function buildListMock(array $items)
    {
        $listMock = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $listMock->expects($this->any())
            ->method('getItems')
            ->willReturn($items);
        return $listMock;
    }

    /**
     * Build data source mock
     *
     * @param array $items
     * @return array
     */
    private function buildDataSourceMock(array $items)
    {
        $dataSourceMock = [
            'data' => [
                'items' => $items
            ]
        ];
        return $dataSourceMock;
    }
}
