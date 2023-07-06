<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Test\Unit\Ui\Component\Listing\Column\Configure;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder;
use Magento\SharedCatalog\Ui\Component\Listing\Column\Configure\CustomPrice;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CustomPriceTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    protected $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    protected $uiComponentFactory;

    /**
     * @var UrlBuilder|MockObject
     */
    protected $urlHelper;

    /**
     * @var Currency|MockObject
     */
    protected $priceCurrency;

    /**
     * @var Currency|MockObject
     */
    protected $currency;

    /**
     * @var CustomPrice|MockObject
     */
    protected $customPrice;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Processor|MockObject
     */
    protected $processor;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->processor = $this->createPartialMock(
            Processor::class,
            ['register', 'notify']
        );
        $this->context = $this->getMockForAbstractClass(
            ContextInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getProcessor']
        );
        $this->uiComponentFactory = $this->createMock(
            UiComponentFactory::class
        );
        $this->priceCurrency = $this->getMockForAbstractClass(
            PriceCurrencyInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getCurrency']
        );
        $this->currency = $this->createPartialMock(
            Currency::class,
            ['getCurrencySymbol', 'format']
        );
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')
            ->willReturn($this->currency);
        $this->urlHelper =
            $this->createMock(UrlBuilder::class);
        $this->objectManager = new ObjectManager($this);
    }

    /**
     * Test prepareDataSource() method
     *
     * @dataProvider prepareDataSourceDataProvider
     * @param array $dataSource
     * @param int $formatCalls
     */
    public function testPrepareDataSource($dataSource, $formatCalls)
    {
        $this->context->expects($this->never())->method('getProcessor');
        $data = [];
        $fieldName = 'name';
        $data[$fieldName] = 'field_name';
        $this->customPrice = $this->objectManager->getObject(
            CustomPrice::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlBuilder' => $this->urlHelper,
                'priceCurrency' => $this->priceCurrency,
                'components' => [],
                'data' => $data,
            ]
        );
        $this->currency->expects($this->exactly($formatCalls))
            ->method('format')
            ->with('field_value', ['display' => ''], false)
            ->willReturn(true);
        $this->customPrice->prepareDataSource($dataSource);
    }

    /**
     * @return array
     */
    public function prepareDataSourceDataProvider()
    {
        return [
            'datasource_set_items_set' => [
                'datasource' => [
                    'data' => [
                        'items' => [
                            'item1' => [
                                'field_name' => 'field_value'
                            ],
                            'item2' => [
                                'field_name' => 'field_value'
                            ],
                        ],
                    ]
                ],
                'format_calls' => 2
            ],
            'datasource_not_set' => [
                'datasource' => [],
                'format_calls' => 0
            ],

        ];
    }

    /**
     * Test prepare() method
     */
    public function testPrepare()
    {
        $data = ['config' => []];
        $currencySymbol = 'test currency symbol';
        $this->currency->expects($this->once())
            ->method('getCurrencySymbol')
            ->willReturn($currencySymbol);
        $this->urlHelper =
            $this->createPartialMock(UrlBuilder::class, ['getUrl']);
        $this->context->expects($this->atLeastOnce())->method('getProcessor')->willReturn($this->processor);
        $this->customPrice = $this->objectManager->getObject(
            CustomPrice::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'urlBuilder' => $this->urlHelper,
                'priceCurrency' => $this->priceCurrency,
                'components' => [],
                'data' => $data,
            ]
        );
        $this->customPrice->prepare();
    }
}
