<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RequisitionList\Model\RequisitionListItem;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\DataObject;
use Magento\RequisitionList\Api\RequisitionListManagementInterface;
use Magento\RequisitionList\Api\RequisitionListRepositoryInterface;
use Magento\RequisitionList\Model\RequisitionListItem\Options\Builder;
use Magento\RequisitionList\Model\RequisitionListProduct;

/**
 * Prepare and save requisition list item.
 */
class SaveHandler
{
    /**
     * @var RequisitionListRepositoryInterface
     */
    private $requisitionListRepository;

    /**
     * @var Builder
     */
    private $optionsBuilder;

    /**
     * @var RequisitionListManagementInterface
     */
    private $requisitionListManagement;

    /**
     * @var Locator
     */
    private $requisitionListItemLocator;

    /**
     * @var RequisitionListProduct
     */
    private $requisitionListProduct;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param RequisitionListRepositoryInterface $requisitionListRepository
     * @param Builder $optionsBuilder
     * @param RequisitionListManagementInterface $requisitionListManagement
     * @param Locator $requisitionListItemLocator
     * @param RequisitionListProduct $requisitionListProduct
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        RequisitionListRepositoryInterface $requisitionListRepository,
        Builder $optionsBuilder,
        RequisitionListManagementInterface $requisitionListManagement,
        Locator $requisitionListItemLocator,
        RequisitionListProduct $requisitionListProduct,
        StockRegistryInterface $stockRegistry
    ) {
        $this->requisitionListRepository = $requisitionListRepository;
        $this->optionsBuilder = $optionsBuilder;
        $this->requisitionListManagement = $requisitionListManagement;
        $this->requisitionListItemLocator = $requisitionListItemLocator;
        $this->requisitionListProduct = $requisitionListProduct;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Set options and save requisition list item.
     *
     * @param DataObject $productData
     * @param array $options
     * @param int $itemId
     * @param int $listId
     * @return \Magento\Framework\Phrase
     */
    public function saveItem(DataObject $productData, array $options, $itemId, $listId)
    {
        $sku = (string)$productData->getSku();
        $qty = $this->retrieveQty($productData);
        if (!$this->isDecimalQtyUsed($sku)) {
            $qty = (int)$qty;
        }

        $requisitionList = $this->requisitionListRepository->get($listId);
        $itemOptions = $this->optionsBuilder->build($options, $itemId, false);
        $item = $this->requisitionListItemLocator->getItem($itemId);
        $item->setQty($qty);
        $item->setOptions($itemOptions);
        $item->setSku($sku);

        $items = $requisitionList->getItems();

        if ($item->getId()) {
            foreach ($items as $i => $existItem) {
                if ($existItem->getId() == $item->getId()) {
                    $items[$i] = $item;
                }
            }
        } else {
            $items[] = $item;
        }

        $product = $this->requisitionListProduct->getProduct($productData->getSku());
        if ($item->getId()) {
            $message = __('%1 has been updated in your requisition list.', $product->getName());
        } else {
            $message = __(
                'Product %1 has been added to the requisition list %2.',
                $product->getName(),
                $requisitionList->getName()
            );
        }

        $this->requisitionListManagement->setItemsToList($requisitionList, $items);
        $this->requisitionListRepository->save($requisitionList);

        return $message;
    }

    /**
     * Retrieve qty param
     *
     * @param DataObject $productData
     * @return float
     */
    private function retrieveQty(DataObject $productData): float
    {
        $qty = (float)$productData->getOptions('qty');
        if ($qty <= 0) {
            $qty = 1.0;
        }

        return $qty;
    }

    /**
     * Is stock item qty uses decimal
     *
     * @param string $sku
     * @return bool
     */
    private function isDecimalQtyUsed(string $sku): bool
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);

        return (bool)$stockItem->getIsQtyDecimal();
    }
}
