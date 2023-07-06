<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedSharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GroupedSharedCatalog\Ui\DataProvider\Modifier\Grouped;
use Magento\SharedCatalog\Model\Form\Storage\PriceCalculator;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Model\Form\Storage\Wizard;
use Magento\SharedCatalog\Model\Form\Storage\WizardFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Grouped modifier.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GroupedTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var Type|MockObject
     */
    private $productType;

    /**
     * @var WizardFactory|MockObject
     */
    private $storageFactory;

    /**
     * @var Wizard|MockObject
     */
    private $storage;

    /**
     * @var PriceCalculator|MockObject
     */
    private $priceCalculator;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Grouped
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
        $this->productType = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storageFactory = $this->getMockBuilder(WizardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->storage = $this->getMockBuilder(Wizard::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCalculator = $this->getMockBuilder(PriceCalculator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            Grouped::class,
            [
                'productRepository' => $this->productRepository,
                'productType' => $this->productType,
                'storageFactory' => $this->storageFactory,
                'priceCalculator' => $this->priceCalculator,
                'metadataPool' => $this->metadataPool,
                'request' => $this->request
            ]
        );
    }

    /**
     * Test modifyData method.
     *
     * @return void
     */
    public function testModifyData()
    {
        $this->request->expects($this->atLeastOnce())->method('getParam')
            ->with(UrlBuilder::REQUEST_PARAM_CONFIGURE_KEY)
            ->willReturn('configure_key');
        $data = [
            'entity_id' => 1,
            'website_id' => 1,
        ];
        $productSku = 'SKU1';
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $abstractType = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAssociatedProducts'])
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
        $this->productType->expects($this->once())->method('factory')->with($product)->willReturn($abstractType);
        $abstractType->expects($this->once())->method('getAssociatedProducts')->with($product)->willReturn([$product]);
        $this->storageFactory->expects($this->once())
            ->method('create')
            ->with(['key' => 'configure_key'])
            ->willReturn($this->storage);
        $this->storage->expects($this->once())->method('isProductAssigned')->with($productSku)->willReturn(true);
        $product->expects($this->atLeastOnce())->method('getSku')->willReturn($productSku);
        $product->expects($this->atLeastOnce())->method('getPrice')->willReturn(20);
        $this->priceCalculator->expects($this->once())
            ->method('calculateNewPriceForProduct')
            ->with('configure_key', $productSku, 20)
            ->willReturnArgument(2);

        $this->assertSame(
            [
                'entity_id' => 1,
                'website_id' => 1,
                'price' => 20,
                'new_price' => 20,
            ],
            $this->modifier->modifyData($data)
        );
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
