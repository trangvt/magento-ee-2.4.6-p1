<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Plugin\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\ControlsPlugin;
use Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Creditmemo\Controls;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for refund to customer balance.
 */
class ControlsPluginTest extends TestCase
{
    /**
     * @var Registry|MockObject
     */
    private $coreRegistry;

    /**
     * @var ControlsPlugin
     */
    private $controlsPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->coreRegistry = $this->createMock(Registry::class);

        $objectManager = new ObjectManager($this);
        $this->controlsPlugin = $objectManager->getObject(
            ControlsPlugin::class,
            [
                'coreRegistry' => $this->coreRegistry,
            ]
        );
    }

    /**
     * Test for afterCanRefundToCustomerBalance method.
     *
     * @return void
     */
    public function testAfterCanRefundToCustomerBalance()
    {
        $subject = $this->createMock(
            Controls::class
        );
        $creditmemo = $this->getMockForAbstractClass(
            CreditmemoInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getOrder']
        );
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $this->coreRegistry->expects($this->once())
            ->method('registry')->with('current_creditmemo')->willReturn($creditmemo);
        $creditmemo->expects($this->once())->method('getOrder')->willReturn($order);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $this->assertFalse($this->controlsPlugin->afterCanRefundToCustomerBalance($subject, true));
    }
}
