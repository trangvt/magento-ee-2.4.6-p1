<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SharedCatalog\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Copier;
use Magento\Framework\Exception\LocalizedException;
use Magento\SharedCatalog\Api\ProductManagementInterface;
use Magento\SharedCatalog\Model\ProductSharedCatalogsLoader;
use Psr\Log\LoggerInterface;

/**
 * Assign products to Shared Catalog on product duplicate action.
 */
class AssignSharedCatalogOnDuplicateProductPlugin
{
    /**
     * @var ProductSharedCatalogsLoader
     */
    private $productSharedCatalogsLoader;

    /**
     * @var ProductManagementInterface
     */
    private $sharedCatalogProductManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductSharedCatalogsLoader $productSharedCatalogsLoader
     * @param ProductManagementInterface $sharedCatalogProductManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductSharedCatalogsLoader $productSharedCatalogsLoader,
        ProductManagementInterface $sharedCatalogProductManagement,
        LoggerInterface $logger
    ) {
        $this->productSharedCatalogsLoader = $productSharedCatalogsLoader;
        $this->sharedCatalogProductManagement = $sharedCatalogProductManagement;
        $this->logger = $logger;
    }

    /**
     * Product after copy plugin.
     *
     * @param Copier $subject
     * @param Product $result
     * @param Product $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCopy(
        Copier $subject,
        Product $result,
        Product $product
    ) {
        $origProductSharedCatalogs = $this->productSharedCatalogsLoader->getAssignedSharedCatalogs($product->getSku());
        if (count($origProductSharedCatalogs)) {
            foreach ($origProductSharedCatalogs as $origProductSharedCatalog) {
                try {
                    $this->sharedCatalogProductManagement->assignProducts(
                        $origProductSharedCatalog->getId(),
                        [$result]
                    );
                } catch (LocalizedException $e) {
                    $this->logger->critical($e);
                }
            }
        }

        return $result;
    }
}
