<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardSharedCatalog\Test\Unit\Ui\DataProvider\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\Price;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftCardSharedCatalog\Ui\DataProvider\Modifier\GiftCard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for GiftCard modifier.
 */
class GiftCardTest extends TestCase
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
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var GiftCard
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
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->modifier = $objectManager->getObject(
            GiftCard::class,
            [
                'productRepository' => $this->productRepository,
                'productType' => $this->productType,
                'metadataPool' => $this->metadataPool,
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
        $data = [
            'entity_id' => 1,
            'type_id' => 'fixed'
        ];
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $price = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMinAmount'])
            ->getMock();
        $entity = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($entity);
        $entity->expects($this->once())->method('getIdentifierField')->willReturn('entity_id');
        $this->productRepository->expects($this->once())->method('getById')->with(1)->willReturn($product);
        $this->productType->expects($this->once())->method('priceFactory')->with('fixed')->willReturn($price);
        $price->expects($this->once())->method('getMinAmount')->with($product)->willReturn(15);

        $this->assertSame(
            [
                'entity_id' => 1,
                'type_id' => 'fixed',
                'price' => 15,
                'new_price' => 15
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
