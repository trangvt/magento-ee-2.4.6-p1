<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Company;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Company\SharedCatalogId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SharedCatalogId UI listing company column component.
 */
class SharedCatalogIdTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var SharedCatalogId
     */
    private $sharedCatalogId;

    /**
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $processorMock = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock->expects($this->never())->method('getProcessor')->willReturn($processorMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->sharedCatalogId = $this->objectManagerHelper->getObject(
            SharedCatalogId::class,
            [
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testApplySorting()
    {
        $fieldName = 'test';
        $direction = 'direction';
        $sorting = [
            'field' => $fieldName,
            'direction' => $direction
        ];
        $this->contextMock->expects($this->once())->method('getRequestParam')->with('sorting')->willReturn($sorting);
        $this->sharedCatalogId->setData('config/sortable', true);
        $this->sharedCatalogId->setData('name', $fieldName);
        $dataProviderMock = $this->getMockBuilder(DataProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock->expects($this->once())->method('getDataProvider')->willReturn($dataProviderMock);
        $dataProviderMock->expects($this->once())->method('addOrder')->with(
            'shared_catalog_name',
            strtoupper($direction)
        );

        $this->sharedCatalogId->applySorting();
    }
}
