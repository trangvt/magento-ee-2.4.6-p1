<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GroupedRequisitionList\Test\Unit\Plugin\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\RequisitionList\Model\RequisitionListItem\OptionFactory;
use Magento\GroupedRequisitionList\Plugin\Model\OptionsManagement as PluginOptionsManagement;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\GroupedRequisitionList\Model\RetrieveParentByRequest;

/**
 * Test options management for grouped product
 */
class OptionsManagementTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var OptionFactory
     */
    private $itemOptionsFactory;

    /**
     * @var PluginOptionsManagement
     */
    private $pluginOptionsManagement;

    /**
     * @var RetrieveParentByRequest
     */
    private $retrieveParentByRequest;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->itemOptionsFactory = $this->createPartialMock(OptionFactory::class, ['create']);
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->retrieveParentByRequest = $this->createMock(RetrieveParentByRequest::class);
        $this->pluginOptionsManagement = new PluginOptionsManagement(
            $this->itemOptionsFactory,
            $this->productRepository,
            $this->retrieveParentByRequest
        );
    }

    /**
     * Test save requisition list for grouped product.
     *
     * @param array $infoBuyRequest
     *
     * @dataProvider infoByuRequest
     *
     * @return void
     */
    public function testAfterGetOptions(array $infoBuyRequest): void
    {
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $product->method('getTypeId')
            ->willReturn('grouped');
        $this->productRepository->method('getById')
            ->willReturn($product);
        $this->retrieveParentByRequest->method('execute')->willReturn($product);
        $subject = $this->createMock(OptionsManagement::class);
        $subject->method('getInfoBuyRequest')->willReturn($infoBuyRequest);
        $requisitionItem = $this->getMockForAbstractClass(RequisitionListItemInterface::class);
        $requisitionItem->method('getId')->willReturn(1);
        $option = new DataObject;
        $this->itemOptionsFactory->method('create')->willReturn($option);
        $options =  $this->pluginOptionsManagement->afterGetOptions(
            $subject,
            [],
            $requisitionItem
        );
        $option->setData('value', 'grouped')
            ->setData('code', 'product_type');
        $option->setProduct($product);
        $expected = ['product_type' => $option];

        $this->assertEquals($expected, $options);
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
