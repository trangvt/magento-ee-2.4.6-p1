<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\OrderNumber;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderNumberTest.
 *
 * Unit test for order number filter.
 */
class OrderNumberTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var OrderNumber
     */
    private $orderNumberModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->orderNumberModel = $this->objectManagerHelper->getObject(
            OrderNumber::class,
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

        $value = '1';

        $collectionMock
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('increment_id', ['like' => '%' . $value . '%']);

        $this->assertSame(
            $collectionMock,
            $this->orderNumberModel->applyFilter($collectionMock, $value)
        );
    }
}
