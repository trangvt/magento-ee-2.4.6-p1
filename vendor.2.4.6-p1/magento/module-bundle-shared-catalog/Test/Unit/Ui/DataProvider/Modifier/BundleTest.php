<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleSharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\Data\OptionInterface;
use Magento\BundleSharedCatalog\Ui\DataProvider\Modifier\Bundle;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\SharedCatalog\Model\Form\Storage\PriceCalculator;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Bundle modifier.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BundleTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var WizardFactory|MockObject
     */
    private $storageFactory;

    /**
     * @var Wizard|MockObject
     */
    private $wizardStorage;

    /**
     * @var PriceCalculator|MockObject
     */
    private $priceCalculator;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var Type|MockObject
     */
    private $productType;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Bundle
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
        $this->storageFactory = $this->getMockBuilder(WizardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->wizardStorage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCalculator = $this->getMockBuilder(PriceCalculator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productType = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            Bundle::class,
            [
                'productRepository' => $this->productRepository,
                'storageFactory' => $this->storageFactory,
                'priceCalculator' => $this->priceCalculator,
                'metadataPool' => $this->metadataPool,
                'productType' => $this->productType,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test modifyData method.
     *
     * @param array $result
     * @param int $priceType
     * @param int $linkPriceType
     * @param int $priceTypeInvocationCounter
     * @return void
     * @dataProvider modifyDataDataProvider
     */
    public function testModifyData(array $result, $priceType, $linkPriceType, $priceTypeInvocationCounter)
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with(UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)
            ->willReturn('configure_key');
        $data = [
            'entity_id' => 1,
            'website_id' => 1,
        ];
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $attribute = $this->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $extensionAttribute = $this->getMockBuilder(ProductExtensionInterface::class)
            ->setMethods(['getBundleProductOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $bundleProductOptions = $this->getMockBuilder(OptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $linkProduct = $this->getMockBuilder(LinkInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $entity = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($entity);
        $entity->expects($this->once())->method('getIdentifierField')->willReturn('entity_id');
        $this->productRepository->expects($this->once())->method('getById')->with(1)->willReturn($product);
        $product->expects($this->atLeastOnce())
            ->method('getCustomAttribute')
            ->withConsecutive(['price_type'], ['price_type'], ['price_view'])
            ->willReturn($attribute);
        $attribute->expects($this->atLeastOnce())->method('getValue')->willReturn($priceType);
        $product->expects($this->atLeastOnce())->method('getPrice')->willReturn(120);
        $product->expects($this->atLeastOnce())->method('getExtensionAttributes')->willReturn($extensionAttribute);
        $extensionAttribute->expects($this->atLeastOnce())
            ->method('getBundleProductOptions')
            ->willReturn([$bundleProductOptions]);
        $bundleProductOptions->expects($this->once())->method('getProductLinks')->willReturn([$linkProduct]);
        $linkProduct->expects($this->once())->method('getSku')->willReturn('test_sku');
        $this->productRepository->expects($this->once())->method('get')->willReturn($product);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn('SKU1');
        $this->storageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($this->wizardStorage);
        $this->wizardStorage->expects($this->once())->method('isProductAssigned')->with('SKU1')->willReturn(true);
        $linkProduct->expects($this->exactly($priceTypeInvocationCounter))
            ->method('getPriceType')
            ->willReturn($linkPriceType);
        $linkProduct->expects($this->exactly($priceTypeInvocationCounter))->method('getPrice')->willReturn(20);
        $linkProduct->expects($this->once())->method('getQty')->willReturn(1);
        $bundleProductOptions->expects($this->once())->method('getRequired')->willReturn(true);
        $this->priceCalculator->expects($this->atLeastOnce())
            ->method('calculateNewPriceForProduct')
            ->willReturnArgument(2);

        $productType = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSalable'])
            ->getMockForAbstractClass();
        $productType->expects($this->once())->method('isSalable')->willReturn(true);
        $this->productType->expects($this->once())->method('factory')->willReturn($productType);

        $this->assertSame($result, $this->modifier->modifyData($data));
    }

    /**
     * Data provider for modifyData method.
     *
     * @return array
     */
    public function modifyDataDataProvider()
    {
        return [
            [
                [
                    'entity_id' => 1,
                    'website_id' => 1,
                    'max_new_price' => 144.0,
                    'new_price' => 144.0,
                    'max_price' => 144.0,
                    'price' => 144.0,
                    'currency_type' => 'percent',
                    'price_view' => 1,
                    'price_type' => 1
                ],
                1,
                1,
                1
            ],
            [
                [
                    'entity_id' => 1,
                    'website_id' => 1,
                    'max_new_price' => 140,
                    'new_price' => 140,
                    'max_price' => 140,
                    'price' => 140,
                    'currency_type' => 'percent',
                    'price_view' => 1,
                    'price_type' => 1
                ],
                1,
                0,
                1
            ],
            [
                [
                    'entity_id' => 1,
                    'website_id' => 1,
                    'max_new_price' => 120,
                    'new_price' => 120,
                    'max_price' => 120,
                    'price' => 120,
                    'currency_type' => 'percent',
                    'price_view' => 0,
                    'price_type' => 0
                ],
                0,
                0,
                0
            ],
        ];
    }

    /**
     * Test modifyMeta method.
     *
     * @return void
     */
    public function testModifyMeta()
    {
        $data = ['modifyMeta'];
        $this->assertEquals($data, $this->modifier->modifyMeta($data));
    }
}
