<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\QuickOrder\Plugin\AdvancedCheckout;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\QuickOrder\Model\Config;
use Magento\Store\Model\Store;

/**
 * Plugin class for set items quantity in QuickOrder.
 * @see \Magento\Checkout\Model\Cart\CartInterface
 */
class SetQuantityForQuickOrderItemsPlugin
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var Config
     */
    private $quickOrderConfig;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param Config $quickOrderConfig
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        Config $quickOrderConfig
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;
        $this->quickOrderConfig = $quickOrderConfig;
    }

    /**
     * Add minimal qty to the items if it wasn't set during Quick Order process
     *
     * @param Cart $subject
     * @param array $items
     * @return array
     */
    public function beforeCheckItems(
        Cart $subject,
        array $items
    ): array {
        if ($this->quickOrderConfig->isActive()) {
            $store = $subject->getCurrentStore();
            $customerGroupId = $subject->getCustomer() ? $subject->getCustomer()->getGroupId() : null;
            foreach ($items as &$item) {
                if (!empty($item['sku']) && empty($item['qty'])) {
                    $item['qty'] = $this->getMinimalQty($item['sku'], $store, $customerGroupId);
                }
            }
        }
        return [$items];
    }

    /**
     * Gets minimal sales quantity.
     *
     * @param string $sku
     * @param Store $store
     * @param int|null $customerGroupId
     * @return float|null
     */
    private function getMinimalQty(string $sku, Store $store, $customerGroupId = null): ?float
    {
        $minSaleQty = null;
        try {
            $stockItem = $this->stockRegistry->getStockItemBySku($sku, (int)$store->getWebsiteId());
            $minSaleQty = $stockItem->getMinSaleQty();
        } catch (NoSuchEntityException $exception) {
            $minSaleQty = $this->stockConfiguration->getMinSaleQty($store->getId(), $customerGroupId);
        }

        return $minSaleQty > 0 ? $minSaleQty : null;
    }
}
