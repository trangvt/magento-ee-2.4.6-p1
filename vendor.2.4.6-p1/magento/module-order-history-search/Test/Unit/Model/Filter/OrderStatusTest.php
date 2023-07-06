<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\OrderStatus;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderStatusTest.
 *
 * Unit test for Order Status filter.
 */
class OrderStatusTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderStatus
     */
    private $orderStatusModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->orderStatusModel = $this->objectManagerHelper->getObject(
            OrderStatus::class,
            []
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

        $value = 'completed';

        $collectionMock->expects($this->once())->method('addFieldToFilter')->with('status', ['eq' => $value]);

        $this->assertSame(
            $collectionMock,
            $this->orderStatusModel->applyFilter($collectionMock, $value)
        );
    }
}
