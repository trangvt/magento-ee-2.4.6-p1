<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Ui\DataProvider\Modifier\Product\Listing;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\ProductItemManagementInterface;
use Magento\SharedCatalog\Api\ProductItemRepositoryInterface;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;

/**
 * Append shared catalog(-s) data for each product.
 */
class SharedCatalog implements \Magento\Ui\DataProvider\Modifier\ModifierInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ProductItemRepositoryInterface
     */
    private $productItemRepository;
    /**
     * @var SharedCatalogRepositoryInterface
     */
    private $sharedCatalogRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductItemRepositoryInterface $productItemRepository
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductItemRepositoryInterface $productItemRepository,
        SharedCatalogRepositoryInterface $sharedCatalogRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productItemRepository = $productItemRepository;
        $this->sharedCatalogRepository = $sharedCatalogRepository;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
         return $this->appendSharedCatalogToDataSource($data);
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Appends shared catalog data.
     *
     * @param array $dataSource
     * @return array
     */
    private function appendSharedCatalogToDataSource(array $dataSource): array
    {
        if (!isset($dataSource['items'])) {
            return $dataSource;
        }

        $skus = array_column($dataSource['items'], 'sku');
        $productItems = $this->getProductItems($skus);
        foreach ($dataSource['items'] as &$item) {
            $item['shared_catalog'] = $productItems[$item['sku']] ?? '';
        }

        return $dataSource;
    }

    /**
     * Get linked products with shared catalog id(-s).
     *
     * @param array $skus
     * @return array
     */
    private function getProductItems(array $skus): array
    {
        $productItems = [];
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->addFilter('customer_group_id', ProductItemManagementInterface::CUSTOMER_GROUP_NOT_LOGGED_IN, 'neq')
            ->create();
        $items = $this->productItemRepository->getList($searchCriteria)->getItems();
        $sharedCatalogList = $this->getSharedCatalogList();

        foreach ($items as $item) {
            if (!empty($sharedCatalogList[$item->getCustomerGroupId()])) {
                $productItems[$item->getSku()][] = $sharedCatalogList[$item->getCustomerGroupId()];
            }
        }

        return $productItems;
    }

    /**
     * Retrieve shared catalog list.
     *
     * @return array
     */
    private function getSharedCatalogList(): array
    {
        $sharedCatalogs = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $items = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();

        foreach ($items as $item) {
            $sharedCatalogs[$item->getCustomerGroupId()] = $item->getId();
        }

        return $sharedCatalogs;
    }
}
