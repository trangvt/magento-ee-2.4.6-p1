<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\FilterPool;
use Magento\OrderHistorySearch\Model\Filter\OrderNumber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class FilterPoolTest.
 *
 * Unit test for filters pool.
 */
class FilterPoolTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var FilterPool
     */
    private $filterPoolModel;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManagerMock = $this
            ->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->filterPoolModel = $this->objectManagerHelper->getObject(
            FilterPool::class,
            [
                'objectManager' => $this->objectManagerMock,
                'filtersClassMap' => ['order-number' => OrderNumber::class],
            ]
        );
    }

    /**
     * Test get() method with successful result.
     *
     * @return void
     */
    public function testGetSuccessful()
    {
        $filterName = 'order-number';

        $orderNumberFilterMock = $this
            ->getMockBuilder(OrderNumber::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(OrderNumber::class)
            ->willReturn($orderNumberFilterMock);

        $this->assertSame($orderNumberFilterMock, $this->filterPoolModel->get($filterName));
    }

    /**
     * Test create() method with exception result.
     *
     * @return void
     */
    public function testCreateException()
    {
        $filterName = 'xyz';

        $this->objectManagerMock
            ->expects($this->never())
            ->method('get');

        $this->expectException(\InvalidArgumentException::class);

        $this->filterPoolModel->get($filterName);
    }
}
