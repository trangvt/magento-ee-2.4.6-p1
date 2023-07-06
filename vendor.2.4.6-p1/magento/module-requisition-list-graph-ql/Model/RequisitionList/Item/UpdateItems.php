<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList\Item;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;

/**
 * Update requisition list items
 */
class UpdateItems
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
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * UpdateItems constructor
     *
     * @param RequisitionListItems $requisitionListItems
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        RequisitionListItems $requisitionListItems,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        IdEncoder $idEncoder
    ) {
        $this->requisitionListItems = $requisitionListItems;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Update requisition list items from a list by item id
     *
     * @param array $items
     * @param int $requisitionListId
     * @return void
     *
     * @throws GraphQlInputException
     */
    public function execute(array $items, int $requisitionListId): void
    {
        $itemIds = [];
        $itemData = [];
        foreach ($items as $itemDetail) {
            $id = $this->idEncoder->decode($itemDetail['item_id']);
            $itemData[$id] = [
                'quantity' => array_key_exists('quantity', $itemDetail) ? (float)$itemDetail['quantity'] : []
            ];
            array_push($itemIds, $id);
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('requisition_list_id', $requisitionListId, 'eq')
            ->addFilter('item_id', $itemIds, 'in')
            ->create();

        $requisitionListItemsToUpdate = $this->requisitionListItems->getList($searchCriteria)->getItems();
        /** @var RequisitionListItemInterface  $item */
        foreach ($requisitionListItemsToUpdate as $item) {
            if (($key = array_search($item->getId(), $itemIds)) !== false) {
                unset($itemIds[$key]);
            }
            $quantity = $itemData[$item->getId()]['quantity'];

            try {
                if ($quantity <= 0.0) {
                    throw new GraphQlInputException(__('Quantity should be greater than 0'));
                } elseif ($quantity) {
                    $item->setQty($quantity);
                }
                $this->requisitionListItems->save($item);
            } catch (LocalizedException $stateException) {
                throw new GraphQlInputException(__($stateException->getMessage()), $stateException);
            }
        }
        if (count($itemIds) !== 0) {
            throw new GraphQlInputException(
                __('No such item(s) with ID(s) "%1"', implode(', ', $itemIds))
            );
        }
    }
}
