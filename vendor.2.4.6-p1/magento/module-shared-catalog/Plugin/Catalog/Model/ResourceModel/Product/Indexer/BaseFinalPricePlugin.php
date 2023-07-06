<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SharedCatalog\Plugin\Catalog\Model\ResourceModel\Product\Indexer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Select;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\Query\BaseFinalPrice;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Plugin for Catalog price indexer.
 */
class BaseFinalPricePlugin
{
    public const DIRECT_PRODUCTS_PRICE_ASSIGNING = 'btob/website_configuration/direct_products_price_assigning';

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceConnection $resource
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param string $connectionName
     */
    public function __construct(
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        $connectionName = 'indexer'
    ) {
        $this->resource = $resource;
        $this->connectionName = $connectionName;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Plugin that modify indexer query to include only products that are assigned to the customer groups.
     *
     * @param BaseFinalPrice $subject
     * @param Select $result
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetQuery(BaseFinalPrice $subject, Select $result)
    {
        if ($this->scopeConfig->isSetFlag(
            self::DIRECT_PRODUCTS_PRICE_ASSIGNING,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getId()
        )
        ) {
            $sharedCatalogTableName = $this->resource->getTableName(
                'shared_catalog_product_item',
                $this->connectionName
            );

            $result->joinLeft(
                ['sct' => $sharedCatalogTableName],
                'e.sku = sct.sku AND cg.customer_group_id = sct.customer_group_id',
                []
            );

            $result->where('sct.customer_group_id IS NOT NULL');
        }

        return $result;
    }
}
