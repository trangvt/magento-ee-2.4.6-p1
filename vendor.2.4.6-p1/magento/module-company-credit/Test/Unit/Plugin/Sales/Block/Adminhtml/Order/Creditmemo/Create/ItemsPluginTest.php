<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Sales\Block\Adminhtml\Order\Creditmemo\Create;

use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\CompanyCredit\Plugin\Sales\Block\Adminhtml\Order\Creditmemo\Create\ItemsPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for add labels to items.
 */
class ItemsPluginTest extends TestCase
{
    /**
     * @var ItemsPlugin
     */
    private $itemsPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->itemsPlugin = $objectManager->getObject(
            ItemsPlugin::class
        );
    }

    /**
     * Test for beforeToHtml method.
     *
     * @return void
     */
    public function testBeforeToHtml()
    {
        $subject = $this->createMock(
            Items::class
        );
        $refundBtn = $this->getMockBuilder(AbstractBlock::class)
            ->addMethods(['setLabel'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $orderPayment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $subject->expects($this->once())->method('getOrder')->willReturn($order);
        $order->expects($this->once())->method('getPayment')->willReturn($orderPayment);
        $orderPayment->expects($this->once())
            ->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $subject->expects($this->once())->method('getChildBlock')->with('submit_offline')->willReturn($refundBtn);
        $refundBtn->expects($this->once())->method('setLabel')->with(__('Refund to Company Credit'))->willReturnSelf();
        $this->itemsPlugin->beforeToHtml($subject);
    }
}
