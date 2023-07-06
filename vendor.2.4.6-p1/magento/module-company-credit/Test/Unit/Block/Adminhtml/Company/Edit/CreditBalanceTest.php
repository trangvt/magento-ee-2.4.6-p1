<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Block\Adminhtml\Company\Edit;

use Magento\CompanyCredit\Api\CreditDataProviderInterface;
use Magento\CompanyCredit\Api\Data\CreditDataInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Block\Adminhtml\Company\Edit\CreditBalance;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditBalanceTest extends TestCase
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
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var CreditDataInterface|MockObject
     */
    private $credit;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrency;

    /**
     * @var CreditBalance
     */
    private $creditBalance;

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
        $this->websiteCurrency = $this->createMock(
            WebsiteCurrency::class
        );
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->credit = $this->getMockForAbstractClass(CreditDataInterface::class);

        $objectManager = new ObjectManager($this);
        $this->creditBalance = $objectManager->getObject(
            CreditBalance::class,
            [
                'creditLimit' => $this->creditLimit,
                'creditDataProvider' => $this->creditDataProvider,
                'priceFormatter' => $this->priceFormatter,
                'websiteCurrency' => $this->websiteCurrency,
                '_request' => $this->request,
            ]
        );
    }

    /**
     * Test for method getOutstandingBalance.
     *
     * @return void
     */
    public function testGetOutstandingBalance()
    {
        $value = -5;
        $expectedValue = $this->prepareMocks($value);
        $this->credit->expects($this->once())->method('getBalance')->willReturn($value);
        $this->assertEquals($expectedValue, $this->creditBalance->getOutstandingBalance());
    }

    /**
     * Test for method getCreditLimit.
     *
     * @return void
     */
    public function testGetCreditLimit()
    {
        $value = 20;
        $expectedValue = $this->prepareMocks($value);
        $this->credit->expects($this->once())->method('getCreditLimit')->willReturn($value);
        $this->assertEquals($expectedValue, $this->creditBalance->getCreditLimit());
    }

    /**
     * Test for method getAvailableCredit.
     *
     * @return void
     */
    public function testGetAvailableCredit()
    {
        $value = 15;
        $expectedValue = $this->prepareMocks($value);
        $this->credit->expects($this->once())->method('getAvailableLimit')->willReturn($value);
        $this->assertEquals($expectedValue, $this->creditBalance->getAvailableCredit());
    }

    /**
     * Test for method isOutstandingBalanceNegative.
     *
     * @return void
     */
    public function testIsOutstandingBalanceNegative()
    {
        $companyId = 1;
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('id')->willReturn($companyId);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)->willReturn($this->credit);
        $this->credit->expects($this->once())->method('getBalance')->willReturn(-1);
        $this->assertTrue($this->creditBalance->isOutstandingBalanceNegative());
    }

    /**
     * Test for method isOutstandingBalanceNegative with exception.
     *
     * @return void
     */
    public function testIsOutstandingBalanceNegativeWithException()
    {
        $companyId = 1;
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('id')->willReturn($companyId);
        $this->creditDataProvider->expects($this->once())->method('get')->with($companyId)
            ->willThrowException(new NoSuchEntityException());
        $this->assertFalse($this->creditBalance->isOutstandingBalanceNegative());
    }

    /**
     * Prepare mocks and return expected value.
     *
     * @param int $value
     * @return string
     */
    private function prepareMocks($value)
    {
        $companyId = 1;
        $currencyCode = 'USD';
        $expectedValue = '$' . $value . '.00';
        $this->request->expects($this->atLeastOnce())->method('getParam')->with('id')->willReturn($companyId);
        $this->creditDataProvider->expects($this->once())->method('get')
            ->with($companyId)->willReturn($this->credit);
        $this->credit->expects($this->once())->method('getCurrencyCode')->willReturn($currencyCode);
        $this->websiteCurrency->expects($this->once())
            ->method('getCurrencyByCode')->with($currencyCode)->willReturn($currencyCode);
        $this->priceFormatter->expects($this->once())->method('format')
            ->with(
                $value,
                false,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                null,
                $currencyCode
            )->willReturn($expectedValue);
        return $expectedValue;
    }
}
