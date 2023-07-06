<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\SharedCatalog\Plugin\AdvancedCheckout\Model;

use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\StatusInfoInterface;
use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Helper\Data;
use Magento\Customer\Model\GroupManagement;
use Magento\SharedCatalog\Model\ResourceModel\ProductItem\CollectionFactory;
use Magento\SharedCatalog\Model\SharedCatalogResolver;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin for the AdvancedCheckout Cart model to change item status on not found.
 */
class HideProductsAbsentInSharedCatalogPlugin
{
    /**
     * @var StatusInfoInterface
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SharedCatalogResolver
     */
    private $sharedCatalogResolver;

    /**
     * @var CollectionFactory
     */
    private $sharedCatalogProductCollectionFactory;

    /**
     * @param StatusInfoInterface $config
     * @param StoreManagerInterface $storeManager
     * @param SharedCatalogResolver $sharedCatalogResolver
     * @param CollectionFactory $sharedCatalogProductCollectionFactory
     */
    public function __construct(
        StatusInfoInterface $config,
        StoreManagerInterface $storeManager,
        SharedCatalogResolver $sharedCatalogResolver,
        CollectionFactory $sharedCatalogProductCollectionFactory
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->sharedCatalogResolver = $sharedCatalogResolver;
        $this->sharedCatalogProductCollectionFactory = $sharedCatalogProductCollectionFactory;
    }

    /**
     * Change item code to not found if appropriate product is not in the shared catalog.
     *
     * @param Cart $subject
     * @param array $items
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterCheckItems(Cart $subject, array $items): array
    {
        $website = $this->storeManager->getWebsite()->getId();
        if ($this->config->isActive(ScopeInterface::SCOPE_WEBSITE, $website)) {
            $customer = $subject->getActualQuote()->getCustomer();
            $groupId = $customer && $customer->getId()
                ? (int) $customer->getGroupId()
                : GroupManagement::NOT_LOGGED_IN_ID;
            $isPrimaryCatalogAvailable = $this->sharedCatalogResolver->isPrimaryCatalogAvailable($groupId);

            if (!$isPrimaryCatalogAvailable
                && ($unavailableProducts = $this->getUnavalableProducts(\array_column($items, 'sku'), $groupId))
            ) {
                foreach ($items as &$item) {
                    if (\in_array($item['sku'], $unavailableProducts)) {
                        $item['code'] = Data::ADD_ITEM_STATUS_FAILED_SKU;
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Get product SKUs unavailable in Shared Catalog
     *
     * @param array $skus
     * @param int $customerGroupId
     * @return array
     */
    private function getUnavalableProducts(array $skus, $customerGroupId)
    {
        $collection = $this->sharedCatalogProductCollectionFactory->create();
        $collection->addFieldToSelect(ProductItemInterface::SKU);
        $collection->addFieldToFilter(ProductItemInterface::CUSTOMER_GROUP_ID, $customerGroupId);
        $collection->getSelect()->where(\sprintf('%s IN (?)', ProductItemInterface::SKU), $skus);

        return \array_udiff($skus, $collection->getColumnValues('sku'), 'strcasecmp');
    }
}
