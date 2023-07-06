<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Block\Info;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Block\Info\CompanyCredit;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CompanyCreditTest extends TestCase
{
    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyCredit
     */
    private $companyCredit;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditDataProvider = $this->createMock(
            CreditDataProviderInterface::class
        );
        $this->priceCurrency = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );

        $objectManager = new ObjectManager($this);
        $this->companyCredit = $objectManager->getObject(
            CompanyCredit::class,
            [
                'creditDataProvider' => $this->creditDataProvider,
                'priceCurrency' => $this->priceCurrency,
                'customerRepository' => $this->customerRepository,
            ]
        );
    }

    /**
     * Test for getChargedAmount method.
     *
     * @return void
     */
    public function testGetChargedAmount()
    {
        $storeId = 1;
        $companyId = 2;
        $customerId = 3;
        $grandTotal = 12.5;
        $rate = 1.4;
        $creditCurrency = 'USD';
        $orderCurrency = 'EUR';
        $expectedResult = '$' . ($grandTotal * $rate);
        $info = $this->getMockForAbstractClass(
            InfoInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getOrder']
        );
        $this->companyCredit->setData('info', $info);
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $info->expects($this->exactly(3))->method('getOrder')->willReturn($order);
        $order->expects($this->once())->method('getGrandTotal')->willReturn($grandTotal);
        $order->expects($this->once())->method('getBaseGrandTotal')->willReturn($grandTotal);
        $order->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $order->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->customerRepository->expects($this->once())->method('getById')->with($customerId)->willReturn($customer);
        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['getCompanyAttributes']
        );
        $customer->expects($this->exactly(4))
            ->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $customerExtensionAttributes->expects($this->exactly(3))
            ->method('getCompanyAttributes')->willReturn($companyAttributes);
        $companyAttributes->expects($this->exactly(2))->method('getCompanyId')->willReturn($companyId);
        $creditData = $this->getMockForAbstractClass(CreditDataInterface::class);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($creditData);
        $creditData->expects($this->exactly(5))->method('getCurrencyCode')->willReturn($creditCurrency);
        $order->expects($this->once())->method('getOrderCurrencyCode')->willReturn($orderCurrency);
        $order->expects($this->once())->method('getBaseCurrencyCode')->willReturn($orderCurrency);
        $currency = $this->createPartialMock(
            Currency::class,
            ['getRate', 'convert']
        );
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')->with(true, $orderCurrency)->willReturn($currency);
        $currency->expects($this->once())->method('getRate')->with($creditCurrency)->willReturn($rate);
        $currency->expects($this->once())
            ->method('convert')->with($grandTotal, $creditCurrency)->willReturn($grandTotal * $rate);
        $this->priceCurrency->expects($this->once())->method('format')->with(
            $grandTotal * $rate,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $storeId,
            $creditCurrency
        )->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->companyCredit->getChargedAmount());
    }

    /**
     * Test for getChargedAmount method with empty company id.
     *
     * @return void
     */
    public function testGetChargedAmountWithEmptyCompanyId()
    {
        $storeId = 1;
        $grandTotal = 12.5;
        $expectedResult = '$' . $grandTotal;
        $info = $this->getMockForAbstractClass(
            InfoInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getOrder']
        );
        $this->companyCredit->setData('info', $info);
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $order->expects($this->once())->method('getGrandTotal')->willReturn($grandTotal);
        $order->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $info->expects($this->exactly(2))->method('getOrder')->willReturn($order);
        $creditData = $this->getMockForAbstractClass(CreditDataInterface::class);
        $this->creditDataProvider->expects($this->once())->method('get')->with(0)->willReturn($creditData);
        $creditData->expects($this->exactly(2))->method('getCurrencyCode')->willReturn(null);
        $this->priceCurrency->expects($this->once())->method('format')->with(
            $grandTotal,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $storeId,
            null
        )->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $this->companyCredit->getChargedAmount());
    }
}
