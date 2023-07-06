<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\OrderTotalMin;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Order total min filter.
 *
 * @see OrderTotal
 */
class OrderTotalMinTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderTotalMin
     */
    private $orderTotalModel;

    /**
     * @var OrderCollection|MockObject
     */
    private $collectionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->orderTotalModel = $this->objectManagerHelper->getObject(OrderTotalMin::class);

        $this->collectionMock = $this
            ->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter'])
            ->getMock();
    }

    /**
     * Test applyFilter() method.
     *
     * @return void
     */
    public function testApplyFilter()
    {
        $value = 10.99;

        $this->collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('grand_total', ['gteq' => floor($value)]);

        $this->assertSame(
            $this->collectionMock,
            $this->orderTotalModel->applyFilter($this->collectionMock, $value)
        );
    }
}
