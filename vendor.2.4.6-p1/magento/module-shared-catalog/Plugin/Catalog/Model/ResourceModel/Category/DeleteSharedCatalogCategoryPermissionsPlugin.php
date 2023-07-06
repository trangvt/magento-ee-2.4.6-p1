<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;

/**
 * Delete shared catalog permissions on category delete.
 */
class DeleteSharedCatalogCategoryPermissionsPlugin
{
    /**
     * @var \Magento\SharedCatalog\Model\ResourceModel\Permission
     */
    private $sharedCatalogPermissionResource;

    /**
     * @param \Magento\SharedCatalog\Model\ResourceModel\Permission $sharedCatalogPermissionResource
     */
    public function __construct(
        \Magento\SharedCatalog\Model\ResourceModel\Permission $sharedCatalogPermissionResource
    ) {
        $this->sharedCatalogPermissionResource = $sharedCatalogPermissionResource;
    }

    /**
     * Delete Shared Catalog category permissions after deleting category.
     *
     * @param CategoryResource $subject
     * @param CategoryResource $result
     * @param CategoryInterface $category
     * @return CategoryResource
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(
        CategoryResource $subject,
        CategoryResource $result,
        CategoryInterface $category
    ) {
        $this->sharedCatalogPermissionResource->deleteItems($category->getId());
        return $result;
    }
}
