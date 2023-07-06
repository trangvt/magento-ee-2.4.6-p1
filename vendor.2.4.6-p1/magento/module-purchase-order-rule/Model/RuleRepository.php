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
use Magento\PurchaseOrderRule\Api\Data\RuleInterface;
use Magento\PurchaseOrderRule\Api\Data\RuleSearchResultsInterfaceFactory;
use Magento\PurchaseOrderRule\Api\RuleRepositoryInterface;
use Magento\PurchaseOrderRule\Model\ResourceModel\Rule as ResourceRule;
use Magento\PurchaseOrderRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

/**
 * Repository for Purchase Order rules
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleRepository implements RuleRepositoryInterface
{
    /**
     * @var ResourceRule
     */
    private $resource;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var RuleSearchResultsInterfaceFactory
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
     * @param ResourceRule $resource
     * @param RuleFactory $ruleFactory
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param RuleSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceRule $resource,
        RuleFactory $ruleFactory,
        RuleCollectionFactory $ruleCollectionFactory,
        RuleSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resource = $resource;
        $this->ruleFactory = $ruleFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function save(RuleInterface $rule) : RuleInterface
    {
        try {
            $this->resource->save($rule);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the rule: %1', $exception->getMessage())
            );
        }

        return $this->get((int) $rule->getId());
    }

    /**
     * @inheritdoc
     */
    public function get($ruleId) : RuleInterface
    {
        $rule = $this->ruleFactory->create();
        $this->resource->load($rule, $ruleId);

        if (!$rule->getId()) {
            throw new NoSuchEntityException(__('Rule with id "%1" does not exist.', $ruleId));
        }

        return $rule;
    }

    /**
     * @inheritDoc
     */
    public function getByCompanyId(int $companyId)
    {
        return $this->getList(
            $this->searchCriteriaBuilder
            ->addFilter(RuleInterface::KEY_COMPANY_ID, $companyId)
            ->addFilter(RuleInterface::KEY_IS_ACTIVE, true)
            ->create()
        );
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->ruleCollectionFactory->create();

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
    public function delete(RuleInterface $rule) : bool
    {
        try {
            $ruleModel = $this->ruleFactory->create();
            $this->resource->load($ruleModel, $rule->getId());
            $this->resource->delete($ruleModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the rule: %1', $exception->getMessage())
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($ruleId) : bool
    {
        return $this->delete($this->get($ruleId));
    }

    /**
     * @inheritDoc
     */
    public function isCompanyRuleNameUnique(string $ruleName, int $companyId, $ruleId = null): bool
    {
        $ruleName = strtolower($ruleName);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(RuleInterface::KEY_COMPANY_ID, $companyId)
            ->create();
        $ruleNames = [];

        foreach ($this->getList($searchCriteria)->getItems() as $rule) {
            if ((int)$rule->getId() !== (int)$ruleId) {
                $ruleNames[] = strtolower($rule->getName());
            }
        }

        return !in_array($ruleName, $ruleNames);
    }
}
