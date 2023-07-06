<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Configure;

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Currency;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Configure\Price;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test price column component.
 */
class PriceTest extends TestCase
{
    /**
     * @var Price
     */
    private $column;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * Set up for test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->localeCurrency = $this->getMockBuilder(CurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $context = $this->getMockBuilder(ContextInterface::class)
            ->setMethods(['getProcessor'])->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(Processor::class)
            ->setMethods(['register', 'notify'])->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->never())->method('getProcessor')->willReturn($processor);
        $objectManager = new ObjectManager($this);
        $this->column = $objectManager->getObject(
            Price::class,
            [
                'context' => $context,
                'localeCurrency' => $this->localeCurrency,
                'storeManager' => $this->storeManager,
            ]
        );

        $this->column->setData('name', 'price');
    }

    /**
     * Test prepareDataSource.
     *
     * @return void
     */
    public function testPrepareDataSource()
    {
        $dataSource['data'] = [
            'items' => [
                [
                    'type_id' => BundleType::TYPE_CODE,
                    'price' => 100,
                    'max_price' => 200,
                    'price_type' => 1
                ],
                [
                    'type_id' => BundleType::TYPE_CODE,
                    'price' => 100,
                    'max_price' => 200,
                    'price_view' => 1,
                    'price_type' => 0
                ],
                [
                    'type_id' => BundleType::TYPE_CODE,
                    'price' => 100,
                    'max_price' => 200,
                    'price_type' => 0
                ],
                [
                    'type_id' => ConfigurableType::TYPE_CODE,
                    'price' => 100,
                ],
                [
                    'type_id' => 'simple',
                    'price' => 100,
                ],
            ]
        ];
        $expect['data'] = [
            'items' => [
                [
                    'type_id' => BundleType::TYPE_CODE,
                    'price' => '100.000000',
                    'max_price' => '200.000000',
                    'price_type' => 1
                ],
                [
                    'type_id' => BundleType::TYPE_CODE,
                    'price' => '100.000000',
                    'max_price' => '200.000000',
                    'price_view' => 1,
                    'price_type' => 0
                ],
                [
                    'type_id' => BundleType::TYPE_CODE,
                    'price' => '100.000000',
                    'max_price' => '200.000000',
                    'price_type' => 0
                ],
                [
                    'type_id' => ConfigurableType::TYPE_CODE,
                    'price' => '100.000000',
                ],
                [
                    'type_id' => 'simple',
                    'price' => '100.000000',
                ],
            ]
        ];
        $website = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $website->expects($this->once())->method('getBaseCurrencyCode')->willReturn('USD');
        $this->storeManager->expects($this->once())->method('getWebsite')->willReturn($website);
        $currency->expects($this->atLeastOnce())->method('toCurrency')->willReturnArgument(0);
        $this->localeCurrency->expects($this->once())->method('getCurrency')->with('USD')->willReturn($currency);
        $this->assertEquals($expect, $this->column->prepareDataSource($dataSource));
    }
}
