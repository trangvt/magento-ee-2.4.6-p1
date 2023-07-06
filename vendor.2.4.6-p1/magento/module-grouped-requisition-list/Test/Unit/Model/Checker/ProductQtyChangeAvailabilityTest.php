<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Test\Unit\Model\Checker;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\GroupedRequisitionList\Model\Checker\ProductQtyChangeAvailability;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductQtyChangeAvailability.
 */
class ProductQtyChangeAvailabilityTest extends TestCase
{
    /**
     * @var ProductQtyChangeAvailability
     */
    private $productQtyChangeAvailability;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->productQtyChangeAvailability = $objectManagerHelper->getObject(
            ProductQtyChangeAvailability::class
        );
    }

    /**
     * Test for isAvailable().
     *
     * @param string $productType
     * @param bool $result
     * @return void
     * @dataProvider isAvailableDataProvider
     */
    public function testIsAvailable($productType, $result)
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeId')->willReturn($productType);

        $this->assertEquals($result, $this->productQtyChangeAvailability->isAvailable($product));
    }

    /**
     * DataProvider for testIsAvailable().
     *
     * @return array
     */
    public function isAvailableDataProvider()
    {
        return [
            [Grouped::TYPE_CODE, false],
            ['', true]
        ];
    }
}
