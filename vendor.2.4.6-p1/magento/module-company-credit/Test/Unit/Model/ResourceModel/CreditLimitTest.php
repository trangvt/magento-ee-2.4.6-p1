<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model\ResourceModel;

use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\ResourceModel\CreditLimit;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditLimitTest extends TestCase
{
    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrency;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connection;

    /**
     * @var CreditLimit
     */
    private $creditLimit;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->priceCurrency = $this->createMock(
            PriceCurrencyInterface::class
        );
        $resources = $this->createMock(
            ResourceConnection::class
        );
        $this->connection = $this->createMock(
            AdapterInterface::class
        );
        $resources->expects($this->atLeastOnce())->method('getConnection')->willReturn($this->connection);

        $objectManager = new ObjectManager($this);
        $this->creditLimit = $objectManager->getObject(
            CreditLimit::class,
            [
                'priceCurrency' => $this->priceCurrency,
                '_resources' => $resources,
            ]
        );
    }

    /**
     * Test for method changeBalance.
     *
     * @return void
     */
    public function testChangeBalance()
    {
        $creditId = 1;
        $value = 15;
        $convertedValue = 12;
        $currency = 'USD';
        $condition = 'entity_id=' . $creditId;
        $rowData = [
            CreditLimitInterface::BALANCE => 25,
            CreditLimitInterface::CURRENCY_CODE => 'EUR',
        ];

        $this->connection->expects($this->once())
            ->method('quoteInto')->with('entity_id=?', $creditId)->willReturn($condition);
        $select = $this->createMock(Select::class);
        $this->connection->expects($this->once())->method('select')->willReturn($select);
        $select->expects($this->once())->method('from')->willReturnSelf();
        $select->expects($this->once())->method('where')->with($condition)->willReturnSelf();
        $this->connection->expects($this->once())->method('fetchRow')->willReturn($rowData);
        $operationCurrency = $this->createMock(Currency::class);
        $this->priceCurrency->expects($this->once())
            ->method('getCurrency')->with(true, $currency)->willReturn($operationCurrency);
        $operationCurrency->expects($this->once())->method('convert')->with(
            $value,
            $rowData[CreditLimitInterface::CURRENCY_CODE]
        )->willReturn($convertedValue);
        $operationCurrency->expects($this->once())->method('getRate')
            ->with($rowData[CreditLimitInterface::CURRENCY_CODE])
            ->willReturn(0.7);
        $this->connection->expects($this->once())->method('update')->with(
            null,
            [CreditLimitInterface::BALANCE => 37],
            $condition
        );
        $this->creditLimit->changeBalance($creditId, $value, $currency);
    }
}
