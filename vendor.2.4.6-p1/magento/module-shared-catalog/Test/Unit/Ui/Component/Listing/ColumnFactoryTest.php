<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\SharedCatalog\Ui\Component\Listing\ColumnFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for columns factory.
 */
class ColumnFactoryTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var ColumnFactory
     */
    private $columnFactory;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $componentFactoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->componentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->columnFactory = $this->objectManagerHelper->getObject(
            ColumnFactory::class,
            [
                'componentFactory' => $this->componentFactoryMock
            ]
        );
    }

    /**
     * Test for create() method.
     *
     * @return void
     */
    public function testCreate()
    {
        $attributeMock = $this->getMockBuilder(ProductAttributeInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getAttributeCode',
                'getDefaultFrontendLabel',
                'getFrontendInput',
                'getIsFilterableInGrid',
                'usesSource',
                'getSource'
            ])
            ->getMockForAbstractClass();
        $attributeMock->expects($this->once())->method('getAttributeCode')->willReturn('testAttributeCode');
        $attributeMock->expects($this->once())->method('getDefaultFrontendLabel')->willReturn('Test Attribute Label');
        $attributeMock->expects($this->any())->method('getFrontendInput')->willReturn('default');
        $attributeMock->expects($this->once())->method('getIsFilterableInGrid')->willReturn(true);
        $attributeMock->expects($this->once())->method('usesSource')->willReturn(true);
        $source = $this->getMockBuilder(AbstractSource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $source->expects($this->once())->method('getAllOptions')->willReturn(['options']);
        $attributeMock->expects($this->once())->method('getSource')->willReturn($source);
        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $uiComponentMock = $this->getMockBuilder(UiComponentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->componentFactoryMock->expects($this->once())->method('create')->willReturn($uiComponentMock);

        $result = $this->columnFactory->create($attributeMock, $contextMock);
        $this->assertInstanceOf(UiComponentInterface::class, $result);
    }
}
