<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\OrderHistorySearch\Model\Order\Status;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Store\Model\StoreManager;

/**
 * Class DataProvider.
 *
 * Options data provider for order statuses.
 */
class DataProvider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * DataProvider constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param StoreManager $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManager $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get order statuses options array.
     *
     * @param bool $onlyVisibleOnStorefront
     * @return array
     */
    public function getOrderStatusOptions(bool $onlyVisibleOnStorefront = true): array
    {
        $collection = $this->initCollection();
        if ($onlyVisibleOnStorefront) {
            $this->filterVisibleOnStorefront($collection);
        }
        try {
            $storeId = (int)$this->storeManager->getStore(true)->getId();
            $this->attachLabels($storeId, $collection);
        } catch (NoSuchEntityException $e) {
            // return without store-view labels
            return $collection->toOptionArray();
        }
        return $collection->toOptionArray();
    }

    /**
     * Initialize collection.
     *
     * @return Collection
     */
    private function initCollection() : Collection
    {
        /** @var Collection $statusCollection */
        $statusCollection = $this->collectionFactory->create();
        $statusCollection->getSelect()->group('status')->order('label ASC');
        return $statusCollection;
    }

    /**
     * Add visible on storefront only filter to collection.
     *
     * @param Collection $statusCollection
     */
    private function filterVisibleOnStorefront(Collection $statusCollection) : void
    {
        $statusCollection->joinStates();
        $statusCollection->addAttributeToFilter('visible_on_front', '1');
    }

    /**
     * Attach custom store view labels to collection.
     *
     * @param int $storeId
     * @param Collection $statusCollection
     */
    private function attachLabels(int $storeId, Collection $statusCollection): void
    {
        $statusCollection->getSelect()->from(
            $statusCollection->getMainTable(),
            [
                'label' => 'COALESCE(sl.label, main_table.label)'
            ]
        );
        $statusCollection->getSelect()->joinLeft(
            ['sl' => $statusCollection->getTable('sales_order_status_label')],
            "sl.status = main_table.status AND sl.store_id = '{$storeId}'",
            ['sl_label' => 'sl.label']
        );
    }
}
