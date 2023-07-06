<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\StateException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;

/**
 * Delete requisition list items from the requisition list
 */
class DeleteItems
{
    /**
     * @var RequisitionListItems
     */
    private $requisitionListItems;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param RequisitionListItems $requisitionListItems
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        RequisitionListItems $requisitionListItems,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->requisitionListItems = $requisitionListItems;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Delete requisition list items from a list
     *
     * @param array $requisitionListItemsId
     * @param int $requisitionListId
     * @throws GraphQlInputException
     */
    public function execute(array $requisitionListItemsId, int $requisitionListId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('requisition_list_id', $requisitionListId, 'eq')
            ->addFilter('item_id', $requisitionListItemsId, 'in')
            ->create();
        $requisitionListItemsToDelete = $this->requisitionListItems->getList($searchCriteria)->getItems();

        /** @var RequisitionListItemInterface  $item */
        foreach ($requisitionListItemsToDelete as $item) {
            try {
                $this->requisitionListItems->delete($item);
            } catch (StateException $stateException) {
                throw new GraphQlInputException(__($stateException->getMessage()), $stateException);
            }
        }
    }
}
