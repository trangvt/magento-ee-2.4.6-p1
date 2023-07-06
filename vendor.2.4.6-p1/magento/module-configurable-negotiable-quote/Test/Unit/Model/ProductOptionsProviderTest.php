<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ConfigurableNegotiableQuote\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableNegotiableQuote\Model\ProductOptionsProvider;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
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
            Configurable::TYPE_CODE,
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
        $configurableAttributes = ['configurable_attributes'];
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConfigurableAttributesAsArray'])
            ->getMockForAbstractClass();
        $typeInstance->expects($this->atLeastOnce())->method('getConfigurableAttributesAsArray')
            ->willReturn($configurableAttributes);
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeInstance'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeInstance')->willReturn($typeInstance);

        $this->assertEquals($configurableAttributes, $this->productOptionsProvider->getOptions($product));
    }
}
