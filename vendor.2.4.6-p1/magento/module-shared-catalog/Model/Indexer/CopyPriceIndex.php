<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model\Indexer;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactoryFactory;
use Magento\Catalog\Model\Indexer\Product\Price\UpdateIndexInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Model\Indexer\CustomerGroupDimensionProvider;
use Magento\Framework\Indexer\MultiDimensionProvider;
use Magento\Framework\Search\Request\IndexScopeResolverInterface;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;

/**
 * Copy index data in the table from default customer group
 */
class CopyPriceIndex implements UpdateIndexInterface
{
    /**
     * @var CopyIndex
     */
    private $copyIndex;

    /**
     * @var IndexScopeResolverInterface
     */
    private $indexScopeResolver;

    /**
     * @var WebsiteDimensionProvider
     */
    private $websiteDimensionProvider;

    /**
     * @var GroupManagementInterface
     */
    private $groupManagement;

    /**
     * @var DimensionCollectionFactoryFactory
     */
    private $dimensionCollectionFactoryFactory;

    /**
     * @var CustomerGroupDataProviderFactory
     */
    private $customerGroupDataProviderFactory;

    /**
     * @param CopyIndex $copyIndex
     * @param IndexScopeResolverInterface $indexScopeResolver
     * @param WebsiteDimensionProvider $websiteDimensionProvider
     * @param GroupManagementInterface $groupManagement
     * @param DimensionCollectionFactoryFactory $dimensionCollectionFactoryFactory
     * @param CustomerGroupDataProviderFactory $customerGroupDataProviderFactory
     */
    public function __construct(
        CopyIndex $copyIndex,
        IndexScopeResolverInterface $indexScopeResolver,
        WebsiteDimensionProvider $websiteDimensionProvider,
        GroupManagementInterface $groupManagement,
        DimensionCollectionFactoryFactory $dimensionCollectionFactoryFactory,
        CustomerGroupDataProviderFactory $customerGroupDataProviderFactory
    ) {
        $this->copyIndex = $copyIndex;
        $this->indexScopeResolver = $indexScopeResolver;
        $this->websiteDimensionProvider = $websiteDimensionProvider;
        $this->groupManagement = $groupManagement;
        $this->dimensionCollectionFactoryFactory = $dimensionCollectionFactoryFactory;
        $this->customerGroupDataProviderFactory = $customerGroupDataProviderFactory;
    }

    /**
     * @inheritdoc
     */
    public function update(GroupInterface $group, $isGroupNew)
    {
        if (!$isGroupNew) {
            return;
        }
        $target = $this->getTables($group);
        $source = $this->getTables($this->groupManagement->getDefaultGroup());
        $this->copyIndex->copy($group, $target, $source);
    }

    /**
     * Get table names by provided dimensions
     *
     * @param GroupInterface $group
     * @return array
     */
    private function getTables(GroupInterface $group)
    {
        $tables = [];
        foreach ($this->getAffectedDimensions($group) as $dimensions) {
            $tables[] = $this->indexScopeResolver->resolve('catalog_product_index_price', $dimensions);
        }

        return $tables;
    }

    /**
     * Get affected dimensions
     *
     * @param GroupInterface $group
     * @return MultiDimensionProvider
     */
    private function getAffectedDimensions(GroupInterface $group)
    {
        /** @var DimensionCollectionFactory $source */
        $source = $this->dimensionCollectionFactoryFactory->create(
            [
                'dimensionProviders' => [
                    WebsiteDimensionProvider::DIMENSION_NAME => $this->websiteDimensionProvider,
                    CustomerGroupDimensionProvider::DIMENSION_NAME => $this->customerGroupDataProviderFactory->create(
                        [
                            'customerGroup' => $group
                        ]
                    )
                ]
            ]
        );

        return $source->create();
    }
}
