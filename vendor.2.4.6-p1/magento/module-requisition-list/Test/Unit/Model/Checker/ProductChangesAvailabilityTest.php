<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\Checker;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Model\Checker\ProductChangesAvailability;
use Magento\RequisitionList\Model\Checker\ProductQtyChangeAvailabilityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ProductChangesAvailability model.
 */
class ProductChangesAvailabilityTest extends TestCase
{
    /**
     * @var ProductQtyChangeAvailabilityInterface|MockObject
     */
    private $checker;

    /**
     * @var ProductChangesAvailability
     */
    private $productChangesAvailability;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->checker = $this
            ->getMockBuilder(ProductQtyChangeAvailabilityInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->productChangesAvailability = $objectManager->getObject(
            ProductChangesAvailability::class,
            [
                'productQtyChangeAvailabilityCheckers' => [$this->checker],
                'ignoreTypes' => [Type::TYPE_SIMPLE],
            ]
        );
    }

    /**
     * Test for isProductEditable method.
     *
     * @param string $productType
     * @param int $optionsCalls
     * @param bool $expectedResult
     * @return void
     * @dataProvider isProductEditableDataProvider
     */
    public function testIsProductEditable($productType, $optionsCalls, $expectedResult)
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getTypeId', 'getTypeInstance'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->expects($this->once())->method('getTypeId')->willReturn($productType);
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typeInstance->expects($this->exactly($optionsCalls))->method('hasOptions')->willReturn(false);
        $product->expects($this->exactly($optionsCalls))->method('getTypeInstance')->willReturn($typeInstance);
        $this->assertEquals($expectedResult, $this->productChangesAvailability->isProductEditable($product));
    }

    /**
     * Test for isQtyChangeAvailable method.
     *
     * @param bool $expectedResult
     * @return void
     * @dataProvider isQtyChangeAvailableDataProvider
     */
    public function testIsQtyChangeAvailable($expectedResult)
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->checker->expects($this->once())->method('isAvailable')->with($product)->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->productChangesAvailability->isQtyChangeAvailable($product));
    }

    /**
     * Data provider for testIsProductEditable.
     *
     * @return array
     */
    public function isProductEditableDataProvider()
    {
        return [
            [Type::TYPE_SIMPLE, 1, false],
            [Type::TYPE_BUNDLE, 0, true],
        ];
    }

    /**
     * Data provider for testIsQtyChangeAvailable.
     *
     * @return array
     */
    public function isQtyChangeAvailableDataProvider()
    {
        return [[true], [false]];
    }
}
