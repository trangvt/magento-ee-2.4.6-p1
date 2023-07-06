<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Filter;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * Class ProductSku.
 *
 * Model for 'Product Sku' filter for order search filter.
 */
class ProductSku implements FilterInterface
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var ItemRepository
     */
    private $itemRepository;

    /**
     * ProductSku constructor.
     *
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param ItemRepository $itemRepository
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        ItemRepository $itemRepository
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->itemRepository = $itemRepository;
    }

    /**
     * @inheritdoc
     */
    public function applyFilter(Collection $ordersCollection, $value): Collection
    {
        /** @var SearchCriteriaBuilder $itemCriteria */
        $itemCriteria = $this->searchCriteriaBuilderFactory->create();

        $nameFilter = $this->filterBuilder->setField(OrderItemInterface::NAME)
            ->setValue('%' . $value . '%')
            ->setConditionType('like')
            ->create();

        $skuFilter = $this->filterBuilder->setField(OrderItemInterface::SKU)
            ->setValue('%' . $value . '%')
            ->setConditionType('like')
            ->create();

        $filterGroup = $this->filterGroupBuilder->addFilter($nameFilter)
            ->addFilter($skuFilter)
            ->create();

        $itemCriteria->setFilterGroups([$filterGroup]);

        $items = $this->itemRepository->getList($itemCriteria->create());

        $orderIds = [];
        foreach ($items as $item) {
            $orderIds[] = $item->getOrderId();
        }

        $ordersCollection->addFieldToFilter(OrderInterface::ENTITY_ID, ['in' => array_unique($orderIds)]);

        return $ordersCollection;
    }
}
