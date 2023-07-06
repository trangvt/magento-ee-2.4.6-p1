<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\Form;

use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\CompanyCredit\Ui\Component\Form\AmountField;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for AmountField.
 */
class AmountFieldTest extends TestCase
{
    /**
     * @var AmountField
     */
    private $amountField;

    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactory;

    /**
     * @var UiComponentInterface|MockObject
     */
    private $wrappedComponent;

    /**
     * @var ContextInterface|MockObject
     */
    private $context;

    /**
     * @var Currency|MockObject
     */
    private $currencyFormatter;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditDataProvider = $this->getMockBuilder(CreditDataProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->priceCurrency = $this->getMockBuilder(PriceCurrencyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->websiteCurrency = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->wrappedComponent = $this->getMockBuilder(UiComponentInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->uiComponentFactory = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->currencyFormatter = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->amountField = $objectManager->getObject(
            AmountField::class,
            [
                'context' => $this->context,
                'creditDataProvider' => $this->creditDataProvider,
                'priceCurrency' => $this->priceCurrency,
                'websiteCurrency' => $this->websiteCurrency,
                'uiComponentFactory' => $this->uiComponentFactory,
                'currencyFormatter' => $this->currencyFormatter
            ]
        );
        $this->amountField->setData(
            'config',
            [
                'formElement' => '1',
                'defaultFieldValue' => 0
            ]
        );
    }

    /**
     * Test method for prepare.
     *
     * @return void
     */
    public function testPrepareWithCredit()
    {
        $formattedDefaultFieldValue = '0.00';
        $contextComponent = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->uiComponentFactory->expects($this->once())->method('create')->willReturn($this->wrappedComponent);
        $this->wrappedComponent->expects($this->once())->method('getContext')->willReturn($contextComponent);
        $this->context->expects($this->atLeastOnce())->method('getRequestParam')->with('id')->willReturn(1);
        $creditData = $this->getMockBuilder(CreditDataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditData->expects($this->atLeastOnce())->method('getCurrencyCode')->willReturn('USD');
        $this->creditDataProvider->expects($this->once())->method('get')->with(1)->willReturn($creditData);
        $this->priceCurrency->expects($this->once())->method('getCurrencySymbol')->willReturn('$');
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processor);
        $this->currencyFormatter->expects($this->atLeastOnce())->method('formatTxt')
            ->willReturn($formattedDefaultFieldValue);
        $this->amountField->prepare();
        $expected = [
            'addbefore' => '$',
            'value' => $formattedDefaultFieldValue
        ];

        $this->assertEquals($expected, $this->amountField->getData('config'));
    }

    /**
     * Test method for prepare.
     *
     * @return void
     */
    public function testPrepareWithoutCredit()
    {
        $formattedDefaultFieldValue = '0.00';
        $contextComponent = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->uiComponentFactory->expects($this->once())->method('create')->willReturn($this->wrappedComponent);
        $this->wrappedComponent->expects($this->once())->method('getContext')->willReturn($contextComponent);
        $this->context->expects($this->atLeastOnce())->method('getRequestParam')->with('id')->willReturn(1);
        $creditData = $this->getMockBuilder(CreditDataInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditData->expects($this->atLeastOnce())->method('getCurrencyCode')->willReturn(null);
        $this->creditDataProvider->expects($this->once())->method('get')->with(1)->willReturn($creditData);
        $this->priceCurrency->expects($this->once())->method('getCurrencySymbol')->willReturn('$');
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->atLeastOnce())->method('getProcessor')->willReturn($processor);
        $this->currencyFormatter->expects($this->atLeastOnce())->method('formatTxt')
            ->willReturn($formattedDefaultFieldValue);
        $this->amountField->prepare();
        $expected = [
            'addbefore' => '$',
            'value' => $formattedDefaultFieldValue
        ];

        $this->assertEquals($expected, $this->amountField->getData('config'));
    }
}
