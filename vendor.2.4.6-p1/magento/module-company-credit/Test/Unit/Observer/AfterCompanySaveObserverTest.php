<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Observer;

use Magento\Company\Api\Data\CompanyInterface;
use Magento\CompanyCredit\Api\CreditLimitManagementInterface;
use Magento\CompanyCredit\Api\CreditLimitRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterfaceFactory;
use Magento\CompanyCredit\Model\CreditCurrency;
use Magento\CompanyCredit\Model\CreditLimit;
use Magento\CompanyCredit\Model\CreditLimitHistory;
use Magento\CompanyCredit\Observer\AfterCompanySaveObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Magento\CompanyCredit\Observer\AfterCompanySaveObserver class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AfterCompanySaveObserverTest extends TestCase
{
    /**
     * @var AfterCompanySaveObserver
     */
    private $afterCompanySaveObserver;

    /**
     * @var CreditLimitRepositoryInterface|MockObject
     */
    private $creditLimitRepository;

    /**
     * @var CreditLimitManagementInterface|MockObject
     */
    private $creditLimitManagement;

    /**
     * @var CreditLimitHistory|MockObject
     */
    private $creditLimitHistory;

    /**
     * @var CreditCurrency|MockObject
     */
    private $creditCurrency;

    /**
     * @var ResolverInterface|MockObject
     */
    private $localeResolver;

    /**
     * @var CreditLimitInterfaceFactory|MockObject
     */
    private $creditLimitFactory;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->creditLimitRepository = $this
            ->getMockBuilder(CreditLimitRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitManagement = $this
            ->getMockBuilder(CreditLimitManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditLimitHistory = $this->getMockBuilder(CreditLimitHistory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->setMethods(['getRequest', 'getCompany'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeResolver = $this->getMockBuilder(ResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creditCurrency = $this->getMockBuilder(CreditCurrency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditLimitFactory = $this
            ->getMockBuilder(CreditLimitInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->afterCompanySaveObserver = $objectManager->getObject(
            AfterCompanySaveObserver::class,
            [
                'creditLimitHistory' => $this->creditLimitHistory,
                'creditLimitRepository' => $this->creditLimitRepository,
                'creditLimitManagement' => $this->creditLimitManagement,
                'localeResolver' => $this->localeResolver,
                'creditCurrency' => $this->creditCurrency,
                'creditLimitFactory' => $this->creditLimitFactory,
            ]
        );
    }

    /**
     * Test execute method.
     *
     * @param int|null $companyCreditLimitValue
     * @param string $code
     * @param float|null $rate
     * @param $invokesMatched
     * @param $saveInvoked
     * @dataProvider executeDataProvider
     * @return void
     */
    public function testExecute(
        $companyCreditLimitValue,
        $code,
        $rate,
        $invokesMatched,
        $saveInvoked
    ) {
        $creditLimitId = 1;
        $companyCreditCurrencyCode ='USD';
        $params = [
            'company_credit' => [
                CreditLimitInterface::EXCEED_LIMIT => 1,
                CreditLimitInterface::CURRENCY_CODE => $companyCreditCurrencyCode,
                CreditLimitInterface::CREDIT_LIMIT => $companyCreditLimitValue,
                'credit_comment' => 'test',
                'currency_rate' => $rate,
            ]
        ];
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->observer->expects($this->once())->method('getRequest')->willReturn($request);
        $this->observer->expects($this->once())->method('getCompany')->willReturn($company);
        $request->expects($this->once())->method('getParams')->willReturn($params);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with(1)->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getCurrencyCode')->willReturn($code);
        $creditLimit->expects($this->atLeastOnce())->method('getId')->willReturn($creditLimitId);
        $creditData = $params['company_credit'];
        $extraCreditData = [
            CreditLimitInterface::CREDIT_ID => $creditLimitId,
            CreditLimitInterface::COMPANY_ID => 1
        ];
        array_merge($creditData, $extraCreditData);

        $creditLimit->expects($this->once())->method('setData')->with();
        $creditLimit->expects($this->once())->method('setExceedLimit')->with(true);

        if ($companyCreditCurrencyCode === $code) {
            $this->creditLimitRepository->expects($saveInvoked)->method('save')->with($creditLimit);
        }
        $creditLimit->expects($this->once())->method('setCreditLimit')->with((int) $companyCreditLimitValue);

        $this->localeResolver->expects($invokesMatched)->method('getLocale')->willReturn('en_US');

        $this->afterCompanySaveObserver->execute($this->observer);
    }

    /**
     * Data provide for execute method..
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [100, 'USD', 1, $this->once(), $this->once()],
            [null, 'USD', null, $this->never(), $this->once()],
            [null, 'EUR', 1.12, $this->never(), $this->never()]
        ];
    }

    /**
     * Test for execute method with Localized exception.
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('Please enter a valid EUR/USD currency rate.');
        $params = [
            'company_credit' => [
                CreditLimitInterface::CURRENCY_CODE => 'USD',
                'credit_comment' => 'test',
                'currency_rate' => -1,
            ]
        ];
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer->expects($this->once())->method('getRequest')->willReturn($request);
        $request->expects($this->once())->method('getParams')->willReturn($params);
        $this->observer->expects($this->once())->method('getCompany')->willReturn($company);
        $company->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')
            ->with(1)
            ->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('getCurrencyCode')->willReturn('EUR');

        $this->afterCompanySaveObserver->execute($this->observer);
    }

    /**
     * Test for execute method with NoSuchEntity exception.
     *
     * @return void
     */
    public function testExecuteWithNoSuchEntityException()
    {
        $creditLimitId = 1;
        $companyCreditCurrencyCode ='USD';
        $params = [
            'company_credit' => [
                CreditLimitInterface::EXCEED_LIMIT => 1,
                CreditLimitInterface::CURRENCY_CODE => $companyCreditCurrencyCode,
                CreditLimitInterface::CREDIT_LIMIT => 100,
                'credit_comment' => 'test',
                'currency_rate' => 1,
            ]
        ];
        $exception = new NoSuchEntityException();
        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();
        $company = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $creditLimit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $company->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $this->observer->expects($this->once())->method('getRequest')->willReturn($request);
        $this->observer->expects($this->once())->method('getCompany')->willReturn($company);
        $request->expects($this->once())->method('getParams')->willReturn($params);
        $this->creditLimitManagement->expects($this->once())
            ->method('getCreditByCompanyId')->with(1)->willThrowException($exception);
        $this->creditLimitFactory->expects($this->once())->method('create')->willReturn($creditLimit);
        $creditLimit->expects($this->once())->method('setCompanyId')->with(1)->willReturnSelf();
        $creditLimit->expects($this->once())->method('getCurrencyCode')->willReturn('USD');
        $creditLimit->expects($this->atLeastOnce())->method('getId')->willReturn($creditLimitId);
        $creditData = $params['company_credit'];
        $extraCreditData = [
            CreditLimitInterface::CREDIT_ID => $creditLimitId,
            CreditLimitInterface::COMPANY_ID => 1
        ];
        array_merge($creditData, $extraCreditData);

        $creditLimit->expects($this->once())->method('setData')->with();
        $creditLimit->expects($this->once())->method('setExceedLimit')->with(true);
        $this->creditLimitRepository->expects($this->once())->method('save')->with($creditLimit);
        $creditLimit->expects($this->once())->method('setCreditLimit')->with(100);

        $this->localeResolver->expects($this->once())->method('getLocale')->willReturn('en_US');

        $this->afterCompanySaveObserver->execute($this->observer);
    }
}
