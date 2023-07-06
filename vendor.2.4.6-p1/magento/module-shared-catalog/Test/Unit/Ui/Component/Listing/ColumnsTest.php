<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\Component\Listing\Attribute\RepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\SharedCatalog\Ui\Component\Listing\ColumnFactory;
use Magento\SharedCatalog\Ui\Component\Listing\Columns;
use Magento\Ui\Component\Listing\Columns\ColumnInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for columns UI component.
 */
class ColumnsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var Columns
     */
    private $columns;

    /**
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * @var ColumnFactory|MockObject
     */
    private $columnFactoryMock;

    /**
     * @var RepositoryInterface|MockObject
     */
    private $attributeRepositoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->columnFactoryMock = $this->getMockBuilder(
            ColumnFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeRepositoryMock = $this->getMockBuilder(
            RepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->columns = $this->objectManagerHelper->getObject(
            Columns::class,
            [
                'context' => $this->contextMock,
                'columnFactory' => $this->columnFactoryMock,
                'attributeRepository' => $this->attributeRepositoryMock
            ]
        );
    }

    /**
     * Test for prepare() method.
     *
     * @return void
     */
    public function testPrepare()
    {
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processorMock);
        $attributeMock = $this->getMockBuilder(ProductAttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $attributeMock->expects($this->any())->method('getAttributeCode')->willReturn('testAttributeCode');
        $attributeMock->expects($this->once())->method('getIsFilterableInGrid')->willReturn(true);
        $this->attributeRepositoryMock->expects($this->once())->method('getList')->willReturn([$attributeMock]);
        $columnMock = $this->getMockBuilder(ColumnInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $columnMock->expects($this->once())->method('prepare');
        $this->columnFactoryMock->expects($this->once())->method('create')->willReturn($columnMock);

        $this->columns->prepare();
    }
}
