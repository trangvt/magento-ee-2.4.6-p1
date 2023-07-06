<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Plugin\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions;

use Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions\Row;
use Magento\SharedCatalog\Model\State;

/**
 * Plugin model for catalog category permission row block
 */
class RowPlugin
{
    /**
     * @var State
     */
    private $sharedCatalogConfig;

    /**
     * @param State $sharedCatalogConfig
     */
    public function __construct(
        State $sharedCatalogConfig
    ) {
        $this->sharedCatalogConfig = $sharedCatalogConfig;
    }

    /**
     * Force websites selector to show for edit.
     *
     * By default website selector is hidden if only one store is configured.
     * Category permission is auto generated for a new category for all websites if shared catalog is enabled.
     * Showing website selector gives visibility of permission scope.
     *
     * @param Row $subject
     * @param bool $canEdit
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanEditWebsites(
        Row $subject,
        bool $canEdit
    ): bool {
        if ($this->sharedCatalogConfig->isEnabled()) {
            $canEdit = true;
        }
        return $canEdit;
    }
}
