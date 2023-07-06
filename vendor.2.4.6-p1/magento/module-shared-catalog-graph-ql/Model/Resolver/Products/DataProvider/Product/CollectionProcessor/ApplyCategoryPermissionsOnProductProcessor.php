<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessor;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\CatalogPermissionsGraphQl\Model\Customer\GroupProcessor;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\SharedCatalog\Model\Config;
use Magento\SharedCatalog\Model\CustomerGroupManagement;

class ApplyCategoryPermissionsOnProductProcessor implements CollectionProcessorInterface
{
    /**
     * Flag to determine presence shared catalog filter in collection
     */
    private const SHARED_CATALOG_FILTER = 'has_shared_catalog_filter';

    /**
     * @var \Magento\SharedCatalog\Model\Config
     */
    private $config;

    /**
     * @var \Magento\SharedCatalog\Model\CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @var GroupProcessor
     */
    private $groupProcessor;

    /**
     * @param Config $config
     * @param CustomerGroupManagement $customerGroupManagement
     * @param GroupProcessor $groupProcessor
     */
    public function __construct(
        Config $config,
        CustomerGroupManagement $customerGroupManagement,
        GroupProcessor $groupProcessor
    ) {
        $this->config = $config;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->groupProcessor = $groupProcessor;
    }

    /**
     * Process collection to add additional joins, attributes, and clauses to a product collection.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param ContextInterface|null $context
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        ContextInterface $context = null
    ): Collection {
        if (!$context) {
            return $collection;
        }
        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();
        $customerGroupId = $this->groupProcessor->getCustomerGroup($context);
        $website = $store->getWebsite()->getId();
        if (!$collection->hasFlag(self::SHARED_CATALOG_FILTER)
            && $this->config->isActive(ScopeInterface::SCOPE_WEBSITE, $website)
            && !$this->customerGroupManagement->isPrimaryCatalogAvailable($customerGroupId)
        ) {
            $collection->joinTable(
                ['shared_product' => $collection->getTable(
                    'shared_catalog_product_item'
                )],
                'sku = sku',
                ['customer_group_id'],
                '{{table}}.customer_group_id = \'' . $customerGroupId . '\''
            );

            $collection->setFlag(self::SHARED_CATALOG_FILTER, true);
        }
        return $collection;
    }
}
