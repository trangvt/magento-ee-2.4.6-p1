<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionList\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\ProductSkuLocator;
use Magento\RequisitionList\Model\RequisitionListItem\OrderItem\Converter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Converter.
 */
class ConverterTest extends TestCase
{
    /**
     * @var RequisitionListRepositoryInterface|MockObject
     */
    private $requisitionListRepository;

    /**
     * @var RequisitionListManagementInterface|MockObject
     */
    private $requisitionListManagement;

    /**
     * @var Converter|MockObject
     */
    private $requisitionListItemConverter;

    /**
     * @var ProductSkuLocator|MockObject
     */
    private $productSkuLocator;

    /**
     * @var \Magento\RequisitionList\Model\RequisitionList\Order\Converter
     */
    private $converter;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->requisitionListRepository = $this
            ->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListManagement = $this
            ->getMockBuilder(RequisitionListManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemConverter = $this
            ->getMockBuilder(Converter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productSkuLocator = $this->getMockBuilder(ProductSkuLocator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->converter = $objectManagerHelper->getObject(
            \Magento\RequisitionList\Model\RequisitionList\Order\Converter::class,
            [
                'requisitionListRepository' => $this->requisitionListRepository,
                'requisitionListManagement' => $this->requisitionListManagement,
                'requisitionListItemConverter' => $this->requisitionListItemConverter,
                'productSkuLocator' => $this->productSkuLocator,
            ]
        );
    }

    /**
     * Test for convert() method.
     *
     * @return void
     */
    public function testConvert()
    {
        $productId = 2;
        $sku = 'sku';
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $orderItem = $this->getMockBuilder(OrderItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductId'])
            ->getMockForAbstractClass();
        $orderItem->expects($this->atLeastOnce())->method('getProductId')->willReturn($productId);
        $order = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $order->expects($this->atLeastOnce())->method('getItems')->willReturn([$orderItem]);
        $this->productSkuLocator->expects($this->atLeastOnce())->method('getProductSkus')->with([$productId])
            ->willReturn([$productId => $sku]);
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemConverter->expects($this->atLeastOnce())->method('convert')
            ->willReturn($requisitionListItem);
        $this->requisitionListManagement->expects($this->atLeastOnce())->method('addItemToList')
            ->with($requisitionList, $requisitionListItem)->willReturn($requisitionList);
        $this->requisitionListRepository->expects($this->atLeastOnce())->method('save')->willReturn($requisitionList);

        $this->assertEquals([$requisitionListItem], $this->converter->addItems($order, $requisitionList));
    }
}
