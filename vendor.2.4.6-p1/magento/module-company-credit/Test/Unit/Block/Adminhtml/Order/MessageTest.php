<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Block\Adminhtml\Order;

use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Block\Adminhtml\Order\Message;
use Magento\CompanyCredit\Model\CompanyCreditPaymentConfigProvider;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtensionInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for block Message.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MessageTest extends TestCase
{
    /**
     * @var CreditLimitInterface|MockObject
     */
    private $creditLimit;

    /**
     * @var CreditDataProviderInterface|MockObject
     */
    private $creditDataProvider;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceFormatter;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var OrderInterface|MockObject
     */
    private $order;

    /**
     * @var Message
     */
    private $message;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->creditLimit = $this->createMock(
            CreditLimitInterface::class
        );
        $this->creditDataProvider = $this->createMock(
            CreditDataProviderInterface::class
        );
        $this->priceFormatter = $this->createMock(
            PriceCurrencyInterface::class
        );
        $this->customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->companyRepository = $this->createMock(
            CompanyRepositoryInterface::class
        );

        $this->order = $this->getMockForAbstractClass(OrderInterface::class);
        $coreRegistry = $this->createMock(Registry::class);
        $coreRegistry->expects($this->atLeastOnce())->method('registry')
            ->with('current_order')->willReturn($this->order);

        $objectManager = new ObjectManager($this);
        $this->message = $objectManager->getObject(
            Message::class,
            [
                'creditLimit' => $this->creditLimit,
                'creditDataProvider' => $this->creditDataProvider,
                'priceFormatter' => $this->priceFormatter,
                'customerRepository' => $this->customerRepository,
                'companyRepository' => $this->companyRepository,
                '_coreRegistry' => $coreRegistry,
            ]
        );
    }

    /**
     * Test for method isPayOnAccountMethod.
     *
     * @return void
     */
    public function testIsPayOnAccountMethod()
    {
        $payment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $this->order->expects($this->once())->method('getPayment')->willReturn($payment);
        $payment->expects($this->once())->method('getMethod')
            ->willReturn(CompanyCreditPaymentConfigProvider::METHOD_NAME);
        $this->assertTrue($this->message->isPayOnAccountMethod());
    }

    /**
     * Test for method formatPrice.
     *
     * @return void
     */
    public function testFormatPrice()
    {
        $price = 17;
        $currencyCode = 'USD';
        $expectedValue = '$' . $price . '.00';
        $credit = $this->prepareMocks(2);
        $credit->expects($this->once())->method('getCurrencyCode')->willReturn($currencyCode);
        $credit->expects($this->once())->method('getId')->willReturn(1);
        $this->priceFormatter->expects($this->once())->method('format')
            ->with($price, false, 2, null, $currencyCode)->willReturn($expectedValue);
        $this->assertEquals($expectedValue, $this->message->formatPrice($price));
    }

    /**
     * Test for method formatPrice with exception.
     *
     * @return void
     */
    public function testFormatPriceWithException()
    {
        $customerId = 1;
        $price = 17;
        $expectedValue = '$' . $price . '.00';
        $this->order->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->with($customerId)
            ->willThrowException(new NoSuchEntityException());
        $this->priceFormatter->expects($this->once())->method('format')
            ->with($price, false, 2, null, null)->willReturn($expectedValue);
        $this->assertEquals($expectedValue, $this->message->formatPrice($price));
    }

    /**
     * Test for method formatPrice without credit.
     *
     * @return void
     */
    public function testFormatPriceWithoutCredit()
    {
        $price = 17;
        $expectedValue = '$' . $price . '.00';
        $credit = $this->prepareMocks(2);
        $credit->expects($this->once())->method('getId')->willReturn(0);
        $this->priceFormatter->expects($this->once())->method('format')
            ->with($price, false, 2, null, null)->willReturn($expectedValue);
        $this->assertEquals($expectedValue, $this->message->formatPrice($price));
    }

    /**
     * Test for method getCompanyName.
     *
     * @return void
     */
    public function testGetCompanyName()
    {
        $companyId = 2;
        $companyName = 'Some Company';
        $credit = $this->prepareMocks($companyId);
        $credit->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $credit->expects($this->once())->method('getId')->willReturn(1);
        $company = $this->getMockForAbstractClass(CompanyInterface::class);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)->willReturn($company);
        $company->expects($this->once())->method('getCompanyName')->willReturn($companyName);
        $this->assertEquals($companyName, $this->message->getCompanyName());
    }

    /**
     * Test for method getCompanyName with exception.
     *
     * @return void
     */
    public function testGetCompanyNameWithException()
    {
        $companyId = 2;
        $credit = $this->prepareMocks($companyId);
        $credit->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $credit->expects($this->once())->method('getId')->willReturn(1);
        $this->companyRepository->expects($this->once())->method('get')->with($companyId)
            ->willThrowException(new NoSuchEntityException());
        $this->assertEquals('', $this->message->getCompanyName());
    }

    /**
     * Prepare mocks for getCredit method and return credit mock.
     *
     * @param int $companyId
     * @return MockObject
     */
    private function prepareMocks($companyId)
    {
        $customerId = 1;
        $customer = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->order->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepository->expects($this->once())->method('getById')->with($customerId)->willReturn($customer);
        $customerExtensionAttributes = $this->getMockForAbstractClass(
            CustomerExtensionInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['setCompanyAttributes', 'getCompanyAttributes']
        );
        $companyAttributes = $this->createMock(
            CompanyCustomerInterface::class
        );
        $customer->expects($this->once())->method('getExtensionAttributes')->willReturn($customerExtensionAttributes);
        $customerExtensionAttributes->expects($this->any())->method('getCompanyAttributes')
            ->willReturn($companyAttributes);
        $companyAttributes->expects($this->once())->method('getCompanyId')->willReturn($companyId);
        $credit = $this->getMockForAbstractClass(CreditDataInterface::class);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($credit);
        return $credit;
    }
}
