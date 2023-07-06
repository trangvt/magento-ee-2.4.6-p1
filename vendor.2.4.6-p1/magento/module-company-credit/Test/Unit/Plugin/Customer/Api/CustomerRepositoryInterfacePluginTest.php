<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCredit\Test\Unit\Plugin\Customer\Api;

use Magento\CompanyCredit\Model\HistoryInterface;
use Magento\CompanyCredit\Model\HistoryRepositoryInterface;
use Magento\CompanyCredit\Plugin\Customer\Api\CustomerRepositoryInterfacePlugin;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for CustomerRepositoryInterfacePluginTest.
 */
class CustomerRepositoryInterfacePluginTest extends TestCase
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
     * @var CustomerRepositoryInterfacePlugin
     */
    private $customerRepositoryPlugin;

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

        $objectManager = new ObjectManager($this);
        $this->customerRepositoryPlugin = $objectManager->getObject(
            CustomerRepositoryInterfacePlugin::class,
            [
                'historyRepository' => $this->historyRepository,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
            ]
        );
    }

    /**
     * Test aroundDeleteById method.
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testAroundDeleteById()
    {
        $customerId = 1;
        $searchCriteria = $this->getMockForAbstractClass(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
            ->with(HistoryInterface::USER_ID, $customerId)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);
        $searchResults = $this->getMockForAbstractClass(SearchResultsInterface::class);
        $this->historyRepository->expects($this->once())->method('getList')
            ->with($searchCriteria)->willReturn($searchResults);
        $historyItem = $this->getMockForAbstractClass(HistoryInterface::class);
        $searchResults->expects($this->once())->method('getItems')->willReturn(new \ArrayIterator([$historyItem]));
        $historyItem->expects($this->once())->method('setUserId')->with(null)->willReturnSelf();
        $this->historyRepository->expects($this->once())->method('save')->with($historyItem)->willReturn($historyItem);
        $customerRepository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->assertTrue(
            $this->customerRepositoryPlugin->aroundDeleteById(
                $customerRepository,
                function ($customerId) {
                    return true;
                },
                $customerId
            )
        );
    }
}
