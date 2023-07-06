<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\OrderDateFrom;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderDateFromTest.
 *
 * Unit test for Order Date From filter.
 */
class OrderDateFromTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Timezone|MockObject
     */
    private $localeDateMock;

    /**
     * @var \DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @var OrderDateFrom
     */
    private $orderDateModel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->localeDateMock = $this
            ->getMockBuilder(Timezone::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'convertConfigTimeToUtc'])
            ->getMock();

        $this->dateTimeMock = $this
            ->getMockBuilder(\DateTime::class)
            ->disableOriginalConstructor()
            ->setMethods(['format'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->orderDateModel = $this->objectManagerHelper->getObject(
            OrderDateFrom::class,
            [
                'localeDate' => $this->localeDateMock,
            ]
        );
    }

    /**
     * Test applyFilter() method.
     *
     * @return void
     */
    public function testApplyFilterFrom()
    {
        $value = '01/10/2017';
        $utcValue = '2017-01-10 08:00:00';

        $collectionMock = $this
            ->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter'])
            ->getMock();

        $this->localeDateMock->expects($this->once())
            ->method('convertConfigTimeToUtc')
            ->willReturn($utcValue);
        $this->localeDateMock->expects($this->once())
            ->method('date')
            ->willReturn($this->dateTimeMock);
        $this->dateTimeMock->expects($this->once())
            ->method('format')
            ->willReturn('2017-01-10 08:00:00');
        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('created_at', ['gteq' => '2017-01-10 08:00:00']);

        $this->assertSame(
            $collectionMock,
            $this->orderDateModel->applyFilter($collectionMock, $value)
        );
    }
}
