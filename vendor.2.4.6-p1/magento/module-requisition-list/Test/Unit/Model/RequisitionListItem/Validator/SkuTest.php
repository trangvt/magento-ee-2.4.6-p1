<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem\Validator;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionListItem\Validator\Sku;
use Magento\RequisitionList\Model\RequisitionListItemProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Sku validator.
 */
class SkuTest extends TestCase
{
    /**
     * @var OptionsManagement|MockObject
     */
    private $optionsManagement;

    /**
     * @var RequisitionListItemProduct|MockObject
     */
    private $requisitionListItemProduct;

    /**
     * @var Sku
     */
    private $skuValidator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->optionsManagement = $this->getMockBuilder(OptionsManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemProduct = $this
            ->getMockBuilder(RequisitionListItemProduct::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->skuValidator = $objectManager->getObject(
            Sku::class,
            [
                'optionsManagement' => $this->optionsManagement,
                'requisitionListItemProduct' => $this->requisitionListItemProduct,
            ]
        );
    }

    /**
     * Test for validate method.
     *
     * @return void
     */
    public function testValidate()
    {
        $buyRequestData = ['buy_request_data'];
        $itemOptions = ['option_ids' => '2,3'];
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn(true);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getStatus', 'isComposite', 'getTypeInstance', 'hasOptions', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $product->expects($this->once())->method('isComposite')->willReturn(true);
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareForCartAdvanced'])
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $this->optionsManagement->expects($this->once())
            ->method('getInfoBuyRequest')->with($item)->willReturn($buyRequestData);
        $typeInstance->expects($this->once())->method('prepareForCartAdvanced')
            ->with($this->isInstanceOf(DataObject::class), $product)->willReturn([]);
        $product->expects($this->once())->method('hasOptions')->willReturn(true);
        $item->expects($this->once())->method('getOptions')->willReturn($itemOptions);
        $option = $this->getMockBuilder(ProductCustomOptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getOptions')->willReturn([$option, $option]);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $option->expects($this->exactly(2))->method('getOptionId')->willReturnOnConsecutiveCalls(2, 3);

        $this->assertEquals([], $this->skuValidator->validate($item));
    }

    /**
     * Test for validate method with disabled product.
     *
     * @return void
     */
    public function testValidateWithDisabledProduct()
    {
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn(true);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getStatus'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $product->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_DISABLED);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('setIsProductAttached');

        $this->assertEquals(
            ['unavailable_sku' => __('The SKU was not found in the catalog.')],
            $this->skuValidator->validate($item)
        );
    }

    /**
     * Test for validate method without cart candidates.
     *
     * @return void
     */
    public function testValidateWithoutCartCandidates()
    {
        $buyRequestData = ['buy_request_data'];
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn(true);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getStatus', 'isComposite', 'getTypeInstance', 'hasOptions', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $product->expects($this->once())->method('isComposite')->willReturn(true);
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareForCartAdvanced'])
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $this->optionsManagement->expects($this->once())
            ->method('getInfoBuyRequest')->with($item)->willReturn($buyRequestData);
        $typeInstance->expects($this->once())->method('prepareForCartAdvanced')
            ->with($this->isInstanceOf(DataObject::class), $product)->willReturn('Error message');
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('setIsProductAttached');

        $this->assertEquals(
            ['options_updated' => __('Options were updated. Please review available configurations.')],
            $this->skuValidator->validate($item)
        );
    }

    /**
     * Test for validate method with changed options.
     *
     * @return void
     */
    public function testValidateWithChangedOptions()
    {
        $buyRequestData = ['buy_request_data'];
        $itemOptions = ['option_ids' => '2,3'];
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn(true);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getStatus', 'isComposite', 'getTypeInstance', 'hasOptions', 'getOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $product->expects($this->once())->method('isComposite')->willReturn(true);
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareForCartAdvanced'])
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $this->optionsManagement->expects($this->once())
            ->method('getInfoBuyRequest')->with($item)->willReturn($buyRequestData);
        $typeInstance->expects($this->once())->method('prepareForCartAdvanced')
            ->with($this->isInstanceOf(DataObject::class), $product)->willReturn([]);
        $product->expects($this->once())->method('hasOptions')->willReturn(true);
        $item->expects($this->once())->method('getOptions')->willReturn($itemOptions);
        $option = $this->getMockBuilder(ProductCustomOptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getOptions')->willReturn([$option]);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $option->expects($this->once())->method('getOptionId')->willReturn(2);

        $this->assertEquals(
            ['options_updated' => __('Options were updated. Please review available configurations.')],
            $this->skuValidator->validate($item)
        );
    }

    /**
     * Test for validate() with empty buy request data.
     *
     * @return void
     */
    public function testValidateWithEmptyBuyRequestData()
    {
        $buyRequestData = [];
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('isProductAttached')
            ->willReturn(true);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getStatus', 'isComposite', 'getTypeInstance'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getStatus')
            ->willReturn(Status::STATUS_ENABLED);
        $product->expects($this->once())->method('isComposite')->willReturn(true);
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $this->optionsManagement->expects($this->once())
            ->method('getInfoBuyRequest')->with($item)->willReturn($buyRequestData);
        $this->requisitionListItemProduct->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);

        $this->assertEquals(
            ['options_updated' => __('Options were updated. Please review available configurations.')],
            $this->skuValidator->validate($item)
        );
    }
}
