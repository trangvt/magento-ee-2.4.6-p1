<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CompanyCreditGraphQl\Model;

use Magento\CompanyCredit\Model\ResourceModel\History\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Credit history search model
 */
class CreditHistory
{
    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionFactory
     */
    private $historyCollectionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * CreditHistory constructor.
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionFactory $historyCollectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionFactory $historyCollectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get list of credit history
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $collection = $this->historyCollectionFactory->create();

        $collection->join(
            ['cc' => $this->resourceConnection->getTableName('company_credit')],
            'cc.entity_id = main_table.company_credit_id',
            []
        );

        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?? 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $searchResults->setTotalCount($collection->getSize());
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }
}
