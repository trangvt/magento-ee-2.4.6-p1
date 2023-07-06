<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleSharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Bundle\Model\Product\Type;
use Magento\BundleSharedCatalog\Ui\DataProvider\Modifier\TierPriceBundle;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for TierPriceBundle modifier.
 */
class TierPriceBundleTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var ArrayManager|MockObject
     */
    private $arrayManager;

    /**
     * @var TierPriceBundle
     */
    private $modifier;

    /**
     * Set up for test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->arrayManager = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            TierPriceBundle::class,
            [
                'productRepository' => $this->productRepository,
                'request' => $this->request,
                'arrayManager' => $this->arrayManager
            ]
        );
    }

    /**
     * Test modifyMeta method.
     *
     * @return void
     */
    public function testModifyMeta(): void
    {
        $data = [
            'product_id' => 1
        ];
        $tierPricePath = 'tier/price/path';
        $pricePath = 'price/path';
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['isSalable'])
            ->getMockForAbstractClass();
        $this->request->expects($this->once())->method('getParam')->with('product_id')->willReturn(1);
        $this->productRepository->expects($this->once())->method('getById')->with(1)->willReturn($product);
        $product->expects($this->once())
            ->method('getTypeId')
            ->willReturn(Type::TYPE_CODE);

        $this->arrayManager->expects($this->any())
            ->method('findPath')
            ->withConsecutive(
                [
                    ProductAttributeInterface::CODE_TIER_PRICE,
                    $data,
                    null,
                    'children'
                ],
                [
                    ProductAttributeInterface::CODE_TIER_PRICE_FIELD_PRICE,
                    $data,
                    $tierPricePath
                ]
            )
            ->willReturnOnConsecutiveCalls($tierPricePath, $pricePath);

        $this->arrayManager->expects($this->exactly(2))
            ->method('slicePath')
            ->withConsecutive([$pricePath, 0, -1], ['price/value_type/arguments/data/options', 0, -1])
            ->willReturn('price');
        $this->arrayManager->expects($this->once())
            ->method('get')
            ->with('price/value_type/arguments/data/options', $data)
            ->willReturn([['value' => 'percent'], ['value' => 'fixed']]);
        $this->arrayManager->expects($this->once())
            ->method('remove')
            ->with('price/value_type/arguments/data/options', $data)
            ->willReturn([]);
        $this->arrayManager->expects($this->once())
            ->method('merge')
            ->with('price', [], ['options' => [['value' => 'percent']]])
            ->willReturn(['options' => [['value' => 'percent']]]);

        $this->assertSame(
            ['options' => [['value' => 'percent']]],
            $this->modifier->modifyMeta($data)
        );
    }

    /**
     * Test modifyData method.
     *
     * @return void
     */
    public function testModifyData(): void
    {
        $data = ['modifyData'];
        $this->assertEquals($data, $this->modifier->modifyData($data));
    }
}
