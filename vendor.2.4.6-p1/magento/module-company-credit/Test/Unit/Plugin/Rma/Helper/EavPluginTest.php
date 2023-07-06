<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Rma\Helper;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Plugin\Rma\Helper\EavPlugin;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Helper\Eav;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for attribute option values.
 */
class EavPluginTest extends TestCase
{
    /**
     * @var Registry|MockObject
     */
    private $coreRegistry;

    /**
     * @var EavPlugin
     */
    private $eavPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->coreRegistry = $this->createMock(
            Registry::class
        );

        $objectManager = new ObjectManager($this);
        $this->eavPlugin = $objectManager->getObject(
            EavPlugin::class,
            [
                'coreRegistry' => $this->coreRegistry,
            ]
        );
    }

    /**
     * Test for aroundGetAttributeOptionValues method.
     *
     * @return void
     */
    public function testAroundGetAttributeOptionValues()
    {
        $result = ['Option 1', 'Option 2', 'Store Credit'];
        $helper = $this->createMock(Eav::class);
        $method = function () use ($result) {
            return $result;
        };
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $this->coreRegistry->expects($this->once())->method('registry')->with('current_order')->willReturn($order);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $this->assertEquals(
            array_slice($result, 0, 2),
            $this->eavPlugin->aroundGetAttributeOptionValues($helper, $method, 'resolution')
        );
    }
}
