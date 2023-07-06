<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Block\Requisition\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Catalog\Model\Product\Type\Price;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Adjustment\AdjustmentInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Block\Requisition\View\Item;
use Magento\RequisitionList\Model\Checker\ProductChangesAvailability;
use Magento\RequisitionList\Model\RequisitionListItemOptions;
use Magento\RequisitionList\Model\RequisitionListItemOptionsLocator;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;
use Magento\Tax\Pricing\Adjustment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\RequisitionList\Block\Requisition\View\Item.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var Image|MockObject
     */
    private $imageHelper;

    /**
     * @var \Magento\Tax\Helper\Data|MockObject
     */
    private $taxHelper;

    /**
     * @var \Magento\Catalog\Helper\Data|MockObject
     */
    private $catalogHelper;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilder;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ProductChangesAvailability|MockObject
     */
    private $productChangesAvailabilityChecker;

    /**
     * @var RequisitionListItemProduct|MockObject
     */
    private $requisitionListItemProduct;

    /**
     * @var RequisitionListItemOptionsLocator|MockObject
     */
    private $requisitionListItemOptionsLocator;

    /**
     * @var ItemResolverInterface|MockObject
     */
    private $itemResolver;

    /**
     * @var Item
     */
    private $item;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->imageHelper = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogHelper = $this->getMockBuilder(\Magento\Catalog\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlBuilder = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productChangesAvailabilityChecker = $this
            ->getMockBuilder(ProductChangesAvailability::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemProduct = $this
            ->getMockBuilder(RequisitionListItemProduct::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemOptionsLocator = $this->createMock(RequisitionListItemOptionsLocator::class);
        $this->itemResolver = $this->getMockForAbstractClass(ItemResolverInterface::class);

        $objectManager = new ObjectManager($this);
        $this->item = $objectManager->getObject(
            Item::class,
            [
                '_urlBuilder' => $this->urlBuilder,
                '_request' => $this->request,
                '_storeManager' => $this->storeManager,
                'imageHelper' => $this->imageHelper,
                'taxHelper' => $this->taxHelper,
                'priceCurrency' => $this->priceCurrency,
                'productChangesAvailabilityChecker' => $this->productChangesAvailabilityChecker,
                'requisitionListItemProduct' => $this->requisitionListItemProduct,
                'catalogHelper' => $this->catalogHelper,
                'data' => [],
                'itemOptionsLocator' => $this->requisitionListItemOptionsLocator,
                'itemResolver' => $this->itemResolver,
            ]
        );
    }

    /**
     * Test for getRequisitionListProduct method.
     *
     * @return void
     */
    public function testGetRequisitionListProduct()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')->willReturn(true);
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $this->item->setItem($requisitionListItem)->setItemErrors(['options_updated' => true]);
        $this->assertEquals($product, $this->item->getRequisitionListProduct());
    }

    /**
     * Test for getRequisitionListProduct method without product.
     *
     * @param bool $isProductAttached
     * @return void
     * @dataProvider getRequisitionListProductDataProvider
     */
    public function testGetRequisitionListProductWithoutProduct($isProductAttached)
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->once())
            ->method('isProductAttached')->willReturn($isProductAttached);
        $this->item->setItem($requisitionListItem);
        $this->assertNull($this->item->getRequisitionListProduct());
    }

    /**
     * Test for getRequisitionListProduct method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetRequisitionListProductWithNoSuchEntityException()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->setItem($requisitionListItem);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')->willReturn(true);
        $exception = new NoSuchEntityException(__('Exception'));
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')
            ->willThrowException($exception);

        $this->assertNull($this->item->getRequisitionListProduct());
    }

    /**
     * Test for getFormattedPrice method.
     *
     * @return void
     */
    public function testGetFormattedPrice()
    {
        $productQty = 2;
        $finalPrice = 15;
        $finalPriceWithTax = 20;
        $includingTax = true;
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->setMethods(['getPriceInfo', 'getPriceModel','getCustomOption'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $priceInfo = $this
            ->getMockBuilder(Base::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getPriceInfo')->willReturn($priceInfo);
        $product->expects($this->once())->method('getCustomOption')->willReturn([]);
        $adjustment = $this
            ->getMockBuilder(AdjustmentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $priceInfo->expects($this->once())
            ->method('getAdjustment')->with(Adjustment::ADJUSTMENT_CODE)->willReturn($adjustment);
        $adjustment->expects($this->once())->method('isIncludedInDisplayPrice')->willReturn($includingTax);
        $priceModel = $this
            ->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getPriceModel')->willReturn($priceModel);
        $requisitionListItem->expects($this->once())->method('getQty')->willReturn($productQty);
        $priceModel->expects($this->once())
            ->method('getFinalPrice')->with($productQty, $product)->willReturn($finalPrice);
        $store = $this
            ->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $this->catalogHelper->expects($this->once())->method('getTaxPrice')
            ->with($product, $finalPrice, $includingTax, null, null, null, $store, null, false)
            ->willReturn($finalPriceWithTax);
        $this->priceCurrency->expects($this->once())->method('convertAndFormat')
            ->with(
                $finalPriceWithTax,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $store
            )
            ->willReturn('$' . $finalPriceWithTax);
        $this->item->setItem($requisitionListItem);
        $this->assertEquals('$' . $finalPriceWithTax, $this->item->getFormattedPrice());
    }

    /**
     * Test for getFormattedPriceExcludingTax method.
     *
     * @return void
     */
    public function testGetFormattedPriceExcludingTax()
    {
        $productQty = 2;
        $finalPrice = 15;
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->setMethods(['getPriceInfo', 'getPriceModel','getCustomOption'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $priceModel = $this
            ->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getPriceModel')->willReturn($priceModel);
        $product->expects($this->once())->method('getCustomOption')->willReturn([]);
        $requisitionListItem->expects($this->once())->method('getQty')->willReturn($productQty);
        $priceModel->expects($this->once())
            ->method('getFinalPrice')->with($productQty, $product)->willReturn($finalPrice);
        $store = $this
            ->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $this->catalogHelper->expects($this->once())->method('getTaxPrice')
            ->with($product, $finalPrice, false, null, null, null, $store, null, false)
            ->willReturn($finalPrice);
        $this->priceCurrency->expects($this->once())->method('convertAndFormat')
            ->with(
                $finalPrice,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $store
            )
            ->willReturn('$' . $finalPrice);
        $this->item->setItem($requisitionListItem);
        $this->assertEquals('$' . $finalPrice, $this->item->getFormattedPriceExcludingTax());
    }

    /**
     * Test for getFormattedSubtotal method.
     *
     * @return void
     */
    public function testGetFormattedSubtotal()
    {
        $productQty = 2;
        $finalPrice = 15;
        $finalPriceWithTax = 20;
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->setMethods(['getPriceInfo', 'getPriceModel','getCustomOption'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $priceInfo = $this
            ->getMockBuilder(Base::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getPriceInfo')->willReturn($priceInfo);
        $adjustment = $this
            ->getMockBuilder(AdjustmentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $priceInfo->expects($this->once())
            ->method('getAdjustment')->with(Adjustment::ADJUSTMENT_CODE)->willReturn($adjustment);
        $adjustment->expects($this->once())->method('isIncludedInDisplayPrice')->willReturn(false);
        $priceModel = $this
            ->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getPriceModel')->willReturn($priceModel);
        $product->expects($this->once())->method('getCustomOption')->willReturn([]);
        $requisitionListItem->expects($this->atLeastOnce())->method('getQty')->willReturn($productQty);
        $priceModel->expects($this->once())
            ->method('getFinalPrice')->with($productQty, $product)->willReturn($finalPrice);
        $store = $this
            ->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $this->catalogHelper->expects($this->once())->method('getTaxPrice')
            ->with($product, $finalPrice, false, null, null, null, $store, null, false)
            ->willReturn($finalPriceWithTax);
        $this->priceCurrency->expects($this->once())->method('convertAndFormat')
            ->with(
                $finalPriceWithTax * $productQty,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $store
            )
            ->willReturn('$' . ($finalPriceWithTax * $productQty));
        $this->item->setItem($requisitionListItem);
        $this->assertEquals('$' . ($finalPriceWithTax * $productQty), $this->item->getFormattedSubtotal());
    }

    /**
     * Test for getFormattedSubtotalExcludingTax method.
     *
     * @return void
     */
    public function testGetFormattedSubtotalExcludingTax()
    {
        $productQty = 2;
        $finalPrice = 15;
        $finalPriceWithTax = 20;
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->setMethods(['getPriceInfo', 'getPriceModel','getCustomOption'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $priceModel = $this
            ->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->once())->method('getPriceModel')->willReturn($priceModel);
        $product->expects($this->once())->method('getCustomOption')->willReturn([]);
        $requisitionListItem->expects($this->atLeastOnce())->method('getQty')->willReturn($productQty);
        $priceModel->expects($this->once())
            ->method('getFinalPrice')->with($productQty, $product)->willReturn($finalPrice);
        $store = $this
            ->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $this->catalogHelper->expects($this->once())->method('getTaxPrice')
            ->with($product, $finalPrice, false, null, null, null, $store, null, false)
            ->willReturn($finalPriceWithTax);
        $this->priceCurrency->expects($this->once())->method('convertAndFormat')
            ->with(
                $finalPriceWithTax * $productQty,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $store
            )
            ->willReturn('$' . ($finalPriceWithTax * $productQty));
        $this->item->setItem($requisitionListItem);
        $this->assertEquals('$' . ($finalPriceWithTax * $productQty), $this->item->getFormattedSubtotalExcludingTax());
    }

    /**
     * Test for getImageUrl method.
     *
     * @return void
     */
    public function testGetImageUrl()
    {
        $productThumbnail = 'product_thumbnail_url';
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requisitionListItemOptions = $this->createMock(RequisitionListItemOptions::class);
        $this->requisitionListItemOptionsLocator->expects($this->atLeastOnce())
            ->method('getOptions')
            ->with($requisitionListItem)
            ->willReturn($requisitionListItemOptions);
        $this->itemResolver->expects($this->atLeastOnce())
            ->method('getFinalProduct')
            ->with($requisitionListItemOptions)
            ->willReturn($product);
        $this->imageHelper->expects($this->once())
            ->method('getDefaultPlaceholderUrl')->with('thumbnail')->willReturn('default_thumbnail_url');
        $this->imageHelper->expects($this->once())
            ->method('init')->with($product, 'product_thumbnail_image')->willReturnSelf();
        $this->imageHelper->expects($this->once())->method('getUrl')->willReturn($productThumbnail);
        $this->item->setItem($requisitionListItem);
        $this->assertEquals($productThumbnail, $this->item->getImageUrl());
    }

    /**
     * Test for getImageUrl method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetImageUrlWithNoSuchEntityException()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->setItem($requisitionListItem);
        $exception = new NoSuchEntityException(__('Exception'));
        $requisitionListItemOptions = $this->createMock(RequisitionListItemOptions::class);
        $this->requisitionListItemOptionsLocator->expects($this->atLeastOnce())
            ->method('getOptions')
            ->with($requisitionListItem)
            ->willReturn($requisitionListItemOptions);
        $this->itemResolver->expects($this->atLeastOnce())
            ->method('getFinalProduct')
            ->with($requisitionListItemOptions)
            ->willThrowException($exception);

        $this->assertNull($this->item->getImageUrl());
    }

    /**
     * Test for getProductUrlByItem method.
     *
     * @return void
     */
    public function testGetProductUrlByItem()
    {
        $productUrl = 'product_url';
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->setMethods(['getProductUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getProductUrl')->willReturn($productUrl);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $this->item->setItem($requisitionListItem);
        $this->assertEquals($productUrl, $this->item->getProductUrlByItem());
    }

    /**
     * Test for getProductUrlByItem method with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetProductUrlByItemWithNoSuchEntityException()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->setItem($requisitionListItem);
        $phrase = new Phrase(__('Exception'));
        $exception = new NoSuchEntityException($phrase);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')
            ->willThrowException($exception);

        $this->assertNull($this->item->getProductUrlByItem());
    }

    /**
     * Test for getItemConfigureUrl method.
     *
     * @return void
     */
    public function testGetItemConfigureUrl()
    {
        $itemId = 1;
        $productId = 2;
        $requisitionListId = 3;
        $url = 'configure_product_url';
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->setMethods(['getItemId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->setMethods(['getProductUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->once())->method('getProduct')->willReturn($product);
        $requisitionListItem->expects($this->once())->method('getItemId')->willReturn($itemId);
        $product->expects($this->once())->method('getId')->willReturn($productId);
        $requisitionListItem->expects($this->once())->method('getRequisitionListId')->willReturn($requisitionListId);
        $this->urlBuilder->expects($this->once())->method('getUrl')->with(
            'requisition_list/item/configure',
            [
                'item_id' => $itemId,
                'id' => $productId,
                'requisition_id' => $requisitionListId,
            ]
        )->willReturn($url);
        $this->item->setItem($requisitionListItem);
        $this->assertEquals($url, $this->item->getItemConfigureUrl());
    }

    /**
     * Test for displayBothPrices method.
     *
     * @return void
     */
    public function testDisplayBothPrices()
    {
        $store = $this
            ->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $this->taxHelper->expects($this->once())->method('displayBothPrices')->with($store)->willReturn(true);
        $this->assertTrue($this->item->displayBothPrices());
    }

    /**
     * Test for canEdit method.
     *
     * @return void
     */
    public function testCanEdit()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn($product);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $this->item->setItem($requisitionListItem);
        $this->productChangesAvailabilityChecker->expects($this->once())
            ->method('isProductEditable')->with($product)->willReturn(true);
        $this->assertTrue($this->item->canEdit());
    }

    /**
     * Test for canEdit method with edit allowed.
     *
     * @return void
     */
    public function testCanEditWithEditAllowed()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->setItem($requisitionListItem)->setItemErrors(['options_updated' => true]);
        $this->assertTrue($this->item->canEdit());
    }

    /**
     * Test for canEdit method without product.
     *
     * @return void
     */
    public function testCanEditWithoutProduct()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->once())->method('isProductAttached')->willReturn(true);
        $this->item->setItem($requisitionListItem);
        $this->assertFalse($this->item->canEdit());
    }

    /**
     * Test for canEdit method with NoSuchEntityException.
     *
     * @return void
     */
    public function testCanEditWithNoSuchEntityException()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->setItem($requisitionListItem);
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn($product);
        $exception = new NoSuchEntityException(__('Exception'));
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')
            ->willThrowException($exception);

        $this->assertFalse($this->item->canEdit());
    }

    /**
     * Test for canEditQty method.
     *
     * @return void
     */
    public function testCanEditQty()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->once())->method('isProductAttached')->willReturn(false);
        $product = $this
            ->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $this->productChangesAvailabilityChecker->expects($this->once())
            ->method('isQtyChangeAvailable')->with($product)->willReturn(true);
        $this->item->setItem($requisitionListItem);
        $this->assertTrue($this->item->canEditQty());
    }

    /**
     * Test for canEditQty method with NoSuchEntityException.
     *
     * @return void
     */
    public function testCanEditQtyWithNoSuchEntityException()
    {
        $requisitionListItem = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->item->setItem($requisitionListItem);
        $exception = new NoSuchEntityException(__('Exception'));
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')
            ->willThrowException($exception);

        $this->assertEquals('', $this->item->canEditQty());
    }

    /**
     * Data provider for testGetRequisitionListProduct.
     *
     * @return array
     */
    public function getRequisitionListProductDataProvider()
    {
        return [[true], [false]];
    }
}
