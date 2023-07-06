<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionListProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for RequisitionListProduct.
 * @see RequisitionListProduct
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RequisitionListProductTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var Json|MockObject
     */
    private $jsonSerializer;

    /**
     * @var OptionsManagement|MockObject
     */
    private $optionsManagement;

    /**
     * @var Type|MockObject
     */
    private $productType;

    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->productType = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->setMethods(['factory'])
            ->getMock();

        $productTypesToConfigure = [
            'configurable',
            'bundle',
            'grouped'
        ];

        $this->jsonSerializer = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->optionsManagement = $this->getMockBuilder(OptionsManagement::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->requisitionListProduct = $objectManagerHelper->getObject(
            RequisitionListProduct::class,
            [
                'productRepository' => $this->productRepository,
                'serializer' => $this->serializer,
                'productType' => $this->productType,
                'productTypesToConfigure' => $productTypesToConfigure,
                'jsonSerializer' => $this->jsonSerializer,
                'optionsManagement' => $this->optionsManagement,
            ]
        );
    }

    /**
     * Test for getProduct().
     *
     * @return void
     */
    public function testGetProduct()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isVisibleInCatalog'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('isVisibleInCatalog')->willReturn(true);
        $this->productRepository->expects($this->atLeastOnce())->method('get')->willReturn($product);

        $this->assertEquals($product, $this->requisitionListProduct->getProduct('sku'));
    }

    /**
     * Test for getProduct() when product is not visible in catalog.
     *
     * @return void
     */
    public function testGetProductWithProductNotVisibleInCatalog()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isVisibleInCatalog'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('isVisibleInCatalog')->willReturn(false);
        $this->productRepository->expects($this->atLeastOnce())->method('get')->willReturn($product);

        $this->assertFalse($this->requisitionListProduct->getProduct('sku'));
    }

    /**
     * Test for getProduct() with NoSuchEntityException.
     *
     * @return void
     */
    public function testGetProductWithNoSuchEntityException()
    {
        $exception = new NoSuchEntityException(__('Exception'));
        $this->productRepository->expects($this->atLeastOnce())->method('get')->willThrowException($exception);

        $this->assertFalse($this->requisitionListProduct->getProduct('sku'));
    }

    /**
     * Test for isProductShouldBeConfigured().
     *
     * @return void
     */
    public function testGetIsProductShouldBeConfigured()
    {
        $typeInstance = $this->getMockBuilder(AbstractType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $typeInstance->expects($this->atLeastOnce())->method('hasRequiredOptions')->willReturn(true);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getTypeId',
                'getTypeInstance'
            ])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeId')
            ->willReturn(Type::TYPE_SIMPLE);
        $this->productType->expects($this->atLeastOnce())->method('factory')->willReturn($typeInstance);

        $this->assertTrue($this->requisitionListProduct->isProductShouldBeConfigured($product));
    }

    /**
     * Test for isProductShouldBeConfigured() for configurable product.
     *
     * @return void
     */
    public function testGetIsProductShouldBeConfiguredForConfigurableProduct()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId'])
            ->getMockForAbstractClass();
        $product->expects($this->atLeastOnce())->method('getTypeId')->willReturn('configurable');

        $this->assertTrue($this->requisitionListProduct->isProductShouldBeConfigured($product));
    }

    /**
     * Test for prepareProductData().
     *
     * @return void
     */
    public function testPrepareProductData()
    {
        $productData = '{"product_data":"options"}';
        $this->jsonSerializer->expects($this->atLeastOnce())->method('unserialize')
            ->willReturn(['options' => 'option_1']);

        $this->assertInstanceOf(
            DataObject::class,
            $this->requisitionListProduct->prepareProductData($productData)
        );
    }

    /**
     * Test for isProductExistsInRequisitionList() with empty list
     * This method should return false
     *
     * @return void
     */
    public function testIsProductExistsInRequisitionListWithEmptyList()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItems = [];

        $requisitionList->expects($this->once())->method('getItems')->willReturn($requisitionListItems);

        $this->optionsManagement->expects($this->exactly(count($requisitionListItems)))
            ->method('getInfoBuyRequest')
            ->willReturn([]);

        $this->assertFalse(
            $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                []
            )
        );
    }

    /**
     * Test for isProductExistsInRequisitionList() with list with matching simple product id
     * This method should return true
     *
     * @return void
     */
    public function testIsProductExistsInRequisitionListWithMatchingSimpleProductId()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productId = 1;

        $product->expects($this->once())->method('getId')->willReturn($productId);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem->expects($this->once())->method('getOptions')->willReturn(
            [
                'simple_product' => $productId
            ]
        );

        $requisitionListItems = [$requisitionListItem];

        $requisitionList->expects($this->once())->method('getItems')->willReturn($requisitionListItems);

        $this->optionsManagement->expects($this->exactly(count($requisitionListItems)))
            ->method('getInfoBuyRequest')
            ->willReturn([]);

        $this->assertTrue(
            $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                []
            )
        );
    }

    /**
     * Test for isProductExistsInRequisitionList() with list with matching super attributes
     * This method should return true
     *
     * @return void
     */
    public function testIsProductExistsInRequisitionListWithMatchingSuperAttributes()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productId = 1;

        $product->expects($this->once())->method('getId')->willReturn($productId);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem->expects($this->once())->method('getOptions')->willReturn([]);

        $requisitionListItems = [$requisitionListItem];

        $requisitionList->expects($this->once())->method('getItems')->willReturn($requisitionListItems);

        $this->optionsManagement->expects($this->exactly(count($requisitionListItems)))
            ->method('getInfoBuyRequest')
            ->willReturn([
                'product' => $productId,
                'super_attribute' => [
                    1 => 1,
                    2 => 2,
                ]
            ]);

        $this->assertTrue(
            $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                [
                    'super_attribute' => [
                        1 => 1,
                        2 => 2,
                    ]
                ]
            )
        );
    }

    /**
     * Test for isProductExistsInRequisitionList() with list with different super attributes
     * This method should return false
     *
     * @return void
     */
    public function testIsProductExistsInRequisitionListWithDifferentSuperAttributes()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productId = 1;

        $product->expects($this->once())->method('getId')->willReturn($productId);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem->expects($this->once())->method('getOptions')->willReturn([]);

        $requisitionListItems = [$requisitionListItem];

        $requisitionList->expects($this->once())->method('getItems')->willReturn($requisitionListItems);

        $this->optionsManagement->expects($this->exactly(count($requisitionListItems)))
            ->method('getInfoBuyRequest')
            ->willReturn([
                'product' => $productId,
                'super_attribute' => [
                    1 => 1,
                    2 => 2,
                ]
            ]);

        $this->assertFalse(
            $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                [
                    'super_attribute' => [
                        1 => 3,
                        2 => 4,
                    ]
                ]
            )
        );
    }

    /**
     * Test for isProductExistsInRequisitionList() with list with matching buy request product data
     * This method should return true
     *
     * @return void
     */
    public function testIsProductExistsInRequisitionListWithMatchingBuyRequestProduct()
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productId = 1;

        $product->expects($this->once())->method('getId')->willReturn($productId);

        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $requisitionListItem->expects($this->once())->method('getOptions')->willReturn([]);

        $requisitionListItems = [$requisitionListItem];

        $requisitionList->expects($this->once())->method('getItems')->willReturn($requisitionListItems);

        $this->optionsManagement->expects($this->exactly(count($requisitionListItems)))
            ->method('getInfoBuyRequest')
            ->willReturn(
                [
                    'product' => $productId
                ]
            );

        $this->assertTrue(
            $this->requisitionListProduct->isProductExistsInRequisitionList(
                $requisitionList,
                $product,
                []
            )
        );
    }
}
