<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\BundleNegotiableQuote\Test\Unit\Model;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Model\ResourceModel\Option\Collection;
use Magento\BundleNegotiableQuote\Model\ProductOptionsProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductOptionsProvider.
 */
class ProductOptionsProviderTest extends TestCase
{
    /**
     * @var ProductOptionsProvider
     */
    private $productOptionsProvider;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->productOptionsProvider = $objectManagerHelper->getObject(
            ProductOptionsProvider::class
        );
    }

    /**
     * Test for getProductType().
     *
     * @return void
     */
    public function testGetProductType()
    {
        $this->assertEquals(
            Type::TYPE_CODE,
            $this->productOptionsProvider->getProductType()
        );
    }

    /**
     * Test for getOptions().
     *
     * @return void
     */
    public function testGetOptions()
    {
        $select = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelectionId', 'getName'])
            ->getMock();
        $select->expects($this->atLeastOnce())->method('getSelectionId')->willReturn(2);
        $select->expects($this->atLeastOnce())->method('getName')->willReturn('product name');
        $selection = $this->getMockBuilder(OptionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOptionId', 'getTitle', 'getSelections'])
            ->getMockForAbstractClass();
        $selection->expects($this->atLeastOnce())->method('getOptionId')->willReturn(1);
        $selection->expects($this->atLeastOnce())->method('getTitle')->willReturn('title');
        $selection->expects($this->atLeastOnce())->method('getSelections')->willReturn([$select]);
        $optionsCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $optionsCollection->expects($this->atLeastOnce())->method('appendSelections')->willReturn([$selection]);
        $selectionsCollection = $this->getMockBuilder(\Magento\Bundle\Model\ResourceModel\Selection\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['setStoreFilter', 'getOptionsCollection', 'getSelectionsCollection', 'getOptionsIds'])
            ->getMockForAbstractClass();
        $typeInstance->expects($this->atLeastOnce())->method('setStoreFilter')->willReturnSelf();
        $typeInstance->expects($this->atLeastOnce())->method('getOptionsCollection')->willReturn($optionsCollection);
        $typeInstance->expects($this->atLeastOnce())->method('getSelectionsCollection')
            ->willReturn($selectionsCollection);
        $typeInstance->expects($this->atLeastOnce())->method('getOptionsIds')->willReturn([1, 2, 3]);
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeInstance', 'getStoreId'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeInstance')->willReturn($typeInstance);
        $optionsArray = [
            1 => [
                'label' => 'title',
                'values' => [
                    0 => [
                        'value_index' => 2,
                        'label' => 'product name'
                    ]
                ]
            ]
        ];

        $this->assertEquals($optionsArray, $this->productOptionsProvider->getOptions($product));
    }
}
