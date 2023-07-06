<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Model\ResourceModel\ProductItem\Price;

use Magento\Catalog\Api\Data\PriceUpdateResultInterface;
use Magento\Catalog\Api\Data\TierPriceInterface;
use Magento\Catalog\Api\Data\TierPriceInterfaceFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\Price\PriceProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PriceProcessor.
 */
class PriceProcessorTest extends TestCase
{
    /**
     * @var TierPriceInterfaceFactory|MockObject
     */
    private $tierPriceFactory;

    /**
     * @var PriceProcessor
     */
    private $priceProcessor;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tierPriceFactory = $this->getMockBuilder(TierPriceInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->priceProcessor = $objectManagerHelper->getObject(
            PriceProcessor::class,
            [
                'tierPriceFactory' => $this->tierPriceFactory
            ]
        );
    }

    /**
     * Test for createPricesUpdate().
     *
     * @param string $priceType
     * @return void
     * @dataProvider priceTypeDataProvider
     */
    public function testCreatePricesUpdate($priceType)
    {
        $operationData = $this->getPricesData($priceType);
        $this->preparePriceUpdateMock($priceType);
        $pricesUpdates = $this->priceProcessor->createPricesUpdate($operationData);

        $this->assertInstanceOf(
            TierPriceInterface::class,
            $pricesUpdates[0]
        );
    }

    /**
     * Test for createPricesDelete().
     *
     * @param string $priceType
     * @return void
     * @dataProvider priceTypeDataProvider
     */
    public function testCreatePricesDelete($priceType)
    {
        $operationData = $this->getPricesData($priceType);
        $this->preparePriceUpdateMock($priceType);
        $pricesDeleteData = $this->priceProcessor->createPricesDelete($operationData);

        $this->assertInstanceOf(
            TierPriceInterface::class,
            $pricesDeleteData[0]
        );
    }

    /**
     * Test for prepareErrorMessage().
     *
     * @return void
     */
    public function testPrepareErrorMessage()
    {
        $message = '% placeholder message';
        $value = 'error';
        $placeholder = ' placeholder';
        $resultMessage = 'error message';
        $result = $this->getMockBuilder(PriceUpdateResultInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $result->expects($this->atLeastOnce())->method('getMessage')->willReturn($message);
        $result->expects($this->atLeastOnce())->method('getParameters')->willReturn([$placeholder => $value]);

        $this->assertEquals($resultMessage, $this->priceProcessor->prepareErrorMessage($result));
    }

    /**
     * Prepare price update mock.
     *
     * @param string $priceType
     * @return void
     */
    private function preparePriceUpdateMock($priceType)
    {
        $priceDto = $this->getMockBuilder(TierPriceInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $priceDto->expects($this->atLeastOnce())->method('setWebsiteId')->willReturnSelf();
        $priceDto->expects($this->atLeastOnce())->method('setSku')->willReturnSelf();
        $priceDto->expects($this->atLeastOnce())->method('setCustomerGroup')->willReturnSelf();
        $priceDto->expects($this->atLeastOnce())->method('setQuantity')->willReturnSelf();
        $priceDto->expects($this->atLeastOnce())->method('setPrice')->willReturnSelf();
        $priceDto->expects($this->atLeastOnce())->method('setPriceType')->with($priceType)->willReturnSelf();
        $this->tierPriceFactory->expects($this->atLeastOnce())->method('create')->willReturn($priceDto);
    }

    /**
     * Prepare prices data.
     *
     * @param string $priceType
     * @return array
     */
    private function getPricesData($priceType)
    {
        return [
            'product_sku' => 'sku',
            'customer_group' => 4,
            'prices' => [
                [
                    'is_deleted' => false,
                    'website_id' => 1,
                    'qty' => 2,
                    'value_type' => $priceType,
                    'price' => 20,
                    'percentage_value' => 20
                ],
                [
                    'is_deleted' => true,
                    'website_id' => 1,
                    'qty' => 2,
                    'value_type' => $priceType,
                    'price' => 20,
                    'percentage_value' => 20
                ]
            ]
        ];
    }

    /**
     * Price type DataProvider.
     *
     * @return array
     */
    public function priceTypeDataProvider()
    {
        return [
            [TierPriceInterface::PRICE_TYPE_FIXED],
            [TierPriceInterface::PRICE_TYPE_DISCOUNT]
        ];
    }
}
