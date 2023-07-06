<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Plugin\CatalogPermissions\Model\Indexer\Product;

use Magento\CatalogPermissions\Model\Indexer\Product\IndexFiller as ProductIndexFiller;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\CatalogPermissions\Model\Permission;
use Magento\SharedCatalog\Api\StatusInfoInterface;
use Magento\SharedCatalog\Model\CustomerGroupManagement;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Plugin provides additional filtration with shared catalog settings
 */
class UpdateProductPermissionsPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var StatusInfoInterface
     */
    private $sharedCatalogConfig;

    /**
     * @var CustomerGroupManagement
     */
    private $customerGroupManagement;

    /**
     * @param ResourceConnection $resource
     * @param StatusInfoInterface $sharedCatalogConfig
     * @param CustomerGroupManagement $customerGroupManagement
     */
    public function __construct(
        ResourceConnection $resource,
        StatusInfoInterface $sharedCatalogConfig,
        CustomerGroupManagement $customerGroupManagement
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->sharedCatalogConfig = $sharedCatalogConfig;
        $this->customerGroupManagement = $customerGroupManagement;
    }

    /**
     * Update product permissions base on assignments on shared catalog
     *
     * @param ProductIndexFiller $subject
     * @param mixed $result
     * @param StoreInterface $store
     * @param int $customerGroupId
     * @param string $categoryPermissionsTable
     * @param string $productPermissionsTable
     * @param array $productIds
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPopulate(
        ProductIndexFiller $subject,
        $result,
        StoreInterface $store,
        int $customerGroupId,
        string $categoryPermissionsTable,
        string $productPermissionsTable,
        array $productIds
    ): void {
        if ($this->sharedCatalogConfig->isActive(ScopeInterface::SCOPE_STORE, $store->getId())
            && !$this->customerGroupManagement->isPrimaryCatalogAvailable($customerGroupId)
        ) {
            $select = $this->connection->select();
            $select->joinInner(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                'cpe.entity_id = i.product_id',
                []
            )->joinLeft(
                ['scpi' => $this->resource->getTableName('shared_catalog_product_item')],
                'scpi.sku = cpe.sku AND scpi.customer_group_id = i.customer_group_id',
                []
            )->where(
                'i.store_id = ?',
                $store->getId(),
                'INT'
            )->where(
                'i.customer_group_id = ?',
                $customerGroupId,
                'INT'
            )->where(
                'scpi.entity_id IS NULL'
            )->columns(
                ['grant_catalog_category_view' => new \Zend_Db_Expr(Permission::PERMISSION_DENY)]
            );
            $sql = $select->crossUpdateFromSelect(['i' => $productPermissionsTable]);
            $this->connection->query($sql);
        }
    }
}
