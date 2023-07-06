<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\InvoiceNumber;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class InvoiceNumberTest.
 *
 * Unit test for invoice number filter.
 */
class InvoiceNumberTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var InvoiceRepository|MockObject
     */
    private $invoiceRepositoryMock;

    /**
     * @var SearchCriteriaBuilderFactory|MockObject
     */
    private $searchCriteriaBuilderFactoryMock;

    /**
     * @var InvoiceNumber
     */
    private $invoiceNumberModel;

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

        $this->invoiceRepositoryMock = $this
            ->getMockBuilder(InvoiceRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->invoiceNumberModel = $this->objectManagerHelper->getObject(
            InvoiceNumber::class,
            [
                'invoiceRepository' => $this->invoiceRepositoryMock,
                'searchCriteriaBuilderFactory' => $this->searchCriteriaBuilderFactoryMock,
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

        $value = '1';

        $searchCriteriaMock = $this
            ->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchCriteriaBuilderMock = $this
            ->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addFilter'])
            ->getMock();

        $this->searchCriteriaBuilderFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilderMock);

        $searchCriteriaBuilderMock->expects($this->once())->method('addFilter');
        $searchCriteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteriaMock);
        $this->invoiceRepositoryMock
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn([]);

        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('entity_id', ['in' => []]);

        $this->assertSame(
            $collectionMock,
            $this->invoiceNumberModel->applyFilter($collectionMock, $value)
        );
    }
}
