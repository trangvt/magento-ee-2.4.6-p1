<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\CategoryManagementInterface;
use Magento\SharedCatalog\Api\Data\ProductItemInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Api\ProductManagementInterface;

/**
 * Shared catalog products actions.
 */
class ProductManagement implements ProductManagementInterface
{
    /**
     * @var ProductItemManagementInterface
     */
    private $sharedCatalogProductItemManagement;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductItemRepositoryInterface
     */
    private $sharedCatalogProductItemRepository;

    /**
     * @var SharedCatalogInvalidation
     */
    private $sharedCatalogInvalidation;

    /**
     * @var CategoryManagementInterface
     */
    private $sharedCatalogCategoryManagement;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CatalogPermissionManagement
     */
    private $catalogPermissionManagement;

    /**
     * Batch size to iterate collection
     *
     * @var int
     */
    private $batchSize;

    /**
     * ProductSharedCatalogsManagement constructor.
     *
     * @param ProductItemManagementInterface $productItemManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductItemRepositoryInterface $productItemRepository
     * @param SharedCatalogInvalidation $sharedCatalogInvalidation
     * @param CategoryManagementInterface $sharedCatalogCategoryManagement
     * @param ProductRepositoryInterface $productRepository
     * @param CatalogPermissionManagement $catalogPermissionManagement
     * @param int $batchSize defines how many items can be processed by one iteration
     */
    public function __construct(
        ProductItemManagementInterface $productItemManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductItemRepositoryInterface $productItemRepository,
        SharedCatalogInvalidation $sharedCatalogInvalidation,
        CategoryManagementInterface $sharedCatalogCategoryManagement,
        ProductRepositoryInterface $productRepository,
        CatalogPermissionManagement $catalogPermissionManagement,
        int $batchSize = 5000
    ) {
        $this->sharedCatalogProductItemManagement = $productItemManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sharedCatalogProductItemRepository = $productItemRepository;
        $this->sharedCatalogInvalidation = $sharedCatalogInvalidation;
        $this->sharedCatalogCategoryManagement = $sharedCatalogCategoryManagement;
        $this->productRepository = $productRepository;
        $this->catalogPermissionManagement = $catalogPermissionManagement;
        $this->batchSize = $batchSize;
    }

    /**
     * @inheritdoc
     */
    public function getProducts($id)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $this->searchCriteriaBuilder->addFilter(
            ProductItemInterface::CUSTOMER_GROUP_ID,
            $sharedCatalog->getCustomerGroupId()
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setPageSize($this->batchSize);

        $currentPage = 1;
        $productsSku = [];
        $totalCount = null;
        do {
            $searchCriteria->setCurrentPage($currentPage++);
            $searchResults = $this->sharedCatalogProductItemRepository->getList($searchCriteria);
            $productItems = $searchResults->getItems();
            if (count($productItems)) {
                $productsSku = array_merge($productsSku, $this->prepareProductSkus($productItems));
            }
            $totalCount = null === $totalCount
                ? $searchResults->getTotalCount() - $this->batchSize
                : $totalCount - $this->batchSize;
        } while ($totalCount > 0);

        return $productsSku;
    }

    /**
     * @inheritdoc
     */
    public function assignProducts($id, array $products)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $customerGroupIds = $this->getAssociatedCustomerGroupIds($sharedCatalog);

        $skus = [];
        foreach ($products as $product) {
            $skus[] = $product->getSku();
        }
        $skus = array_unique($skus);

        $categoryIds = $this->sharedCatalogCategoryManagement->getCategories($sharedCatalog->getId());
        $productsCategoryIds = $this->getProductsCategoryIds($skus);
        $assignCategoriesIds = array_diff($productsCategoryIds, $categoryIds);
        $this->catalogPermissionManagement->setAllowPermissions($assignCategoriesIds, $customerGroupIds);

        foreach ($customerGroupIds as $customerGroupId) {
            $this->sharedCatalogProductItemManagement->addItems($customerGroupId, $skus);
        }
        $ids = [];
        foreach ($products as $product) {
            if ($product->getId()) {
                $ids[] = $product->getId();
            }
        }
        $this->sharedCatalogInvalidation->reindexCatalogProductPermissions($ids);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function unassignProducts($id, array $products)
    {
        $sharedCatalog = $this->sharedCatalogInvalidation->checkSharedCatalogExist($id);
        $skus = $this->sharedCatalogInvalidation->validateUnassignProducts($products);
        $customerGroupIds = $this->getAssociatedCustomerGroupIds($sharedCatalog);
        foreach ($customerGroupIds as $customerGroupId) {
            $this->deleteProductItems($customerGroupId, $skus, 'in');
        }

        return true;
    }

    /**
     * Reassign products to shared catalog.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @param array $skus
     * @return $this
     */
    public function reassignProducts(SharedCatalogInterface $sharedCatalog, array $skus)
    {
        $customerGroupIds = $this->getAssociatedCustomerGroupIds($sharedCatalog);
        foreach ($customerGroupIds as $customerGroupId) {
            $this->deleteProductItems($customerGroupId, $skus);
            $this->sharedCatalogProductItemManagement->addItems($customerGroupId, $skus);
        }

        return $this;
    }

    /**
     * Delete product items from shared catalog.
     *
     * @param int $customerGroupId
     * @param array $skus [optional]
     * @param string $conditionType [optional]
     * @return $this
     */
    private function deleteProductItems(int $customerGroupId, array $skus = [], string $conditionType = 'nin')
    {
        $this->searchCriteriaBuilder->addFilter(ProductItemInterface::CUSTOMER_GROUP_ID, $customerGroupId);
        if (!empty($skus)) {
            $this->searchCriteriaBuilder->addFilter(ProductItemInterface::SKU, $skus, $conditionType);
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $productItems = $this->sharedCatalogProductItemRepository->getList($searchCriteria)->getItems();
        $this->sharedCatalogProductItemRepository->deleteItems($productItems);
        foreach ($productItems as $productItem) {
            $this->sharedCatalogInvalidation->cleanCacheByTag($productItem->getSku());
        }
        $this->sharedCatalogInvalidation->invalidateIndexRegistryItem();

        return $this;
    }

    /**
     * Prepare product skus array.
     *
     * @param ProductItemInterface[] $products
     * @return string[]
     */
    private function prepareProductSkus(array $products): array
    {
        $productsSkus = [];
        foreach ($products as $product) {
            $productsSkus[] = $product->getSku();
        }

        return $productsSkus;
    }

    /**
     * Get customer group ids that associated with shared catalog.
     *
     * @param SharedCatalogInterface $sharedCatalog
     * @return int[]
     */
    private function getAssociatedCustomerGroupIds(SharedCatalogInterface $sharedCatalog): array
    {
        $customerGroupIds = [(int) $sharedCatalog->getCustomerGroupId()];
        if ($sharedCatalog->getType() == SharedCatalogInterface::TYPE_PUBLIC) {
            $customerGroupIds[] = GroupInterface::NOT_LOGGED_IN_ID;
        }

        return $customerGroupIds;
    }

    /**
     * Get categories id for products
     *
     * @param string[] $skus
     * @return int[]
     */
    private function getProductsCategoryIds(array $skus): array
    {
        $productsCategoryIds = [];
        foreach ($skus as $sku) {
            $product = $this->productRepository->get($sku);
            $productsCategoryIds[] = (array) $product->getCategoryIds();
        }
        $productsCategoryIds = array_unique(array_merge([], ...$productsCategoryIds));

        return $productsCategoryIds;
    }
}
