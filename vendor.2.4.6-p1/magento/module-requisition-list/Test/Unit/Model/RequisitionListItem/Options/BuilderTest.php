<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model\RequisitionListItem\Options;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use Magento\RequisitionList\Model\RequisitionListItem\OptionFactory;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for builder.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BuilderTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var OptionFactory|MockObject
     */
    private $optionFactory;

    /**
     * @var OptionsManagement|MockObject
     */
    private $optionsManagement;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Product|MockObject
     */
    private $productHelper;

    /**
     * @var Locator|MockObject
     */
    private $requisitionListItemLocator;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById', 'getParentProductId'])
            ->getMockForAbstractClass();
        $this->optionFactory = $this
            ->getMockBuilder(OptionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->optionsManagement = $this->getMockBuilder(OptionsManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productHelper = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['addParamsToBuyRequest'])
            ->getMock();
        $this->requisitionListItemLocator =
            $this->getMockBuilder(Locator::class)
                ->disableOriginalConstructor()
                ->setMethods(['getItem'])
                ->getMock();

        $objectManager = new ObjectManager($this);
        $this->builder = $objectManager->getObject(
            Builder::class,
            [
                'storeManager' => $this->storeManager,
                'productRepository' => $this->productRepository,
                'optionFactory' => $this->optionFactory,
                'optionsManagement' => $this->optionsManagement,
                'serializer' => $this->serializer,
                'productHelper' => $this->productHelper,
                'requisitionListItemLocator' => $this->requisitionListItemLocator
            ]
        );
    }

    /**
     * Test for build().
     *
     * @return void
     */
    public function testBuild()
    {
        $itemId = 1;
        $buyRequest = ['product' => 123];
        $itemProductOption = $this
            ->getMockBuilder(OptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemProductOption->expects($this->atLeastOnce())->method('getValue')->willReturn('option_value');
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $item = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemLocator->expects($this->once())->method('getItem')->willReturn($item);
        $this->productHelper->expects($this->once())->method('addParamsToBuyRequest')
            ->willReturn(new DataObject($buyRequest));
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentProductId', 'getTypeInstance', 'getCustomOptions'])
            ->getMock();
        $product->expects($this->atLeastOnce())->method('getTypeInstance')->willReturn($typeInstance);
        $product->expects($this->atLeastOnce())->method('getParentProductId')->willReturn(null);
        $product->expects($this->atLeastOnce())->method('getCustomOptions')->willReturn([$itemProductOption]);
        $typeInstance->expects($this->atLeastOnce())->method('processConfiguration')->willReturn([$product]);
        $this->productRepository->expects($this->atLeastOnce())->method('getById')->willReturn($product);
        $this->optionsManagement->expects($this->atLeastOnce())->method('addOption');
        $this->optionsManagement->expects($this->atLeastOnce())->method('getOptionsByRequisitionListItemId')
            ->willReturn(['option' => $itemProductOption]);

        $this->assertEquals(['option' => 'option_value'], $this->builder->build($buyRequest, $itemId, false));
    }

    /**
     * Test for build() with empty product id.
     *
     * @return void
     */
    public function testBuildWithEmptyProductId()
    {
        $itemId = 1;
        $buyRequest = [];

        $this->assertEquals(['info_buyRequest' => []], $this->builder->build($buyRequest, $itemId, false));
    }

    /**
     * Test for build() with param unserialization.
     *
     * @param array|string $infoBuyRequest
     * @param array $infoBuyRequestData
     * @param array $result
     * @param int $unserializeInvokesCount
     * @return void
     *
     * @dataProvider buildWithUnserializeDataProvider
     */
    public function testBuildWithUnserialize(
        $infoBuyRequest,
        array $infoBuyRequestData,
        array $result,
        $unserializeInvokesCount
    ) {
        $itemId = 1;
        $buyRequest = ['product' => 123];
        $itemProductOption = $this
            ->getMockBuilder(OptionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $itemProductOption->expects($this->atLeastOnce())->method('getValue')->willReturn($infoBuyRequest);
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $item = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemLocator->expects($this->once())->method('getItem')->willReturn($item);
        $this->productHelper->expects($this->once())->method('addParamsToBuyRequest')
            ->willReturn(new DataObject($buyRequest));
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParentProductId', 'getTypeInstance', 'getCustomOptions'])
            ->getMock();
        $product->expects($this->atLeastOnce())->method('getTypeInstance')->willReturn($typeInstance);
        $product->expects($this->atLeastOnce())->method('getParentProductId')->willReturn(null);
        $product->expects($this->atLeastOnce())->method('getCustomOptions')->willReturn([$itemProductOption]);
        $typeInstance->expects($this->atLeastOnce())->method('processConfiguration')->willReturn([$product]);
        $this->productRepository->expects($this->atLeastOnce())->method('getById')->willReturn($product);
        $this->optionsManagement->expects($this->atLeastOnce())->method('addOption');
        $this->optionsManagement->expects($this->atLeastOnce())->method('getOptionsByRequisitionListItemId')
            ->willReturn(['info_buyRequest' => $itemProductOption]);
        $this->serializer->expects($this->exactly($unserializeInvokesCount))->method('unserialize')
            ->willReturn($infoBuyRequestData);

        $this->assertEquals($result, $this->builder->build($buyRequest, $itemId, false));
    }

    /**
     * Test for build() with LocalizedException.
     *
     * @return void
     */
    public function testBuildWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $phrase = new Phrase('Exception');
        $exception = new NoSuchEntityException(__($phrase));
        $this->productRepository->expects($this->atLeastOnce())->method('getById')->willThrowException($exception);

        $this->builder->build(['product' => 123], 1, false);
    }

    /**
     * Test for build() for misconfigured complex product.
     *
     * @return void
     */
    public function testBuildForMisconfiguredProduct()
    {
        $itemId = 1;
        $buyRequest = ['product' => 123];
        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $store->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->storeManager->expects($this->atLeastOnce())->method('getStore')->willReturn($store);
        $item = $this
            ->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemLocator->expects($this->once())->method('getItem')->willReturn($item);
        $this->productHelper->expects($this->once())->method('addParamsToBuyRequest')
            ->willReturn(new DataObject($buyRequest));
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeInstance'])
            ->getMock();
        $product->expects($this->atLeastOnce())->method('getTypeInstance')->willReturn($typeInstance);
        $typeInstance->expects($this->atLeastOnce())->method('processConfiguration')->willReturn('error message');
        $this->productRepository->expects($this->atLeastOnce())->method('getById')->willReturn($product);

        $this->assertEquals([], $this->builder->build($buyRequest, $itemId, true));
    }

    /**
     * Data provider for test testBuildWithUnserialize.
     *
     * @return array
     */
    public function buildWithUnserializeDataProvider()
    {
        return [
            [
                json_encode(['key' => 'value']),
                ['key' => 'value'],
                ['info_buyRequest' => ['key' => 'value']],
                1
            ],
            [
                ['key' => 'value'],
                ['key' => 'value'],
                ['info_buyRequest' => ['key' => 'value']],
                0
            ]
        ];
    }
}
