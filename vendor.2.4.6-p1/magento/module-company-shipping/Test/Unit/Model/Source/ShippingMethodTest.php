<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyShipping\Test\Unit\Model\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\CompanyShipping\Model\Source\ShippingMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CompanyShipping\Test\Unit\Model\Source\ShippingMethodTest
 */
class ShippingMethodTest extends TestCase
{
    /**
     * @var ShippingMethod
     */
    private $shippingMethod;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    /**
     * @var CarrierFactory|MockObject
     */
    private $carrierFactory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->getMockForAbstractClass();
        $this->carrierFactory = $this->getMockBuilder(CarrierFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethod = new ShippingMethod(
            $this->scopeConfig,
            $this->carrierFactory
        );
    }

    /**
     * @param array $carriers
     * @param array $enabledCarriersModule
     * @param array $optionsExpected
     * @return void
     * @dataProvider toOptionArrayDataProvider
     */
    public function testToOptionArray(array $carriers, array $enabledCarriersModule, array $optionsExpected): void
    {
        $this->scopeConfig->expects($this->once())->method('getValue')->with('carriers')->willReturn($carriers);
        $this->carrierFactory->expects($this->any())
            ->method('create')
            ->willReturnMap($enabledCarriersModule);
        $this->assertEquals(
            $optionsExpected,
            $this->shippingMethod->toOptionArray()
        );
    }

    /**
     * @return array[]
     */
    public function toOptionArrayDataProvider(): array
    {
        return [
            [
                'carriers' => [
                    'dhl' => ['active' => 1, 'title' => 'DHL'],
                    'ups' => ['active' => 1, 'title' => 'UPS'],
                    'fedex' => ['active' => 0, 'title' => 'Fedex'],
                ],
                'enabledCarriersModule' => [
                    ['dhl', null, false],
                    ['ups', null, true],
                    ['fedex', null, true],
                ],
                'optionsExpected' => [
                    ['value' => 'ups', 'label' => 'UPS'],
                    ['value' => 'fedex', 'label' => 'Fedex (disabled)'],
                ]
            ]
        ];
    }
}
