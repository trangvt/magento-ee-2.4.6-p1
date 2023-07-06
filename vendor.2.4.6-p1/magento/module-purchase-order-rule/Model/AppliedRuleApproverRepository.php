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
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverSearchResultsInterface;
use Magento\PurchaseOrderRule\Api\Data\AppliedRuleApproverSearchResultsInterfaceFactory;
use Magento\PurchaseOrderRule\Api\AppliedRuleApproverRepositoryInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover as ResourceAppliedRuleApprover;
use Magento\PurchaseOrderRule\Model\ResourceModel\AppliedRuleApprover\CollectionFactory
    as AppliedRuleApproverCollectionFactory;

/**
 * Repository for Purchase Order rules
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AppliedRuleApproverRepository implements AppliedRuleApproverRepositoryInterface
{
    /**
     * @var ResourceAppliedRuleApprover
     */
    private $resource;

    /**
     * @var AppliedRuleApproverFactory
     */
    private $appliedRuleApproverFactory;

    /**
     * @var AppliedRuleCollectionFactory
     */
    private $appliedRuleApproverCollectionFactory;

    /**
     * @var AppliedRuleApproverSearchResultsInterfaceFactory
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
     * @param ResourceAppliedRuleApprover $resource
     * @param AppliedRuleApproverFactory $ruleFactory
     * @param AppliedRuleApproverCollectionFactory $appliedRuleCollectionFactory
     * @param AppliedRuleApproverSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceAppliedRuleApprover $resource,
        AppliedRuleApproverFactory $ruleFactory,
        AppliedRuleApproverCollectionFactory $appliedRuleCollectionFactory,
        AppliedRuleApproverSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->appliedRuleApproverFactory = $ruleFactory;
        $this->appliedRuleApproverCollectionFactory = $appliedRuleCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function save(AppliedRuleApproverInterface $appliedRuleApprover) : AppliedRuleApproverInterface
    {
        try {
            $this->resource->save($appliedRuleApprover);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the applied rule approver: %1', $exception->getMessage())
            );
        }

        return $this->get((int) $appliedRuleApprover->getId());
    }

    /**
     * @inheritdoc
     */
    public function get(int $appliedRuleApproverId) : AppliedRuleApproverInterface
    {
        $appliedRuleApprover = $this->appliedRuleApproverFactory->create();
        $this->resource->load($appliedRuleApprover, $appliedRuleApproverId);

        if (!$appliedRuleApprover->getId()) {
            throw new NoSuchEntityException(
                __('Applied rule approver with id "%1" does not exist.', $appliedRuleApproverId)
            );
        }

        return $appliedRuleApprover;
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->appliedRuleApproverCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getListByAppliedRuleId(int $appliedRuleId)
    {
        return $this->getList(
            $this->searchCriteriaBuilder
                ->addFilter(AppliedRuleApproverInterface::KEY_APPLIED_RULE_ID, $appliedRuleId)
                ->create()
        );
    }

    /**
     * @inheritdoc
     */
    public function delete(AppliedRuleApproverInterface $appliedRuleApprover) : bool
    {
        try {
            $appliedRuleApproverModel = $this->appliedRuleApproverFactory->create();
            $this->resource->load($appliedRuleApproverModel, $appliedRuleApprover->getId());
            $this->resource->delete($appliedRuleApproverModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the applied rule approver: %1', $exception->getMessage())
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
