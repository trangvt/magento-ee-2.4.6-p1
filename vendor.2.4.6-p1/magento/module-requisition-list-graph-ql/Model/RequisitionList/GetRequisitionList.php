<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RequisitionListGraphQl\Model\RequisitionList;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\RequisitionList\Api\Data\RequisitionListInterface;
use Magento\RequisitionList\Model\RequisitionListRepository;
use Magento\Framework\GraphQl\Query\Uid as IdEncoder;

/**
 * Get RequisitionList for current user
 */
class GetRequisitionList
{
    /**
     * @var RequisitionListRepository
     */
    private $repository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var IdEncoder
     */
    private $idEncoder;

    /**
     * @param RequisitionListRepository $repository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param IdEncoder $idEncoder
     */
    public function __construct(
        RequisitionListRepository $repository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        IdEncoder $idEncoder
    ) {
        $this->repository = $repository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->idEncoder  = $idEncoder;
    }

    /**
     * Get all Requisition list for user
     *
     * @param int $customerId
     * @param array $args
     * @return array
     */
    public function execute(int $customerId, array $args): array
    {
        $currentPage = $args['currentPage'] ?? 1;
        $pageSize = $args['pageSize'] ?? 20;

        $builderCount = $this->getFilters($customerId, true, $args);
        $countLists = $this->repository->getList($builderCount->create())->getTotalCount();

        $builder = $this->getFilters($customerId, false, $args);
        $lists = $this->repository->getList($builder->create())->getItems();

        $data = [
            'total_count' => count($lists),
            'items' => [],
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => (int)ceil($countLists/$pageSize)
            ],
        ];

        /** @var RequisitionListInterface $list */
        foreach ($lists as $list) {
            $listItemCount = count($list->getItems());
            $data['items'][$list->getId()] = [
                'uid' => $this->idEncoder->encode((string)$list->getId()),
                'name' => $list->getName(),
                'items' => [],
                'description' => $list->getDescription(),
                'items_count' => $listItemCount,
                'updated_at' => $list->getUpdatedAt()
            ];

            if ($list->getItems()) {
                $offset = array_keys($list->getItems())[0];

                $data['items'][$list->getId()]['items'] = [
                    'sku' => $list->getItems()[$offset]->getSku(),
                    'qty' => $list->getItems()[$offset]->getQty()
                ];
            }
        }

        return $data;
    }

    /**
     * Get Filters
     *
     * @param int $customerId
     * @param bool $isTotal
     * @param array $args
     * @return SearchCriteriaBuilder
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getFilters(int $customerId, bool $isTotal, array $args = [])
    {
        $filtersGiven = isset($args['filter']) ? $args['filter'] : [];

        $filters = [];

        if (!$isTotal) {
            if (isset($filtersGiven['uids'])) {
                if (key($filtersGiven['uids']) == 'eq' || key($filtersGiven['uids']) == 'in') {
                    $reqIds = [];
                    if (is_array($filtersGiven['uids'][key($filtersGiven['uids'])])) {
                        foreach ($filtersGiven['uids'][key($filtersGiven['uids'])] as $id) {
                            $reqIds[] = $this->idEncoder->decode($id);
                        }
                    } else {
                        $reqIds = $this->idEncoder->decode($filtersGiven['uids'][key($filtersGiven['uids'])]);
                    }
                    
                    $filters[] = $this->filterBuilder
                        ->setField(RequisitionListInterface::REQUISITION_LIST_ID)
                        ->setConditionType(key($filtersGiven['uids']))
                        ->setValue($reqIds)
                        ->create();
                }
            }

            if (isset($filtersGiven['name'])) {
                if (key($filtersGiven['name']) == 'match') {
                    $filters[] = $this->filterBuilder
                        ->setField(RequisitionListInterface::NAME)
                        ->setConditionType('like')
                        ->setValue($filtersGiven['name'][key($filtersGiven['name'])])
                        ->create();
                }
            }
            return $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId)
                ->addFilters($filters);
        } else {
            return $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId);
        }
    }
}
