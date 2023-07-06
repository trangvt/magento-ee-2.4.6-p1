<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\PurchaseOrderRule\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleSearchResultsInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleSearchResultsInterfaceFactory;
use Magento\PurchaseOrderRule\Api\AppliedRuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRule as ResourceAppliedRule;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRule\CollectionFactory as AppliedRuleCollectionFactory;

/**
 * Repository for Purchase Order rules
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AppliedRuleRepository implements AppliedRuleRepositoryInterface
{
    /**
     * @var ResourceAppliedRule
     */
    private $resource;

    /**
     * @var AppliedRuleFactory
     */
    private $appliedRuleFactory;

    /**
     * @var AppliedRuleCollectionFactory
     */
    private $appliedRuleCollectionFactory;

    /**
     * @var AppliedRuleSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ResourceAppliedRule $resource
     * @param AppliedRuleFactory $ruleFactory
     * @param AppliedRuleCollectionFactory $appliedRuleCollectionFactory
     * @param AppliedRuleSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceAppliedRule $resource,
        AppliedRuleFactory $ruleFactory,
        AppliedRuleCollectionFactory $appliedRuleCollectionFactory,
        AppliedRuleSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->appliedRuleFactory = $ruleFactory;
        $this->appliedRuleCollectionFactory = $appliedRuleCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function save(AppliedRuleInterface $appliedRule) : AppliedRuleInterface
    {
        try {
            $this->resource->save($appliedRule);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the applied rule: %1', $exception->getMessage())
            );
        }

        return $this->get((int) $appliedRule->getId());
    }

    /**
     * @inheritdoc
     */
    public function get(int $appliedRuleId) : AppliedRuleInterface
    {
        $appliedRule = $this->appliedRuleFactory->create();
        $this->resource->load($appliedRule, $appliedRuleId);

        if (!$appliedRule->getId()) {
            throw new NoSuchEntityException(__('Applied rule with id "%1" does not exist.', $appliedRuleId));
        }

        return $appliedRule;
    }

    /**
     * @inheritDoc
     */
    public function getListByPurchaseOrderId(int $purchaseOrderId)
    {
        return $this->getList(
            $this->searchCriteriaBuilder
                ->addFilter(AppliedRuleInterface::KEY_PURCHASE_ORDER_ID, $purchaseOrderId)
                ->create()
        );
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->appliedRuleCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(AppliedRuleInterface $appliedRule) : bool
    {
        try {
            $appliedRuleModel = $this->appliedRuleFactory->create();
            $this->resource->load($appliedRuleModel, $appliedRule->getId());
            $this->resource->delete($appliedRuleModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the applied rule: %1', $exception->getMessage())
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $appliedRuleId) : bool
    {
        return $this->delete($this->get($appliedRuleId));
    }
}
