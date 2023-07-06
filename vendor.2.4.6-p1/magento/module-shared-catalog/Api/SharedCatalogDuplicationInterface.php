<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SharedCatalog\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class for processing Shared Catalog duplication actions
 *
 * @api
 */
interface SharedCatalogDuplicationInterface
{
    /**
     * Add products into the shared catalog by catalog ID and array of the products sku.
     *
     * @param int $sharedCatalogId
     * @param string[] $productsSku
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function assignProductsToDuplicate(int $sharedCatalogId, array $productsSku): void;
}
