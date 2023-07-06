<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\OrderHistorySearch\Test\Unit\Model\Filter;

use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\OrderHistorySearch\Model\Filter\OrderDateTo;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderDateToTest.
 *
 * Unit test for order date to filter.
 */
class OrderDateToTest extends TestCase
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
     * @var OrderDateTo
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
            ->setMethods(
                [
                    'add',
                    'format',
                ]
            )
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->orderDateModel = $this->objectManagerHelper->getObject(
            OrderDateTo::class,
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
    public function testApplyFilterTo()
    {
        $value = '01/20/2017';
        $utcValue = '2017-01-20 08:00:00';

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
            ->method('add')
            ->willReturnSelf();
        $this->dateTimeMock->expects($this->once())
            ->method('format')
            ->willReturn('2017-01-21 08:00:00');
        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('created_at', ['lt' => '2017-01-21 08:00:00']);

        $this->assertSame(
            $collectionMock,
            $this->orderDateModel->applyFilter($collectionMock, $value)
        );
    }
}
