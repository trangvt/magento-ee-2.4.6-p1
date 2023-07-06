<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\CompanyPayment\Test\Unit\Plugin\Checkout\Block;

use Magento\CompanyPayment\Model\Payment\Checks\CanUseForCompany;
use Magento\CompanyPayment\Plugin\Checkout\Block\LayoutProcessorPlugin;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session;
use Magento\Payment\Model\Method\Free;

class LayoutProcessorPluginTest extends TestCase
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
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var LayoutProcessorPlugin
     */
    private $layoutProcessorPlugin;

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
        $this->checkoutSession =
            $this->createMock(Session::class);
        $this->arrayManager =
            $this->createMock(ArrayManager::class);

        $this->layoutProcessorPlugin = new LayoutProcessorPlugin(
            $this->canUseForCompany,
            $this->checkoutSession,
            $this->freeMethod,
            $this->arrayManager
        );
    }

    /**
     * Test for method afterProcess.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testAfterProcess()
    {
        $subject = $this->createMock(LayoutProcessor::class);
        $initialResult = $finalResult = [];
        $initialResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['afterMethods']['children']['storeCredit'] = [];
        $finalResult['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['afterMethods']['children'] = [];
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSession->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
        $this->canUseForCompany->expects($this->any())
            ->method('isApplicable')
            ->with($this->freeMethod, $quote)
            ->willReturn(false);
        $this->arrayManager->expects($this->once())
            ->method('findPath')
            ->with('storeCredit', $initialResult)
            ->willReturn(
                'components/checkout/children/steps/children/billing-step/children/' .
                'payment/children/afterMethods/children/storeCredit'
            );
        $this->arrayManager->expects($this->once())
            ->method('remove')
            ->willReturn($finalResult);

        $this->assertEquals($finalResult, $this->layoutProcessorPlugin->afterProcess($subject, $initialResult));
    }
}
