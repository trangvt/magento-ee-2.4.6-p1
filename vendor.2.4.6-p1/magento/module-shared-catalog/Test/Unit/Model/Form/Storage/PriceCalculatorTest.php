<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\Form\Storage;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Form\Storage\PriceCalculator;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use Magento\SharedCatalog\Model\ProductItemTierPriceValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for price calculator model.
 */
class PriceCalculatorTest extends TestCase
{
    /**
     * @var WizardFactory|MockObject
     */
    private $storageFactory;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var Wizard|MockObject
     */
    private $storage;

    /**
     * @var ProductItemTierPriceValidator|MockObject
     */
    private $productItemTierPriceValidator;

    /**
     * @var FormatInterface|MockObject
     */
    private $localeFormat;

    /**
     * @var PriceCalculator
     */
    private $model;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storageFactory = $this
            ->getMockBuilder(WizardFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepository = $this->getMockBuilder(
            ProductRepositoryInterface::class
        )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productItemTierPriceValidator = $this->getMockBuilder(
            ProductItemTierPriceValidator::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeFormat = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            PriceCalculator::class,
            [
                'storageFactory' => $this->storageFactory,
                'productRepository' => $this->productRepository,
                'productItemTierPriceValidator' => $this->productItemTierPriceValidator,
                'localeFormat' => $this->localeFormat,
            ]
        );
    }

    /**
     * Test calculateNewPriceForProduct method.
     *
     * @param float $expectedResult
     * @param array $customPrice
     * @return void
     * @dataProvider calculateNewPriceForProductDataProvider
     */
    public function testCalculateNewPriceForProduct($expectedResult, array $customPrice)
    {
        $productId = 1;
        $oldPrice = 15;
        $this->storageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($this->storage);
        $this->storage->expects($this->once())->method('getProductPrices')->with($productId)->willReturn([]);
        $this->productItemTierPriceValidator->expects($this->once())
            ->method('existsPricePerWebsite')
            ->with([])
            ->willReturn(false);
        $this->storage->expects($this->once())->method('getProductPrice')->with($productId)->willReturn($customPrice);

        $this->assertEquals(
            $expectedResult,
            $this->model->calculateNewPriceForProduct('configure_key', $productId, $oldPrice)
        );
    }

    /**
     * Test calculateNewPriceForProduct method with fixed price.
     *
     * @return void
     */
    public function testCalculateNewPriceForProductWithFixedPrice()
    {
        $productId = 1;
        $oldPrice = 15;
        $customPrice = [
            'value_type' => 'fixed',
            'price' => 23,
        ];
        $this->storageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($this->storage);
        $this->storage->expects($this->once())->method('getProductPrices')->with($productId)->willReturn([]);
        $this->productItemTierPriceValidator->expects($this->once())
            ->method('existsPricePerWebsite')
            ->with([])
            ->willReturn(false);
        $this->storage->expects($this->once())->method('getProductPrice')->with($productId)->willReturn($customPrice);
        $this->localeFormat->expects($this->once())
            ->method('getNumber')
            ->with($customPrice['price'])
            ->willReturn(23);

        $this->assertEquals(
            23,
            $this->model->calculateNewPriceForProduct('configure_key', $productId, $oldPrice)
        );
    }

    /**
     * Test calculateNewPriceForProduct method when price already exists for the website.
     *
     * @return void
     */
    public function testCalculateNewPriceForProductPriceExists()
    {
        $productId = 1;
        $oldPrice = 15;
        $this->storageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($this->storage);
        $this->storage->expects($this->once())->method('getProductPrices')->with($productId)->willReturn([]);
        $this->productItemTierPriceValidator->expects($this->once())
            ->method('existsPricePerWebsite')
            ->with([])
            ->willReturn(true);
        $this->assertNull($this->model->calculateNewPriceForProduct('configure_key', $productId, $oldPrice));
    }

    /**
     * Data provider for calculateNewPriceForProduct method.
     *
     * @return array
     */
    public function calculateNewPriceForProductDataProvider()
    {
        return [
            [
                14.25,
                [
                    'value_type' => 'percent',
                    'percentage_value' => 5,
                ]
            ],
            [
                15,
                []
            ]
        ];
    }
}
