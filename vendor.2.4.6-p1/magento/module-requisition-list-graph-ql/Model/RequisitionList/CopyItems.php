<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Math\Random;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Api\Data\RequisitionListItemInterface;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionList\Items as RequisitionListItems;
use Magento\RequisitionList\Model\RequisitionListFactory;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;

/**
 * Copy Requisition list items from one list to another Model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CopyItems
{
    /**
     * @var RequisitionListFactory
     */
    private $requisitionListFactory;

    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var RequisitionListItems
     */
    private $requisitionListItems;

    /**
     * @var RequisitionListManagementInterface
     */
    private $requisitionListManagement;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * Constant values used to create a new requisition list
     */
    private const REQUISITION_LIST_DEFAULT_NAME = 'DEFAULT_REQUISITION_LIST_';
    private const REQUISITION_LIST_DEFAULT_DESCRIPTION = 'DEFAULT REQUISITION LIST DESCRIPTION ';
    private const RANDOM_STRING_LENGTH = 10;

    /**
     * CopyItems constructor
     *
     * @param RequisitionListFactory $requisitionListFactory
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param RequisitionListItems $requisitionListItems
     * @param RequisitionListManagementInterface $requisitionListManagement
     * @param Random $mathRandom
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        RequisitionListFactory $requisitionListFactory,
        RequisitionListRepositoryInterface $requisitionListRepository,
        RequisitionListItems $requisitionListItems,
        RequisitionListManagementInterface $requisitionListManagement,
        Random $mathRandom,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        IdEncoder $idEncoder
    ) {
        $this->requisitionListFactory = $requisitionListFactory;
        $this->requisitionListRepository = $requisitionListRepository;
        $this->requisitionListItems = $requisitionListItems;
        $this->requisitionListManagement = $requisitionListManagement;
        $this->mathRandom = $mathRandom;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->idEncoder = $idEncoder;
    }

    /**
     * Copy requisition list items from one list to another list.
     *
     * @param RequisitionListInterface $targetRequisitionList
     * @param array $itemIds
     * @param int $sourceId
     * @return void
     * @throws GraphQlInputException
     */
    public function execute(
        RequisitionListInterface $targetRequisitionList,
        array $itemIds,
        int $sourceId
    ): void {
        $requisitionListItemsToCopy = $this->getRequisitionListBySourceAndItemsId($itemIds, $sourceId);
        /** @var RequisitionListItemInterface  $item */
        foreach ($requisitionListItemsToCopy as $item) {
            try {
                $this->requisitionListManagement->copyItemToList($targetRequisitionList, $item);
            } catch (NoSuchEntityException $noSuchEntityException) {
                throw new GraphQlInputException(__($noSuchEntityException->getMessage()), $noSuchEntityException);
            }

            if (($key = array_search($item->getId(), $itemIds)) !== false) {
                unset($itemIds[$key]);
            }
        }
        if (count($itemIds) !== 0) {
            throw new GraphQlInputException(
                __('No such item(s) with ID(s) "%1"', implode(', ', $itemIds))
            );
        }
    }

    /**
     * Get requisition list by source id and item ids
     *
     * @param array $itemIds
     * @param int $sourceId
     * @return array
     */
    private function getRequisitionListBySourceAndItemsId(array $itemIds, int $sourceId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('requisition_list_id', $sourceId, 'eq')
            ->addFilter('item_id', $itemIds, 'in')
            ->create();
        return $this->requisitionListItems->getList($searchCriteria)->getItems();
    }

    /**
     * Create a new requisition list for the customer
     *
     * @param int $customerId
     * @return RequisitionListInterface
     * @throws LocalizedException
     */
    public function createNewRequisitionList(int $customerId): RequisitionListInterface
    {
        $randomString = $this->getRandomString(self::RANDOM_STRING_LENGTH);
        $requisitionList = $this->requisitionListFactory->create();
        $requisitionList->setCustomerId($customerId);
        $requisitionList->setName(self::REQUISITION_LIST_DEFAULT_NAME . $randomString);
        $requisitionList->setDescription(self::REQUISITION_LIST_DEFAULT_DESCRIPTION . $randomString);

        try {
            $requisitionList = $this->requisitionListRepository->save($requisitionList, true);
        } catch (CouldNotSaveException $exception) {
            throw new GraphQlInputException(
                __('Unable to create the target Requisition list')
            );
        }

        return $requisitionList;
    }

    /**
     * Generate a random string
     *
     * @param int $length
     * @param string|null $chars
     * @return string
     * @throws LocalizedException
     */
    private function getRandomString(int $length, string $chars = null): string
    {
        return $this->mathRandom->getRandomString($length, $chars);
    }
}
