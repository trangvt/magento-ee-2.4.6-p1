<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Model;

use Magento\CompanyCredit\Model\CreditCurrencyHistory;
use Magento\CompanyCredit\Model\History;
use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\HistoryRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditCurrencyHistoryTest extends TestCase
{
    /**
     * @var HistoryRepositoryInterface|MockObject
     */
    private $historyRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var TransactionFactory|MockObject
     */
    private $transactionFactory;

    /**
     * @var CreditCurrencyHistory
     */
    private $creditCurrencyHistory;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->historyRepository = $this->createMock(
            HistoryRepositoryInterface::class
        );
        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );
        $this->transactionFactory = $this->createPartialMock(
            TransactionFactory::class,
            ['create']
        );

        $objectManager = new ObjectManager($this);
        $this->creditCurrencyHistory = $objectManager->getObject(
            CreditCurrencyHistory::class,
            [
                'historyRepository' => $this->historyRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'transactionFactory' => $this->transactionFactory,
            ]
        );
    }

    /**
     * Test for update method.
     *
     * @return void
     */
    public function testUpdate()
    {
        $currentId = 1;
        $newId = 2;
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
            ->with(HistoryInterface::COMPANY_CREDIT_ID, $currentId)->willReturnSelf();
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->historyRepository->expects($this->once())
            ->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $historyItem = $this->createMock(History::class);
        $searchResults->expects($this->once())->method('getItems')->willReturn([$historyItem]);
        $transaction = $this->createMock(Transaction::class);
        $this->transactionFactory->expects($this->once())->method('create')->willReturn($transaction);
        $historyItem->expects($this->once())->method('setCompanyCreditId')->with($newId)->willReturnSelf();
        $transaction->expects($this->once())->method('addObject')->with($historyItem)->willReturnSelf();
        $transaction->expects($this->once())->method('save')->willReturnSelf();
        $this->creditCurrencyHistory->update($currentId, $newId);
    }
}
