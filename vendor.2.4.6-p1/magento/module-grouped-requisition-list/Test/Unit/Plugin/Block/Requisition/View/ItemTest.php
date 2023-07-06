<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Test\Unit\Plugin\Block\Requisition\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\GroupedRequisitionList\Plugin\Block\Requisition\View\Item as ItemPlugin;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Block\Requisition\View\Item as ItemBlock;
use PHPUnit\Framework\TestCase;
use Magento\GroupedRequisitionList\Model\RetrieveParentByRequest;

/**
 * Test get product url for grouped product
 */
class ItemTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ItemPlugin
     */
    private $itemPlugin;

    /**
     * @var \Closure
     */
    private $closureMock;

    /**
     * @var RetrieveParentByRequest
     */
    private $retrieveParentByRequest;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManagerHelper($this);
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->retrieveParentByRequest = $this->createMock(RetrieveParentByRequest::class);
        $this->itemPlugin = $this->objectManager->getObject(
            ItemPlugin::class,
            [
                'productRepository' => $this->productRepository,
                'retrieveParentByRequest' => $this->retrieveParentByRequest
            ]
        );
    }

    /**
     * Tests get product url with empty options
     */
    public function testAroundGetProductUrlByItemWithEmptyOptions(): void
    {
        $productUrl = 'product_url';

        $item = $this->createMock(RequisitionListItemInterface::class);
        $item->method('getOptions')->willReturn([]);
        $subject = $this->createMock(ItemBlock::class);
        $subject->method('getItem')->willReturn($item);
        $closureMock = function () use ($productUrl) {
            return $productUrl;
        };

        $this->assertEquals(
            $productUrl,
            $this->itemPlugin->aroundGetProductUrlByItem($subject, $closureMock)
        );
    }

    /**
     * Test get product url for grouped product
     *
     * @dataProvider infoByuRequest
     *
     * @return void
     */
    public function testAroundGetProductUrlByItem(array $infoBuyRequest): void
    {
        $productUrl = 'product_url';
        $subject = $this->getMockBuilder(ItemBlock::class)
            ->setMethods(['getItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $item = $this->getMockBuilder(\stdClass::class)->addMethods(['getOptions'])
            ->disableOriginalConstructor()
            ->getMock();
        $item->method('getOptions')
            ->willReturn(['info_buyRequest' => $infoBuyRequest]);
        $subject->method('getItem')
            ->willReturn($item);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypeId', 'getProductUrl'])
            ->getMockForAbstractClass();
        $product->method('getTypeId')->willReturn('grouped');
        $product->expects($this->once())->method('getProductUrl')->willReturn($productUrl);
        $this->retrieveParentByRequest->method('execute')->willReturn($product);
        $this->closureMock = function () use ($subject) {
            return $subject;
        };
        $this->assertEquals(
            $productUrl,
            $this->itemPlugin->aroundGetProductUrlByItem($subject, $this->closureMock)
        );
    }

    /**
     * Data for request
     *
     * @return array
     */
    public function infoByuRequest(): array
    {
        return [
            [
                [
                    'super_group' => [
                        11 => 1,
                        22 => 1,
                    ],
                    'qty' => 1,
                    'item' => '22',
                ],
            ],
            [
                [
                    'super_product_config' => [
                        'product_type' => 'grouped',
                        'product_id' => '22'
                    ],
                    'qty' => 1,
                ],
            ],

        ];
    }
}
