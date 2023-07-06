<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionListItem;
use Magento\RequisitionList\Model\RequisitionListItem\Option;
use Magento\RequisitionList\Model\RequisitionListItem\ProductExtractor;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for RequisitionListItemProduct model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequisitionListItemProductTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var OptionsManagement|MockObject
     */
    private $optionsManagement;

    /**
     * @var ProductExtractor|MockObject
     */
    private $productExtractor;

    /**
     * @var RequisitionListItemProduct
     */
    private $requisitionListItemProduct;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'getList'])
            ->getMockForAbstractClass();
        $this->optionsManagement = $this->getMockBuilder(OptionsManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productExtractor = $this->getMockBuilder(ProductExtractor::class)
            ->disableOriginalConstructor()
            ->setMethods(['extract'])->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->requisitionListItemProduct = $objectManager->getObject(
            RequisitionListItemProduct::class,
            [
                'productRepository' => $this->productRepository,
                'optionsManagement' => $this->optionsManagement,
                'productExtractor' => $this->productExtractor
            ]
        );
    }

    /**
     * Test for setProduct method.
     *
     * @return void
     */
    public function testSetProduct()
    {
        $itemId = 1;
        $itemSku = 'SKU-01';
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getId')->willReturn($itemId);
        $item->expects($this->once())->method('getSku')->willReturn($itemSku);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->setProduct($item, $product);
    }

    /**
     * Test for getProduct method.
     *
     * @return void
     */
    public function testGetProduct()
    {
        $itemId = 1;
        $itemSku = 'SKU-01';
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getId')->willReturn($itemId);
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn($itemSku);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['setCustomOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('get')->with($itemSku, false, null, true)->willReturn($product);
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsManagement->expects($this->once())
            ->method('getOptions')->with($item, $product)->willReturn([$option]);
        $product->expects($this->once())->method('setCustomOptions')->with([$option])->willReturnSelf();
        $this->assertEquals($product, $this->requisitionListItemProduct->getProduct($item));
    }

    /**
     * Test for getProduct method with preset product.
     *
     * @return void
     */
    public function testGetProductWithPresetProduct()
    {
        $itemId = 1;
        $itemSku = 'SKU-01';
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->atLeastOnce())->method('getId')->willReturn($itemId);
        $item->expects($this->atLeastOnce())->method('getSku')->willReturn($itemSku);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['setCustomOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->never())->method('get');
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsManagement->expects($this->once())
            ->method('getOptions')->with($item, $product)->willReturn([$option]);
        $product->expects($this->once())->method('setCustomOptions')->with([$option])->willReturnSelf();
        $this->requisitionListItemProduct->setProduct($item, $product);
        $this->assertEquals($product, $this->requisitionListItemProduct->getProduct($item));
    }

    /**
     * Test for setIsProductAttached method.
     *
     * @return void
     */
    public function testSetIsProductAttached()
    {
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getId')->willReturn(null);
        $this->requisitionListItemProduct->setIsProductAttached($item, true);
    }

    /**
     * Test for isProductAttached method.
     *
     * @return void
     */
    public function testIsProductAttached()
    {
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->once())->method('getId')->willReturn(null);
        $this->assertFalse($this->requisitionListItemProduct->isProductAttached($item));
    }

    /**
     * Test for isProductAttached method with preset value.
     *
     * @return void
     */
    public function testIsProductAttachedWithPresetValue()
    {
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->atLeastOnce())->method('getId')->willReturn(null);
        $this->requisitionListItemProduct->setIsProductAttached($item, true);
        $this->assertTrue($this->requisitionListItemProduct->isProductAttached($item));
    }

    /**
     * Test for extract method.
     *
     * @return void
     */
    public function testExtract()
    {
        $productSku = 'SKU-01';
        $websiteId = 1;

        $requisitionListItem = $this->getMockBuilder(RequisitionListItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requisitionListItem->expects($this->once())->method('getSku')->willReturn($productSku);

        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productExtractor->expects($this->atLeastOnce())->method('extract')
            ->with([$productSku], $websiteId, true)->willReturn([$productSku => $product]);

        $this->assertEquals(
            [$productSku => $product],
            $this->requisitionListItemProduct->extract([$requisitionListItem], $websiteId)
        );
    }
}
