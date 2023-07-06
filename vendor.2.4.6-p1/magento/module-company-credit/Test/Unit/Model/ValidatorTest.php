<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\Validator;
use Magento\CompanyCredit\Model\WebsiteCurrency;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Validator model.
 */
class ValidatorTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var Validator|MockObject
     */
    private $validator;

    /**
     * @var WebsiteCurrency|MockObject
     */
    private $websiteCurrencyMock;

    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagementMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->websiteCurrencyMock = $this->getMockBuilder(WebsiteCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitManagementMock = $this->getMockBuilder(CreditLimitManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->validator = $this->objectManagerHelper->getObject(
            Validator::class,
            [
                'websiteCurrency' => $this->websiteCurrencyMock,
                'creditLimitManagement' => $this->creditLimitManagementMock
            ]
        );
    }

    /**
     * Test for validateCreditData method.
     *
     * @return void
     */
    public function testValidateCreditData()
    {
        $creditData = [
            'entity_id' => 1,
            'company_id' => 2,
            'currency_code' => 'USD',
            'credit_limit' => 500,
        ];
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagementMock->expects($this->once())
            ->method('getCreditByCompanyId')->with($creditData['company_id'])->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditData['entity_id']);
        $this->websiteCurrencyMock->expects($this->once())
            ->method('isCreditCurrencyEnabled')->with($creditData['currency_code'])->willReturn(true);
        $this->validator->validateCreditData($creditData);
    }

    /**
     * Test for validateCreditData method without company id.
     *
     * @return void
     */
    public function testValidateCreditDataWithoutCompanyId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"company_id" is required. Enter and try again.');
        $this->validator->validateCreditData([]);
    }

    /**
     * Test for validateCreditData method without currency code.
     *
     * @return void
     */
    public function testValidateCreditDataWithoutCurrencyCode()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('"currency_code" is required. Enter and try again.');
        $this->validator->validateCreditData(['company_id' => 1]);
    }

    /**
     * Test for validateCreditData method with different id.
     *
     * @return void
     */
    public function testValidateCreditDataWithDifferentId()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Invalid value of "2" provided for the company_id field.');
        $creditData = [
            'entity_id' => 1,
            'company_id' => 2,
            'currency_code' => 'USD',
        ];
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagementMock->expects($this->once())
            ->method('getCreditByCompanyId')->with($creditData['company_id'])->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn(2);
        $this->validator->validateCreditData($creditData);
    }

    /**
     * Test for validateCreditData method with inactive currency.
     *
     * @return void
     */
    public function testValidateCreditDataWithInactiveCurrency()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Invalid attribute value. Row ID: currency_code = USD.');
        $creditData = [
            'entity_id' => 1,
            'company_id' => 2,
            'currency_code' => 'USD',
        ];
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagementMock->expects($this->once())
            ->method('getCreditByCompanyId')->with($creditData['company_id'])->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditData['entity_id']);
        $this->websiteCurrencyMock->expects($this->once())
            ->method('isCreditCurrencyEnabled')->with($creditData['currency_code'])->willReturn(false);
        $this->validator->validateCreditData($creditData);
    }

    /**
     * Test for validateCreditData method with invalid limit.
     *
     * @return void
     */
    public function testValidateCreditDataWithInvalidLimit()
    {
        $this->expectException('Magento\Framework\Exception\InputException');
        $this->expectExceptionMessage('Invalid attribute value. Row ID: credit_limit = -100.');
        $creditData = [
            'entity_id' => 1,
            'company_id' => 2,
            'currency_code' => 'USD',
            'credit_limit' => -100,
        ];
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagementMock->expects($this->once())
            ->method('getCreditByCompanyId')->with($creditData['company_id'])->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getId')->willReturn($creditData['entity_id']);
        $this->websiteCurrencyMock->expects($this->once())
            ->method('isCreditCurrencyEnabled')->with($creditData['currency_code'])->willReturn(true);
        $this->validator->validateCreditData($creditData);
    }

    /**
     * Test for checkCompanyCreditExist method.
     *
     * @return void
     */
    public function testCheckCompanyCreditExist()
    {
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit->expects($this->once())->method('getId')->willReturn(1);
        $this->validator->checkCompanyCreditExist($creditLimit, 1);
    }

    /**
     * Test for checkCompanyCreditExist method with exception.
     *
     * @return void
     */
    public function testCheckCompanyCreditExistWithException()
    {
        $this->expectException('Magento\Framework\Exception\NoSuchEntityException');
        $this->expectExceptionMessage('Requested company is not found. Row ID: CompanyCreditID = 1.');
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit->expects($this->once())->method('getId')->willReturn(null);
        $this->validator->checkCompanyCreditExist($creditLimit, 1);
    }
}
