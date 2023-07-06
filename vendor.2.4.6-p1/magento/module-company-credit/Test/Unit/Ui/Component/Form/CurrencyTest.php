<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Ui\Component\Form;

use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Ui\Component\Form\Currency;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CurrencyTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    private $context;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagement;

    /**
     * @var CurrencyInterface|MockObject
     */
    private $localeCurrency;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->context = $this->createMock(
            ContextInterface::class
        );
        $processor = $this->createMock(
            Processor::class
        );
        $this->context->expects(static::atLeastOnce())->method('getProcessor')->willReturn($processor);
        $this->uiComponentFactory = $this->createMock(
            UiComponentFactory::class
        );
        $this->storeManager = $this->createMock(
            StoreManagerInterface::class
        );
        $this->request = $this->createMock(
            RequestInterface::class
        );
        $this->creditLimitManagement = $this->createPartialMock(
            CreditLimitManagementInterface::class,
            ['getCreditByCompanyId']
        );
        $this->localeCurrency = $this->createMock(
            CurrencyInterface::class
        );
        $objectManager = new ObjectManager($this);
        $this->currency = $objectManager->getObject(
            Currency::class,
            [
                'context' => $this->context,
                'uiComponentFactory' => $this->uiComponentFactory,
                'storeManager' => $this->storeManager,
                'request' => $this->request,
                'creditLimitManagement' => $this->creditLimitManagement,
                'localeCurrency' => $this->localeCurrency,
            ]
        );
    }

    /**
     * Test prepare method.
     *
     * @param array $configData
     * @return void
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(array $configData)
    {
        $baseCurrencyCode = 'USD';
        $companyId = 1;
        $creditLimitCurrencyCode = 'EUR';
        $creditLimitCurrencyLabel = 'Euro';
        $uiComponent = $this->createMock(
            UiComponentInterface::class
        );
        $context = $this->createMock(
            ContextInterface::class
        );
        $context->method('getNamespace')->willReturn('namespace');
        $uiComponent->method('getContext')->willReturn($context);
        $uiComponent->method('getData')->willReturn($configData['config']);
        $this->uiComponentFactory->method('create')->willReturn($uiComponent);
        $website = $this->createMock(
            Website::class
        );
        $website->expects(static::once())->method('getBaseCurrencyCode')->willReturn($baseCurrencyCode);
        $this->storeManager->expects(static::once())->method('getWebsite')->willReturn($website);
        $this->request->expects(static::once())->method('getParam')->with('id')->willReturn($companyId);
        $creditLimit = $this->createMock(
            CreditLimitInterface::class
        );
        $creditLimit->method('getCurrencyCode')->willReturn($creditLimitCurrencyCode);
        $this->creditLimitManagement->expects(static::any())->method('getCreditByCompanyId')
            ->with($companyId)
            ->willReturn($creditLimit);
        $currency = $this->createMock(
            \Magento\Framework\Currency::class
        );
        $currency->method('getName')->willReturn($creditLimitCurrencyLabel);
        $this->localeCurrency->method('getCurrency')
            ->with($creditLimitCurrencyCode)
            ->willReturn($currency);
        $this->currency->setData($configData);
        $this->currency->prepare();
        $this->assertEquals(
            $this->currency->getData('config/options/0/value'),
            $creditLimitCurrencyCode
        );
    }

    /**
     * DataProvider for prepare method.
     *
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            [
                [
                    'config' => [
                        'formElement' => 'element'
                    ]
                ]

            ],
            [
                [
                    'config' => [
                        'formElement' => 'element',
                        'options' => [0 => ['value' => 'EUR']]
                    ]
                ]
            ]
        ];
    }
}
