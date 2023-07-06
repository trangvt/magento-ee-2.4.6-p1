<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionList\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterfaceFactory;
use Magento\RequisitionList\Model\OptionsManagement;
use Magento\RequisitionList\Model\RequisitionList\Items;
use Magento\RequisitionList\Model\RequisitionListItem\Option;
use Magento\RequisitionList\Model\RequisitionListItem\OptionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for OptionsManagement.
 */
class OptionsManagementTest extends TestCase
{
    /**
     * @var OptionFactory|MockObject
     */
    private $itemOptionFactory;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var Items|MockObject
     */
    private $requisitionListItemRepository;

    /**
     * @var RequisitionListItemInterfaceFactory|MockObject
     */
    private $requisitionListItemFactory;

    /**
     * @var SerializerInterface|MockObject
     */
    private $serializer;

    /**
     * @var JsonValidator|MockObject
     */
    private $jsonValidatorMock;

    /**
     * @var OptionsManagement
     */
    private $optionsManagement;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->itemOptionFactory = $this
            ->getMockBuilder(OptionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requisitionListItemRepository = $this
            ->getMockBuilder(Items::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requisitionListItemFactory = $this
            ->getMockBuilder(RequisitionListItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->jsonValidatorMock = $this->getMockBuilder(JsonValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->optionsManagement = $objectManagerHelper->getObject(
            OptionsManagement::class,
            [
                'itemOptionFactory' => $this->itemOptionFactory,
                'productRepository' => $this->productRepository,
                'requisitionListItemRepository' => $this->requisitionListItemRepository,
                'requisitionListItemFactory' => $this->requisitionListItemFactory,
                'serializer' => $this->serializer,
                'jsonValidator' => $this->jsonValidatorMock
            ]
        );
    }

    /**
     * Test getOptions method.
     *
     * @return void
     */
    public function testGetOptions()
    {
        $this->jsonValidatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $params = $this->prepareGetOptionsMocks();
        $this->assertEquals(
            $params['result'],
            $this->optionsManagement->getOptions($params['item'], $params['product'])
        );
    }

    /**
     * Test getOptions method if requisition list id is not null.
     *
     * @param int|null $itemId
     * @param int $getInvokesCount
     * @param int $createInvokesCount
     * @return void
     *
     * @dataProvider getOptionsByRequisitionListItemIdDataProvider
     */
    public function testGetOptionsByRequisitionListItemId($itemId, $getInvokesCount, $createInvokesCount)
    {
        $this->jsonValidatorMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $params = $this->prepareGetOptionsMocks();
        $item = $params['item'];
        $this->requisitionListItemRepository->expects($this->exactly($getInvokesCount))->method('get')
            ->willReturn($item);
        $this->requisitionListItemFactory->expects($this->exactly($createInvokesCount))->method('create')
            ->willReturn($item);

        $this->assertEquals(
            $params['result'],
            $this->optionsManagement->getOptionsByRequisitionListItemId($itemId, $params['product'])
        );
    }

    /**
     * Test addOption method.
     *
     * @return void
     */
    public function testAddOption()
    {
        $itemId = 1;
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->setMethods(['getData', 'setData', 'getProduct', 'setProduct', 'getCode'])
            ->getMock();
        $option->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $option->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $option->expects($this->atLeastOnce())->method('getProduct')->willReturn($product);
        $option->expects($this->atLeastOnce())->method('setProduct')->willReturnSelf();
        $option->expects($this->atLeastOnce())->method('getCode')->willReturn('code');
        $this->itemOptionFactory->expects($this->atLeastOnce())->method('create')->willReturn($option);

        $this->optionsManagement->addOption($option, $itemId);
    }

    /**
     * Test addOption method if option is an attay.
     *
     * @return void
     */
    public function testAddOptionWhenOptionIsArray()
    {
        $itemId = 1;
        $option = ['option_code' => 'option_value'];
        $optionModel = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'getCode'])
            ->getMock();
        $this->itemOptionFactory->expects($this->atLeastOnce())->method('create')->willReturn($optionModel);
        $optionModel->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $optionModel->expects($this->atLeastOnce())->method('getCode')->willReturn('option_code');

        $this->optionsManagement->addOption($option, $itemId);
    }

    /**
     * Test addOption method throes LocalizedException.
     *
     * @return void
     */
    public function testAddOptionWithLocalizedException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $itemId = 1;
        $option = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->optionsManagement->addOption($option, $itemId);
    }

    /**
     * Test getInfoBuyRequest method.
     *
     * @return void
     */
    public function testGetInfoBuyRequest()
    {
        $options = '[{"info_buyRequest":["value"}]]';
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->atLeastOnce())->method('getOptions')->willReturn(json_encode($options));
        $this->serializer->expects($this->atLeastOnce())->method('unserialize')
            ->willReturnOnConsecutiveCalls(['info_buyRequest' => ['value']], ['info_buyRequest' => ['value']]);

        $this->assertEquals(['value'], $this->optionsManagement->getInfoBuyRequest($item));
    }

    /**
     * Prepare mocks for getOptions.
     *
     * @return array
     */
    private function prepareGetOptionsMocks()
    {
        $optionId = 1;
        $options = ['simple_product' => 'value'];
        $item = $this->getMockBuilder(RequisitionListItemInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $item->expects($this->atLeastOnce())->method('getId')->willReturn($optionId);
        $item->expects($this->atLeastOnce())->method('getOptions')->willReturn(json_encode($options));
        $this->serializer->expects($this->atLeastOnce())->method('unserialize')->willReturn($options);
        $product = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->atLeastOnce())->method('setData')->willReturnSelf();
        $option->expects($this->atLeastOnce())->method('setProduct')->with($product)->willReturnSelf();
        $this->itemOptionFactory->expects($this->atLeastOnce())->method('create')->willReturn($option);
        $this->productRepository->expects($this->atLeastOnce())->method('getById')->willReturn($product);

        return [
            'item' => $item,
            'product' => $product,
            'result' => ['simple_product' => $option]
        ];
    }

    /**
     * DataProvider for getOptionsByRequisitionListItemId.
     *
     * @return array
     */
    public function getOptionsByRequisitionListItemIdDataProvider()
    {
        return [
            [1, 1, 0],
            [null, 0, 1]
        ];
    }
}
