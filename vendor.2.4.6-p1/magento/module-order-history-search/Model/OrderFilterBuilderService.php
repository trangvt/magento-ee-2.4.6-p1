<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model;

use Magento\OrderHistorySearch\Model\Filter\FilterPool;
use Magento\OrderHistorySearch\Model\Filter\FilterInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Theme\Block\Html\Pager;

/**
 * Class OrderFilterBuilderService.
 *
 * Filters builder service for orders collection.
 */
class OrderFilterBuilderService
{
    /**
     * @var FilterPool
     */
    private $filterPool;

    /**
     * @var Pager
     */
    private $pager;

    /**
     * OrderFilterBuilderService constructor.
     *
     * @param FilterPool $filterPool
     * @param Pager $pager
     */
    public function __construct(FilterPool $filterPool, Pager $pager)
    {
        $this->filterPool = $filterPool;
        $this->pager = $pager;
    }

    /**
     * Applies filters to order collection by specified params
     *
     * @param Collection $ordersCollection
     * @param array $params
     *
     * @return Collection
     */
    public function applyOrderFilters(Collection $ordersCollection, array $params = []): Collection
    {
        foreach ($params as $name => $value) {
            if ('' === $value) {
                continue;
            }

            if ($this->pager->getPageVarName() === $name) {
                $ordersCollection->setCurPage((int) $value);
                continue;
            }

            if ($this->pager->getLimitVarName() === $name) {
                $ordersCollection->setPageSize((int) $value);
                continue;
            }

            /** @var FilterInterface $filter */
            $filter = $this->filterPool->get($name);
            $ordersCollection = $filter->applyFilter($ordersCollection, $value);
        }

        $ordersCollection->setFlag('advanced-filtering', true);

        return $ordersCollection;
    }
}
