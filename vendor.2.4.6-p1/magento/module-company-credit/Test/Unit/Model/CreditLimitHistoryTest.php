<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\CompanyCredit\Api\Data\CreditLimitInterface;
use Magento\CompanyCredit\Model\Creator;
use Magento\CompanyCredit\Model\CreditLimit;
use Magento\CompanyCredit\Model\CreditLimitHistory;
use Magento\CompanyCredit\Model\History;
use Magento\CompanyCredit\Model\HistoryFactory;
use Magento\CompanyCredit\Model\HistoryHydrator;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\HistoryRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for CreditLimitHistory model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditLimitHistoryTest extends TestCase
{
    /**
     * @var HistoryRepositoryInterface|MockObject
     */
    private $historyRepository;

    /**
     * @var UserContextInterface|MockObject
     */
    private $userContext;

    /**
     * @var CompanyRepositoryInterface|MockObject
     */
    private $companyRepository;

    /**
     * @var HistoryFactory|MockObject
     */
    private $historyFactory;

    /**
     * @var Creator|MockObject
     */
    private $creator;

    /**
     * @var HistoryHydrator|MockObject
     */
    private $historyHydrator;

    /**
     * @var CreditLimitHistory
     */
    private $creditLimitHistory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->historyRepository = $this->getMockBuilder(HistoryRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->historyFactory = $this->getMockBuilder(HistoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->userContext = $this->getMockBuilder(UserContextInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->companyRepository = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->creator = $this->getMockBuilder(Creator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyHydrator = $this->getMockBuilder(HistoryHydrator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->creditLimitHistory = $objectManager->getObject(
            CreditLimitHistory::class,
            [
                'historyRepository' => $this->historyRepository,
                'userContext' => $this->userContext,
                'companyRepository' => $this->companyRepository,
                'creator' => $this->creator,
                'historyFactory' => $this->historyFactory,
                'historyHydrator' => $this->historyHydrator
            ]
        );
    }

    /**
     * Test for method logCredit.
     *
     * @param array $arguments
     * @return void
     * @dataProvider logCreditDataProvider
     */
    public function testLogCredit($arguments)
    {
        $history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->historyHydrator->expects($this->once())->method('hydrate')->willReturn($history);
        $this->historyRepository->expects($this->once())->method('save')->with($history);

        $this->creditLimitHistory->logCredit(...$arguments);
    }

    /**
     * Data provider for testLogCredit.
     *
     * @return array
     */
    public function logCreditDataProvider()
    {
        $creditLimit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        return [
            [[$creditLimit, 1, 100.00, 'USD', 'comment', [], new DataObject()]],
            [[$creditLimit, 1, 200, 'USD', 'comment', [], null]]
        ];
    }

    /**
     * Test for method logUpdateCreditLimit.
     *
     * @return void
     */
    public function testLogUpdateCreditLimit()
    {
        $credit = $this->getMockBuilder(CreditLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $history = $this->getMockBuilder(History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyFactory->expects($this->once())->method('create')->willReturn($history);
        $this->historyHydrator->expects($this->once())->method('hydrate')->willReturn($history);
        $this->historyRepository->expects($this->once())->method('save')->with($history);

        $this->creditLimitHistory->logUpdateCreditLimit($credit, 'comment', []);
    }

    /**
     * Test for prepareChangeCurrencyComment method.
     *
     * @return void
     */
    public function testPrepareChangeCurrencyComment()
    {
        $userId = 1;
        $userName = 'John Doe';
        $from = 'USD';
        $to = 'EUR';
        $rate = '0.7500';
        $this->userContext->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);
        $this->creator->expects($this->once())->method('retrieveCreatorName')
            ->with(UserContextInterface::USER_TYPE_ADMIN, $userId)
            ->willReturn($userName);
        $expectedCommentData = [
            'currency_from' => $from,
            'currency_to' => $to,
            'currency_rate' => number_format((float)$rate, 4),
            'user_name' => $userName,
        ];
        $this->assertEquals(
            $expectedCommentData,
            $this->creditLimitHistory->prepareChangeCurrencyComment($from, $to, $rate)
        );
    }

    /**
     * Test `logUpdateItem` with new credit.
     *
     * @return void
     */
    public function testLogUpdateItemWithNewCredit()
    {
        $credit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originCredit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originCredit->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(null);

        $this->historyRepository->expects($this->never())
            ->method('save');

        $this->creditLimitHistory->logUpdateItem($credit, $originCredit);
    }

    /**
     * Test `logUpdateItem` with changes.
     *
     * @return void
     */
    public function testLogUpdateItemWithChanges()
    {
        $originCreditId = 1;
        $creditLimit = 10;
        $originCurrencyCode = 'EUR';
        $currencyCode = 'USD';
        $currencyRate = 2;
        $userId = 1;
        $userName = 'User name';

        $credit = $this->getMockBuilder(CreditLimit::class)
            ->setMethods(['getCurrencyRate', 'getCurrencyCode', 'getCreditLimit'])
            ->disableOriginalConstructor()
            ->getMock();
        $originCredit = $this->getMockBuilder(CreditLimit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $originCredit->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($originCreditId);

        $this->userContext->expects($this->atLeastOnce())
            ->method('getUserId')
            ->willReturn($userId);
        $this->creator->expects($this->atLeastOnce())
            ->method('retrieveCreatorName')
            ->with(UserContextInterface::USER_TYPE_ADMIN, $userId)
            ->willReturn($userName);

        $originCredit->expects($this->atLeastOnce())
            ->method('getCurrencyCode')
            ->willReturn($originCurrencyCode);
        $credit->expects($this->atLeastOnce())
            ->method('getCurrencyCode')
            ->willReturn($currencyCode);
        $credit->expects($this->atLeastOnce())
            ->method('getCurrencyRate')
            ->willReturn($currencyRate);

        $credit->expects($this->atLeastOnce())
            ->method('getCreditLimit')
            ->willReturn($creditLimit);

        $history = $this->getMockBuilder(HistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->historyFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($history);
        $this->historyHydrator->expects($this->once())
            ->method('hydrate')
            ->with(
                $history,
                $credit,
                HistoryInterface::TYPE_UPDATED,
                0,
                '',
                null,
                [
                    HistoryInterface::COMMENT_TYPE_UPDATE_CURRENCY => [
                        'currency_from' => $originCurrencyCode,
                        'currency_to' => $currencyCode,
                        'currency_rate' => $currencyRate,
                        'user_name' => $userName
                    ]
                ]
            )
            ->willReturn($history);

        $this->creditLimitHistory->logUpdateItem($credit, $originCredit);
    }
}
