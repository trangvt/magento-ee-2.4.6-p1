<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\DataProvider\Configure;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Ui\DataProvider\Configure\TierPrice;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for TierPrice data provider.
 */
class TierPriceTest extends TestCase
{
    /**
     * @var PoolInterface|MockObject
     */
    private $modifiers;

    /**
     * @var WizardFactory|MockObject
     */
    private $wizardStorageFactory;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var TierPrice
     */
    private $tierPriceDataProvider;

    /**
     * @var array
     */
    private $meta = ['meta_data'];

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->modifiers = $this
            ->getMockBuilder(PoolInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->wizardStorageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this
            ->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->tierPriceDataProvider =$objectManager->getObject(
            TierPrice::class,
            [
                'request' => $this->request,
                'modifiers' => $this->modifiers,
                'wizardStorageFactory' => $this->wizardStorageFactory,
                'productRepository' => $this->productRepository,
                'meta' => $this->meta,
            ]
        );
    }

    /**
     * Test for getData method.
     *
     * @return void
     */
    public function testGetData()
    {
        $productId = 1;
        $productPrice = 15;
        $tierPrices = [
            [
                'qty' => 1,
                'website_id' => 0,
                'price' => 10,
                'price_type' => 'fixed',
                'sku' => 'product_sku',
            ]
        ];
        $sku = 'product_sku';
        $configureKey = 'configure_key_value';
        $expectedResult = ['modified_tier_prices'];
        $this->request->expects($this->exactly(2))->method('getParam')
            ->withConsecutive(['product_id'], ['configure_key'])
            ->willReturnOnConsecutiveCalls($productId, $configureKey);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository->expects($this->once())
            ->method('getById')->with($productId, false, 0, true)->willReturn($product);
        $storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->wizardStorageFactory->expects($this->once())
            ->method('create')->with(['key' => $configureKey])->willReturn($storage);
        $product->expects($this->once())->method('getSku')->willReturn($sku);
        $storage->expects($this->once())->method('getTierPrices')->with($sku)->willReturn($tierPrices);
        $product->expects($this->once())->method('getPrice')->willReturn($productPrice);
        $modifier = $this->getMockBuilder(ModifierInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->modifiers->expects($this->once())->method('getModifiersInstances')->willReturn([$modifier]);
        $modifier->expects($this->once())->method('modifyData')
            ->with(
                [
                    $productId => [
                        'product_id' => $productId,
                        'base_price' => $productPrice,
                        'tier_price' => $tierPrices,
                        'configure_key' => $configureKey,
                    ]
                ]
            )
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->tierPriceDataProvider->getData());
    }

    /**
     * Test for getMeta method.
     *
     * @return void
     */
    public function testGetMeta()
    {
        $expectedResult = ['meta_data_modified'];
        $modifier = $this->getMockBuilder(ModifierInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->modifiers->expects($this->once())->method('getModifiersInstances')->willReturn([$modifier]);
        $modifier->expects($this->once())->method('modifyMeta')->with($this->meta)->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->tierPriceDataProvider->getMeta());
    }
}
