<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyPayment\Test\Unit\Plugin\Checkout\Onepage\Payment;

use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\CompanyPayment\Plugin\CustomerBalance\Block\Checkout\Onepage\Payment\AdditionalPlugin;
use Magento\CustomerBalance\Block\Checkout\Onepage\Payment\Additional;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\AdminOrder\Create;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Payment\Model\Method\Free;

class AdditionalPluginTest extends TestCase
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
     * @var Add
     */
    private $additionalPlugin;

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

        $this->additionalPlugin = new AdditionalPlugin(
            $this->canUseForCompany,
            $this->freeMethod
        );
    }

    /**
     * Test for method afterIsAllowed.
     *
     * @return void
     */
    public function testAfterIsAllowed()
    {

        $subject = $this->createMock(Additional::class);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
        $initialResult = true;
        $finalResult = false;
        $this->canUseForCompany->expects($this->any())
            ->method('isApplicable')
            ->with($this->freeMethod, $quote)
            ->willReturn(false);

        $this->assertEquals($finalResult, $this->additionalPlugin->afterIsAllowed($subject, $initialResult));
    }
}
