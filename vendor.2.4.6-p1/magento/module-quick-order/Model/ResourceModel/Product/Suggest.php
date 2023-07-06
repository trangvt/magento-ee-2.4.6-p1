<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\QuickOrder\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterfaceFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Prepare product collection for suggestions functionality.
 */
class Suggest
{
    /**
     * @var \Magento\QuickOrder\Model\CatalogPermissions\Permissions
     */
    private $permissions;

    /**
     * @var \Magento\Framework\DB\Helper
     */
    private $dbHelper;

    /**
     * Catalog product visibility
     *
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var SearchResultApplierInterfaceFactory
     */
    private $searchResultApplierInterfaceFactory;

    /**
     * @param \Magento\QuickOrder\Model\CatalogPermissions\Permissions $permissions
     * @param \Magento\Framework\DB\Helper $dbHelper
     * @param Visibility|null $catalogProductVisibility
     * @param SearchResultApplierInterfaceFactory|null $searchResultApplierInterfaceFactory
     */
    public function __construct(
        \Magento\QuickOrder\Model\CatalogPermissions\Permissions $permissions,
        \Magento\Framework\DB\Helper $dbHelper,
        Visibility $catalogProductVisibility = null,
        SearchResultApplierInterfaceFactory $searchResultApplierInterfaceFactory = null
    ) {
        $this->permissions = $permissions;
        $this->dbHelper = $dbHelper;
        $this->catalogProductVisibility = $catalogProductVisibility
            ?? ObjectManager::getInstance()->get(Visibility::class);
        $this->searchResultApplierInterfaceFactory = $searchResultApplierInterfaceFactory
            ?? ObjectManager::getInstance()->get(SearchResultApplierInterfaceFactory::class);
    }

    /**
     * Prepare product collection select.
     *
     * Here we prepare products collection to be ready for usage by applying following actions:
     * - inner join Fulltext Search (Elastic Search) results to the collection. It allows us to reduce the
     * collection size significantly;
     * - apply category permissions to the collection;
     * - set collection size and sort order;
     * - add required attributes to the collection;
     * - exclude hidden products with required custom options from the collection.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Framework\Api\Search\SearchResultInterface $fulltextSearchResults
     * @param int $resultLimit
     * @param string $query
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Zend_Db_Exception
     */
    public function prepareProductCollection(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Framework\Api\Search\SearchResultInterface $fulltextSearchResults,
        $resultLimit,
        $query
    ) {
        $productCollection->addAttributeToSelect(ProductInterface::NAME);

        $applier = $this->searchResultApplierInterfaceFactory->create(
            [
                'collection' => $productCollection,
                'searchResult' => $fulltextSearchResults,
                'size' => $fulltextSearchResults->getSearchCriteria()->getPageSize(),
                'currentPage' => $fulltextSearchResults->getSearchCriteria()->getCurrentPage()
            ]
        );
        $applier->apply();
        $this->permissions->applyPermissionsToProductCollection($productCollection);
        $productCollection->setPageSize($resultLimit);

        $query = $this->dbHelper->escapeLikeValue($query, ['position' => 'any']);
        $productCollection->addAttributeToFilter([
            ['attribute' => ProductInterface::SKU, 'like' => $query],
            ['attribute' => ProductInterface::NAME, 'like' => $query],
        ]);

        // here we exclude from collection hidden in catalog products with required custom options.
        $productCollection->addAttributeToFilter(
            [
                ['attribute' => 'required_options', 'neq' => 1],
                [
                    'attribute' => \Magento\Catalog\Api\Data\ProductInterface::VISIBILITY,
                    'in' => [
                        Visibility::VISIBILITY_IN_SEARCH,
                        Visibility::VISIBILITY_IN_CATALOG,
                        Visibility::VISIBILITY_BOTH
                    ]
                ]
            ]
        );

        return $productCollection;
    }
}
