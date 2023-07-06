<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Plugin\Block\Adminhtml\Sales\Order\Create;

use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\CompanyPayment\Plugin\CustomerBalance\Block\Adminhtml\Sales\Order\Create\PaymentPlugin;
use Magento\Quote\Model\Quote;
use Magento\CustomerBalance\Block\Adminhtml\Sales\Order\Create\Payment;
use Magento\Sales\Model\AdminOrder\Create;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Payment\Model\Method\Free;

class PaymentPluginTest extends TestCase
{
    /**
     * @var Free|MockObject
     */
    private $freeMethod;

    /**
     * @var CanUseForCompany|MockObject
     */
    private $canUseForCompany;

    /**
     * @var Create|MockObject
     */
    private $orderCreate;

    /**
     * @var PaymentPlugin
     */
    private $paymentPlugin;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->canUseForCompany =
            $this->getMockBuilder(CanUseForCompany::class)
                ->onlyMethods(['isApplicable'])
                ->disableOriginalConstructor()
                ->getMock();
        $this->freeMethod =
            $this->createMock(Free::class);
        $this->orderCreate =
            $this->createMock(Create::class);

        $this->paymentPlugin = new PaymentPlugin(
            $this->canUseForCompany,
            $this->orderCreate,
            $this->freeMethod
        );
    }

    /**
     * Test for method afterCanUseCustomerBalance.
     *
     * @return void
     */
    public function testAfterCanUseCustomerBalance()
    {

        $subject = $this->createMock(Payment::class);
        $initialResult = true;
        $finalResult = false;
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderCreate->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
        $this->canUseForCompany->expects($this->any())
            ->method('isApplicable')
            ->with($this->freeMethod, $quote)
            ->willReturn(false);

        $this->assertEquals($finalResult, $this->paymentPlugin->afterCanUseCustomerBalance($subject, $initialResult));
    }
}
