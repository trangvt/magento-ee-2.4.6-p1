<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Test\Unit\Plugin\Model\RequisitionListItem;

use Magento\Catalog\Model\Product\Type\AbstractType as CatalogAbstractType;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\DataObject;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\RequisitionList\Model\RequisitionListProduct;
use Magento\RequisitionList\Model\RequisitionListItem\Locator;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\RequisitionListItem\SaveHandler as SaveHandlerModel;
use Magento\GroupedRequisitionList\Plugin\Model\RequisitionListItem\SaveHandler as SaveHandlerPlugin;
use PHPUnit\Framework\TestCase;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Test save handler requisition list for grouped product
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveHandlerTest extends TestCase
{
    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var Builder
     */
    private $optionsBuilder;

    /**
     * @var RequisitionListManagementInterface
     */
    private $requisitionListManagement;

    /**
     * @var Locator
     */
    private $requisitionListItemLocator;

    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /** @var ObjectManagerHelper */
    private $objectManager;

    /** @var SaveHandlerPlugin */
    private $saveHandler;

    /**
     * @var \Closure
     */
    private $closureMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManagerHelper($this);
        $this->requisitionListRepository = $this->getMockBuilder(RequisitionListRepositoryInterface::class)
            ->setMethods(['getItems', 'get'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->optionsBuilder = $this->createPartialMock(Builder::class, ['build']);
        $this->requisitionListManagement = $this->getMockForAbstractClass(RequisitionListManagementInterface::class);
        $this->requisitionListItemLocator = $this->createMock(Locator::class);
        $this->requisitionListProduct = $this->createPartialMock(RequisitionListProduct::class, ['getProduct']);
        $this->saveHandler = $this->objectManager->getObject(
            SaveHandlerPlugin::class,
            [
                'requisitionListRepository' => $this->requisitionListRepository,
                'optionsBuilder' => $this->optionsBuilder,
                'requisitionListManagement' => $this->requisitionListManagement,
                'requisitionListItemLocator' => $this->requisitionListItemLocator,
                'requisitionListProduct' => $this->requisitionListProduct,
            ]
        );
    }

    /**
     * Test save requisition list for grouped product.
     *
     * @return void
     */
    public function testAroundSaveItem(): void
    {
        $options = ['qty' => 1, 'product' => '1'];
        $productData = new DataObject(
            [
                'sku' => 'ParentGrouped',
                'options' => $options
            ]
        );
        $simpleProductMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['getCartQty'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $simpleProductMock->method('getCartQty')->willReturn(5);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeInstance'])
            ->getMockForAbstractClass();
        $product->method('getTypeId')
            ->willReturn('grouped');
        $product->method('getName')
            ->willReturn('Parent Grouped');
        $typeInstance = $this->getMockBuilder(CatalogAbstractType::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepareForCartAdvanced'])
            ->getMockForAbstractClass();
        $typeInstance->method('prepareForCartAdvanced')
            ->willReturn([$simpleProductMock]);
        $product->method('getTypeInstance')
            ->willReturn($typeInstance);
        $this->requisitionListProduct->method('getProduct')
            ->willReturn($product);
        $this->requisitionListRepository->method('getItems')
            ->willReturn([]);
        $requisitionList = $this->getMockBuilder(RequisitionListInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName'])
            ->getMockForAbstractClass();
        $requisitionList->method('getName')
            ->willReturn('Name Requisition List');
        $this->requisitionListRepository->method('get')
            ->willReturn($requisitionList);
        $requisitionItem = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $this->optionsBuilder->method('build')
            ->willReturn([]);
        $this->requisitionListItemLocator->method('getItem')
            ->willReturn($requisitionItem);

        $subject = $this->createMock(SaveHandlerModel::class);
        $this->closureMock = function () use ($subject) {
            return $subject;
        };
        $message =  $this->saveHandler->aroundSaveItem(
            $subject,
            $this->closureMock,
            $productData,
            $options,
            0,
            1
        );

        $expected = __(
            'Product %1 has been added to the requisition list %2.',
            'Parent Grouped',
            'Name Requisition List'
        );

        $this->assertEquals($expected, $message);
    }

    /**
     * Test save requisition list for not grouped product
     *
     * @return void
     */
    public function testAroundSaveNotGroupedProduct(): void
    {
        $productData = new DataObject();
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->method('getTypeId')
            ->willReturn('simple');
        $this->requisitionListProduct->method('getProduct')
            ->willReturn($product);
        $subject = $this->createMock(SaveHandlerModel::class);
        $this->closureMock = function () use ($subject) {
            return 'Not Grouped';
        };
        $message = $this->saveHandler->aroundSaveItem(
            $subject,
            $this->closureMock,
            $productData,
            [],
            0,
            1
        );

        $this->assertEquals('Not Grouped', $message);
    }
}
